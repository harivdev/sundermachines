<?php
// Sanruth ERP — WhatsApp Cloud API Notification Helper
// Handles template delivery for Admin & Customers with Permanent System User Token.
require_once(__DIR__ . '/../config/db.php');

if (!defined('META_PHONE_NUMBER_ID')) {
    define('META_PHONE_NUMBER_ID', getenv('META_PHONE_NUMBER_ID') ?: ($_ENV['META_PHONE_NUMBER_ID'] ?? ''));
}

if (!defined('META_ACCESS_TOKEN')) {
    define('META_ACCESS_TOKEN', getenv('META_ACCESS_TOKEN') ?: ($_ENV['META_ACCESS_TOKEN'] ?? ''));
}

if (!defined('ADMIN_WHATSAPP_NUMBER')) {
    define('ADMIN_WHATSAPP_NUMBER', getenv('ADMIN_WHATSAPP_NUMBER') ?: ($_ENV['ADMIN_WHATSAPP_NUMBER'] ?? ''));
}

if (!defined('META_API_VERSION')) {
    define('META_API_VERSION', getenv('META_API_VERSION') ?: ($_ENV['META_API_VERSION'] ?? 'v20.0'));
}

/**
 * Universal HTTP POST Helper for Meta WhatsApp Graph API
 */
function meta_whatsapp_http_post($url, $payload, $accessToken) {
    $jsonPayload = json_encode($payload);
    $response = false;
    $httpCode = 0;

    // 1. Try PHP cURL Extension if available
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$accessToken}",
                "Content-Type: application/json"
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT        => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response !== false && $httpCode > 0) {
            return [$httpCode, $response];
        }
    }

    // 2. Try file_get_contents Stream Context
    $opts = [
        'http' => [
            'method'        => 'POST',
            'header'        => "Authorization: Bearer {$accessToken}\r\n" .
                               "Content-Type: application/json\r\n",
            'content'       => $jsonPayload,
            'timeout'       => 15,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false
        ]
    ];
    $context  = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);

    if ($response !== false) {
        $code = 200;
        if (isset($http_response_header) && is_array($http_response_header)) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d{3})/', $http_response_header[0] ?? '', $m)) {
                $code = (int)$m[1];
            }
        }
        if ($code > 0 && !empty($response)) {
            return [$code, $response];
        }
    }

    // 3. Fallback to Windows System curl.exe
    $tmpFile = tempnam(sys_get_temp_dir(), 'wa_');
    file_put_contents($tmpFile, $jsonPayload);

    $cmd = 'curl.exe -k -s -w "\n%{http_code}" -X POST ' . escapeshellarg($url) .
           ' -H "Authorization: Bearer ' . addslashes($accessToken) . '"' .
           ' -H "Content-Type: application/json"' .
           ' --data-binary @' . escapeshellarg($tmpFile);

    $sysOut = @shell_exec($cmd);
    @unlink($tmpFile);

    if (!empty($sysOut)) {
        $lines = explode("\n", trim($sysOut));
        $httpCodeStr = array_pop($lines);
        $httpCode = (int)$httpCodeStr;
        $body = implode("\n", $lines);
        return [$httpCode ?: 200, $body];
    }

    return [0, "HTTP connection failed"];
}

/**
 * Master Central WhatsApp Template Sender
 */
function send_whatsapp_template(
    $templateName,
    array $parameters,
    $recipientPhone = null,
    $eventCode = 'GENERIC',
    $docId = null,
    $docNumber = null,
    $idempotencyKey = null,
    $langCode = 'en'
) {
    try {
        // 1. Resolve Recipient Phone Number
        $targetPhone = !empty($recipientPhone) ? $recipientPhone : ADMIN_WHATSAPP_NUMBER;
        $cleanPhone = preg_replace('/[^0-9]/', '', $targetPhone);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        // 2. Build Meta API Parameters Component & Sanitize Input
        $bodyParams = [];
        foreach ($parameters as $param) {
            $paramStr = (string)($param ?? '');
            if (trim($paramStr) === '') {
                $paramStr = 'N/A';
            }
            $paramStr = preg_replace('/[\r\n\t\x00-\x1F\x7F]+/u', ' ', $paramStr);
            $paramStr = preg_replace('/\s+/', ' ', $paramStr);
            $paramStr = trim($paramStr);
            if ($paramStr === '') {
                $paramStr = 'N/A';
            }

            $bodyParams[] = [
                'type' => 'text',
                'text' => $paramStr
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $cleanPhone,
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => [ 'code' => $langCode ]
            ]
        ];

        if (!empty($bodyParams)) {
            $payload['template']['components'] = [
                [
                    'type'       => 'body',
                    'parameters' => $bodyParams
                ]
            ];
        }

        // 3. Send POST to Meta Graph API
        $phoneId = META_PHONE_NUMBER_ID;
        $accessToken = META_ACCESS_TOKEN;
        $apiVersion = META_API_VERSION;
        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneId}/messages";

        list($httpCode, $responseBody) = meta_whatsapp_http_post($url, $payload, $accessToken);

        // Auto-fallback: if language code differs (en vs en_US), retry
        if (($httpCode === 404 || $httpCode === 400) && strpos($responseBody, '132001') !== false) {
            $fallbackLang = ($langCode === 'en') ? 'en_US' : 'en';
            $payload['template']['language']['code'] = $fallbackLang;
            list($retryCode, $retryBody) = meta_whatsapp_http_post($url, $payload, $accessToken);
            if ($retryCode >= 200 && $retryCode < 300) {
                $httpCode = $retryCode;
                $responseBody = $retryBody;
            }
        }

        // 4. Parse Meta API Response
        $success = false;
        $wamid = null;
        $errorMsg = null;
        $resData = json_decode($responseBody, true);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($resData['messages'][0]['id'])) {
            $success = true;
            $wamid = $resData['messages'][0]['id'];
        } else {
            $success = false;
            if (isset($resData['error']['message'])) {
                $errorMsg = "HTTP {$httpCode}: " . $resData['error']['message'];
                if (isset($resData['error']['error_data']['details'])) {
                    $errorMsg .= " (" . $resData['error']['error_data']['details'] . ")";
                }
            } else {
                $errorMsg = "HTTP {$httpCode}: " . substr($responseBody, 0, 255);
            }
            error_log("WhatsApp Notification Failure [$templateName -> $cleanPhone]: $errorMsg");
        }

        return [
            'success'             => $success,
            'status'              => $success ? 'sent' : 'failed',
            'whatsapp_message_id' => $wamid,
            'error'               => $errorMsg
        ];

    } catch (Exception $e) {
        error_log("WhatsApp Central Exception: " . $e->getMessage());
        return [
            'success' => false,
            'status'  => 'failed',
            'error'   => $e->getMessage()
        ];
    }
}

// 1. Job Card Created (Single Recipient)
function send_job_card_notification($jobCardNumber, $customerName, $primaryPhone, $city = null, $jobCardId = null, $recipientPhone = null) {
    $safeCity = !empty($city) ? $city : 'Not provided';
    $parameters = [
        (string)$jobCardNumber,
        (string)($customerName ?: 'Customer'),
        (string)($primaryPhone ?: 'N/A'),
        (string)$safeCity
    ];

    $target = !empty($recipientPhone) ? $recipientPhone : ADMIN_WHATSAPP_NUMBER;

    return send_whatsapp_template(
        'job_card_registered',
        $parameters,
        $target,
        'JOB_CARD_REGISTERED',
        $jobCardId,
        $jobCardNumber,
        null,
        'en'
    );
}

// 1b. Job Card Created Dual Dispatch (Admin + Customer at the same time)
function send_job_card_dual_notification($jobCardNumber, $customerName, $primaryPhone, $city = null, $jobCardId = null) {
    $cleanAdmin = preg_replace('/[^0-9]/', '', ADMIN_WHATSAPP_NUMBER);
    $cleanCust = preg_replace('/[^0-9]/', '', (string)$primaryPhone);
    if (strlen($cleanCust) === 10) {
        $cleanCust = '91' . $cleanCust;
    }

    // 1. Send to Admin
    $adminRes = send_job_card_notification($jobCardNumber, $customerName, $primaryPhone, $city, $jobCardId, $cleanAdmin);
    
    // 2. Send to Customer (if valid phone provided)
    $custRes = null;
    if (!empty($cleanCust) && strlen($cleanCust) >= 10 && $cleanCust !== $cleanAdmin) {
        $custRes = send_job_card_notification($jobCardNumber, $customerName, $cleanCust, $city, $jobCardId, $cleanCust);
    }

    return [
        'admin'    => $adminRes,
        'customer' => $custRes
    ];
}

// 2. Job Card Delivered (Single Recipient)
function send_job_card_delivered_notification(
    $jobCardNumber,
    $customerName,
    $primaryPhone,
    $city = null,
    $machineType = null,
    $serialNo = null,
    $totalAmount = 0,
    $paidAmount = 0,
    $jobCardId = null,
    $recipientPhone = null
) {
    $safeCity       = !empty($city) ? $city : 'Not provided';
    $safeMachine    = !empty($machineType) ? $machineType : 'N/A';
    $safeSerial     = !empty($serialNo) ? $serialNo : 'N/A';
    $formattedTotal = number_format((float)$totalAmount, 2, '.', '');
    $formattedPaid  = number_format((float)$paidAmount, 2, '.', '');

    $parameters = [
        (string)$jobCardNumber,
        (string)($customerName ?: 'Customer'),
        (string)($primaryPhone ?: 'N/A'),
        (string)$safeCity,
        (string)$safeMachine,
        (string)$safeSerial,
        (string)$formattedTotal,
        (string)$formattedPaid
    ];

    $target = !empty($recipientPhone) ? $recipientPhone : ADMIN_WHATSAPP_NUMBER;

    return send_whatsapp_template(
        'jobcard_delivered',
        $parameters,
        $target,
        'JOB_CARD_DELIVERED',
        $jobCardId,
        $jobCardNumber,
        null,
        'en'
    );
}

// 2b. Job Card Delivered Dual Dispatch (Admin + Customer at the same time)
function send_job_card_delivered_dual_notification(
    $jobCardNumber,
    $customerName,
    $primaryPhone,
    $city = null,
    $machineType = null,
    $serialNo = null,
    $totalAmount = 0,
    $paidAmount = 0,
    $jobCardId = null
) {
    $cleanAdmin = preg_replace('/[^0-9]/', '', ADMIN_WHATSAPP_NUMBER);
    $cleanCust = preg_replace('/[^0-9]/', '', (string)$primaryPhone);
    if (strlen($cleanCust) === 10) {
        $cleanCust = '91' . $cleanCust;
    }

    // 1. Send to Admin
    $adminRes = send_job_card_delivered_notification(
        $jobCardNumber,
        $customerName,
        $primaryPhone,
        $city,
        $machineType,
        $serialNo,
        $totalAmount,
        $paidAmount,
        $jobCardId,
        $cleanAdmin
    );

    // 2. Send to Customer (if valid phone provided)
    $custRes = null;
    if (!empty($cleanCust) && strlen($cleanCust) >= 10 && $cleanCust !== $cleanAdmin) {
        $custRes = send_job_card_delivered_notification(
            $jobCardNumber,
            $customerName,
            $cleanCust,
            $city,
            $machineType,
            $serialNo,
            $totalAmount,
            $paidAmount,
            $jobCardId,
            $cleanCust
        );
    }

    return [
        'admin'    => $adminRes,
        'customer' => $custRes
    ];
}

// 3. Stock Reorder / Low Stock (Admin Alert)
function send_stock_reorder_notification($itemName, $barcode, $currentStock, $reorderLevel, $stockId = null) {
    $parameters = [
        (string)$itemName,
        (string)($barcode ?: 'N/A'),
        (string)$currentStock . ' Pcs',
        (string)$reorderLevel . ' Pcs'
    ];

    return send_whatsapp_template(
        'stock_reorder_alert',
        $parameters,
        ADMIN_WHATSAPP_NUMBER,
        'STOCK_REORDER',
        $stockId,
        $barcode,
        null,
        'en_US'
    );
}

// 4. Sales Order Created (Admin Alert)
function send_sales_order_notification($orderId, $customerName, $customerPhone, $sparesSummary, $totalAmount, $salesId = null) {
    $formattedAmount = number_format((float)$totalAmount, 2, '.', '');
    $safeSpares      = (!empty($sparesSummary) && !is_numeric($sparesSummary)) ? $sparesSummary : 'Purchased Spares';
    $safePhone       = !empty($customerPhone) ? $customerPhone : 'N/A';

    $parameters = [
        (string)$orderId,
        (string)($customerName ?: 'Customer'),
        (string)$safePhone,
        (string)$safeSpares,
        (string)$formattedAmount
    ];

    return send_whatsapp_template(
        'sales_order_created',
        $parameters,
        ADMIN_WHATSAPP_NUMBER,
        'SALES_ORDER_CREATED_ADMIN',
        $salesId,
        $orderId,
        null,
        'en_US'
    );
}

// 5. Purchase Order Created (Admin Alert)
function send_purchase_notification($orderId, $supplierName, $productSummary, $totalAmount, $purchaseId = null) {
    $formattedAmount = number_format((float)$totalAmount, 2, '.', '');
    $parameters = [
        (string)$orderId,
        (string)($supplierName ?: 'Supplier'),
        (string)$productSummary ?: 'Purchase Items',
        (string)$formattedAmount
    ];

    return send_whatsapp_template(
        'purchase_order_created',
        $parameters,
        ADMIN_WHATSAPP_NUMBER,
        'PURCHASE_ORDER_CREATED_ADMIN',
        $purchaseId,
        $orderId,
        null,
        'en_US'
    );
}

// 6. Daily Sales Report (Admin Alert)
function send_daily_sales_report_notification($reportDate, $orderCount, $totalSales, $cost, $grossProfit) {
    $formattedDate = date('d M Y', strtotime($reportDate));
    $parameters = [
        (string)$formattedDate,
        (string)$orderCount,
        number_format((float)$totalSales, 2, '.', ''),
        number_format((float)$cost, 2, '.', ''),
        number_format((float)$grossProfit, 2, '.', '')
    ];

    return send_whatsapp_template(
        'daily_sales_report',
        $parameters,
        ADMIN_WHATSAPP_NUMBER,
        'DAILY_SALES_REPORT',
        null,
        $formattedDate,
        null,
        'en'
    );
}

/**
 * Build Formatted WhatsApp Message String for Job Card Intake / Registration
 */
function get_job_card_intake_formatted_message($jobCardNumber, $customerName, $primaryPhone, $city = null, $machineName = null, $serialNo = null, $workDetails = null, $remarks = null, $jobStatus = null) {
    $cNo = str_replace(['/', ' '], '', $jobCardNumber);
    $cName = $customerName ?: 'Customer';
    $phone = $primaryPhone ?: 'N/A';
    $safeCity = $city ?: 'Not provided';
    $machine = $machineName ?: 'N/A';
    $serial = $serialNo ?: 'N/A';
    $work = $workDetails ?: 'Service';
    $statusText = $jobStatus ?: 'New (Received for Service)';

    $icBuilding = mb_chr(0x1F3E2, 'UTF-8');
    $icReceipt  = mb_chr(0x1F9FE, 'UTF-8');
    $icClip     = mb_chr(0x1F4CB, 'UTF-8');
    $icId       = mb_chr(0x1F194, 'UTF-8');
    $icCal      = mb_chr(0x1F4C5, 'UTF-8');
    $icRefresh  = mb_chr(0x1F504, 'UTF-8');
    $icUser     = mb_chr(0x1F464, 'UTF-8');
    $icPhone    = mb_chr(0x1F4DE, 'UTF-8');
    $icCity     = mb_chr(0x1F3D9, 'UTF-8') . mb_chr(0xFE0F, 'UTF-8');
    $icGear     = mb_chr(0x2699, 'UTF-8')  . mb_chr(0xFE0F, 'UTF-8');
    $icNum      = mb_chr(0x1F522, 'UTF-8');
    $icTools    = mb_chr(0x1F6E0, 'UTF-8') . mb_chr(0xFE0F, 'UTF-8');
    $icMemo     = mb_chr(0x1F4DD, 'UTF-8');
    $icPin      = mb_chr(0x1F4CD, 'UTF-8');
    $icInbox    = mb_chr(0x1F4E5, 'UTF-8');
    $icWarn     = mb_chr(0x26A0, 'UTF-8')  . mb_chr(0xFE0F, 'UTF-8');
    $icPray     = mb_chr(0x1F64F, 'UTF-8');
    $icThread   = mb_chr(0x1F9F6, 'UTF-8');
    $icNeedle   = mb_chr(0x1FAA1, 'UTF-8');

    $lines = [];
    $lines[] = "{$icBuilding} *SUNDER MACHNES WORLD*";
    $lines[] = "{$icClip} *JOB CARD INTAKE RECEIPT*";
    $lines[] = "─────────────────";
    $lines[] = "*{$icClip} JOB CARD DETAILS*";
    $lines[] = "Job Card No: {$cNo}";
    $lines[] = "Date & Time: " . date('d/m/Y h:i A');
    $lines[] = "Status: {$statusText}";
    $lines[] = "";
    $lines[] = "*{$icUser} CUSTOMER DETAILS*";
    $lines[] = "Customer Name: {$cName}";
    $lines[] = "Phone Number: {$phone}";
    $lines[] = "City / Location: {$safeCity}";
    $lines[] = "";
    $lines[] = "*{$icGear} MACHINE & SERVICE DETAILS*";
    $lines[] = "Machine Model: {$machine}";
    $lines[] = "Serial No: {$serial}";
    $lines[] = "Work Details: {$work}";
    if (!empty($remarks) && $remarks !== '—') {
        $lines[] = "Remarks: {$remarks}";
    }
    $lines[] = "─────────────────";
    $lines[] = "*{$icPin} IMPORTANT NOTICE*";
    $lines[] = "Your machine has been received safely for service.";
    $lines[] = "Goods cannot be claimed without presenting job card receipt.";
    $lines[] = "─────────────────";
    $lines[] = "{$icPray} *Thank you for choosing Sunder Machnes World!*";
    $lines[] = "{$icThread} *Sunder Machines World* {$icNeedle}";

    return implode("\n", $lines);
}

/**
 * Build Formatted WhatsApp Message String for Job Card Service Delivery & Bill
 */
function get_job_card_delivered_formatted_message(
    $jobCardNumber,
    $customerName,
    $primaryPhone,
    $city = null,
    $machineName = null,
    $serialNo = null,
    $workDetails = null,
    array $spares = [],
    $laborCharge = 0,
    $grandTotal = 0,
    $paidAmount = 0,
    $jobStatus = null
) {
    $cNo = str_replace(['/', ' '], '', $jobCardNumber);
    $cName = $customerName ?: 'Customer';
    $phone = $primaryPhone ?: 'N/A';
    $safeCity = $city ?: 'Not provided';
    $machine = $machineName ?: 'N/A';
    $serial = $serialNo ?: 'N/A';
    $work = $workDetails ?: 'Service';
    $statusText = $jobStatus ?: 'Service Delivered';

    $icBuilding = mb_chr(0x1F3E2, 'UTF-8');
    $icReceipt  = mb_chr(0x1F9FE, 'UTF-8');
    $icClip     = mb_chr(0x1F4CB, 'UTF-8');
    $icId       = mb_chr(0x1F194, 'UTF-8');
    $icCal      = mb_chr(0x1F4C5, 'UTF-8');
    $icRefresh  = mb_chr(0x1F504, 'UTF-8');
    $icUser     = mb_chr(0x1F464, 'UTF-8');
    $icPhone    = mb_chr(0x1F4DE, 'UTF-8');
    $icCity     = mb_chr(0x1F3D9, 'UTF-8') . mb_chr(0xFE0F, 'UTF-8');
    $icGear     = mb_chr(0x2699, 'UTF-8')  . mb_chr(0xFE0F, 'UTF-8');
    $icNum      = mb_chr(0x1F522, 'UTF-8');
    $icTools    = mb_chr(0x1F6E0, 'UTF-8') . mb_chr(0xFE0F, 'UTF-8');
    $icBolt     = mb_chr(0x1F529, 'UTF-8');
    $icBullet   = mb_chr(0x1F539, 'UTF-8');
    $icCard     = mb_chr(0x1F4B3, 'UTF-8');
    $icMoney    = mb_chr(0x1F4B5, 'UTF-8');
    $icGreen    = mb_chr(0x1F7E2, 'UTF-8');
    $icPin      = mb_chr(0x1F4CD, 'UTF-8');
    $icPray     = mb_chr(0x1F64F, 'UTF-8');
    $icThread   = mb_chr(0x1F9F6, 'UTF-8');
    $icNeedle   = mb_chr(0x1FAA1, 'UTF-8');

    $lines = [];
    $lines[] = "{$icBuilding} *SUNDER MACHNES WORLD*";
    $lines[] = "{$icReceipt} *JOB CARD SERVICE BILL & DELIVERY RECEIPT*";
    $lines[] = "─────────────────";
    $lines[] = "*{$icClip} JOB CARD DETAILS*";
    $lines[] = "Job Card Bill No: {$cNo}";
    $lines[] = "Date & Time: " . date('d/m/Y h:i A');
    $lines[] = "Status: {$statusText}";
    $lines[] = "";
    $lines[] = "*{$icUser} CUSTOMER DETAILS*";
    $lines[] = "Customer Name: {$cName}";
    $lines[] = "Phone Number: {$phone}";
    $lines[] = "City / Location: {$safeCity}";
    $lines[] = "";
    $lines[] = "*{$icGear} MACHINE DETAILS*";
    $lines[] = "Machine Model: {$machine}";
    $lines[] = "Serial No: {$serial}";
    $lines[] = "Work Details: {$work}";
    $lines[] = "";
    $lines[] = "*{$icBolt} SPARES & LABOUR CHARGES*";

    if (!empty($spares)) {
        $idx = 1;
        foreach ($spares as $sp) {
            $name = htmlspecialchars_decode($sp['itemName'] ?? 'Spare Item');
            $qty = (int)($sp['quantity'] ?? 1);
            $total = (float)($sp['totalPrice'] ?? ($qty * (float)($sp['pricePerQty'] ?? 0)));
            $lines[] = "{$idx}. {$name} × {$qty} = ₹ " . number_format($total, 2);
            $idx++;
        }
    }
    if ((float)$laborCharge > 0) {
        $lines[] = "Labour Charge: ₹ " . number_format((float)$laborCharge, 2);
    }

    $lines[] = "";
    $lines[] = "*{$icCard} PAYMENT SUMMARY*";
    $lines[] = "Grand Total: ₹ " . number_format((float)$grandTotal, 2);
    $lines[] = "Paid Amount: ₹ " . number_format((float)$paidAmount, 2);

    $balance = max(0, (float)$grandTotal - (float)$paidAmount);
    if ($balance > 0) {
        $lines[] = "Balance Due: ₹ " . number_format($balance, 2);
    }
    $lines[] = "─────────────────";
    $lines[] = "{$icPray} *Thank you for your business!*";
    $lines[] = "{$icThread} *Sunder Machines World* {$icNeedle}";

    return implode("\n", $lines);
}



