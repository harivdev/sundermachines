<?php
/**
 * Sanruth ERP — Meta WhatsApp Cloud API Helper & Notification Engine
 * Location: whatsapp/config.php
 * 
 * Supports:
 * - Admin-Only notifications & customer notification readiness
 * - Central payload builder & HTTPS transport
 * - Database logging in `whatsapp_notification_log`
 * - Idempotency & Duplicate Protection
 * - Parameter sanitization (Strips invalid newlines & tabs to prevent Meta Error 132018)
 * - Automatic language fallback (en_US / en)
 * - 5 Event-Specific Wrappers:
 *   1. job_card_created
 *   2. stock_reorder_alert
 *   3. sales_order_created
 *   4. purchase_order_created
 *   5. daily_sales_report
 */

require_once(__DIR__ . '/../config/db.php');

if (!defined('META_PHONE_NUMBER_ID')) {
    define('META_PHONE_NUMBER_ID', '1299438076594870');
}

if (!defined('META_ACCESS_TOKEN')) {
    define('META_ACCESS_TOKEN', 'EAAUOp13OCZCkBSWZAHILBBvGeghV7rl9flff3RTK8uKfeMEeLR5Cg8ffn9i6Qg3ec4lPnwwbuspNArZAqT0MuuCO65zLSa2OV0eiakueC5fkyMwoWmUOwx5i3s1XGQw0r2WdGMdtj5VSkiDfMRUxmVQepaaFbwrCJkmyL942HehOrHBZAfCGHaN6gaZCCZAfWk6QZDZD');
}

if (!defined('ADMIN_WHATSAPP_NUMBER')) {
    define('ADMIN_WHATSAPP_NUMBER', '917418735076');
}

if (!defined('META_API_VERSION')) {
    define('META_API_VERSION', 'v20.0');
}

/**
 * Universal HTTP POST Helper for Meta WhatsApp Graph API
 */
function meta_whatsapp_http_post($url, $payload, $accessToken) {
    $jsonPayload = json_encode($payload);
    $response = false;
    $httpCode = 0;

    // 1. Try PHP cURL Extension
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
        return [$code, $response];
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
 * Performs duplicate suppression, HTTPS transport, and database logging.
 */
function send_whatsapp_template(
    $templateName,
    array $parameters,
    $recipientPhone = null,
    $eventCode = 'GENERIC',
    $docId = null,
    $docNumber = null,
    $idempotencyKey = null,
    $langCode = 'en_US'
) {
    global $conn;

    try {
        // 1. Resolve Recipient Phone Number
        $targetPhone = !empty($recipientPhone) ? $recipientPhone : ADMIN_WHATSAPP_NUMBER;
        $cleanPhone = preg_replace('/[^0-9]/', '', $targetPhone);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        // 2. Resolve Idempotency Key (default to event_code + docId/docNumber + recipient)
        if (empty($idempotencyKey)) {
            $idempotencyKey = $eventCode . '_' . ($docId ?? $docNumber ?? date('Ymd')) . '_' . $cleanPhone;
        }

        // 3. Check for Existing Successful Log Entry (Duplicate Protection)
        if (isset($conn) && $conn) {
            $e_idempotency = mysqli_real_escape_string($conn, $idempotencyKey);
            $checkSql = "SELECT id, status, whatsapp_message_id FROM whatsapp_notification_log WHERE idempotency_key = '$e_idempotency' AND status = 'SENT' LIMIT 1";
            $res = mysqli_query($conn, $checkSql);
            if ($res && mysqli_num_rows($res) > 0) {
                $row = mysqli_fetch_assoc($res);
                return [
                    'success'            => true,
                    'status'             => 'skipped',
                    'message'            => 'Duplicate notification suppressed',
                    'whatsapp_message_id' => $row['whatsapp_message_id'] ?? null
                ];
            }
        }

        // 4. Build Meta API Parameters Component & Sanitize Input
        $bodyParams = [];
        foreach ($parameters as $param) {
            $paramStr = (string)($param ?? '');
            if (trim($paramStr) === '') {
                $paramStr = 'N/A'; // Safe fallback for empty optional parameters
            }
            // Sanitize parameter text: Meta API forbids newlines (\n) or tabs (\t) in template parameters (Error 132018)
            $paramStr = str_replace(["\r\n", "\r", "\n", "\t"], [" • ", " ", " • ", " "], $paramStr);
            $paramStr = preg_replace('/\s+/', ' ', $paramStr); // Collapse multiple spaces

            $bodyParams[] = [
                'type' => 'text',
                'text' => trim($paramStr)
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $cleanPhone,
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => [ 'code' => $langCode ],
                'components' => [
                    [
                        'type'       => 'body',
                        'parameters' => $bodyParams
                    ]
                ]
            ]
        ];

        // 5. Send POST to Meta Graph API
        $phoneId = META_PHONE_NUMBER_ID;
        $accessToken = META_ACCESS_TOKEN;
        $apiVersion = META_API_VERSION;
        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneId}/messages";

        list($httpCode, $responseBody) = meta_whatsapp_http_post($url, $payload, $accessToken);

        // Auto-fallback: If Meta returns 132001 for 'en_US', retry with 'en' (or vice-versa)
        if (($httpCode === 404 || $httpCode === 400) && strpos($responseBody, '132001') !== false) {
            $fallbackLang = ($langCode === 'en_US') ? 'en' : 'en_US';
            $payload['template']['language']['code'] = $fallbackLang;
            list($retryCode, $retryBody) = meta_whatsapp_http_post($url, $payload, $accessToken);
            if ($retryCode >= 200 && $retryCode < 300) {
                $httpCode = $retryCode;
                $responseBody = $retryBody;
            }
        }

        // 6. Parse Meta API Response
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
        }

        // 7. Write to `whatsapp_notification_log` Table
        if (isset($conn) && $conn) {
            $statusStr   = $success ? 'SENT' : 'FAILED';
            $e_eventCode = mysqli_real_escape_string($conn, $eventCode);
            $e_docId     = $docId ? (int)$docId : "NULL";
            $e_docNo     = $docNumber ? "'" . mysqli_real_escape_string($conn, $docNumber) . "'" : "NULL";
            $e_phone     = mysqli_real_escape_string($conn, $cleanPhone);
            $e_tpl       = mysqli_real_escape_string($conn, $templateName);
            $e_params    = mysqli_real_escape_string($conn, json_encode($parameters));
            $e_wamid     = $wamid ? "'" . mysqli_real_escape_string($conn, $wamid) . "'" : "NULL";
            $e_error     = $errorMsg ? "'" . mysqli_real_escape_string($conn, $errorMsg) . "'" : "NULL";
            $e_idem      = mysqli_real_escape_string($conn, $idempotencyKey);
            $sentAt      = $success ? "NOW()" : "NULL";

            $logSql = "INSERT INTO whatsapp_notification_log 
                (event_code, document_id, document_number, recipient_phone, template_name, parameters_json, status, whatsapp_message_id, error_message, attempt_count, idempotency_key, created_at, sent_at, last_attempt_at)
                VALUES
                ('$e_eventCode', $e_docId, $e_docNo, '$e_phone', '$e_tpl', '$e_params', '$statusStr', $e_wamid, $e_error, 1, '$e_idem', NOW(), $sentAt, NOW())
                ON DUPLICATE KEY UPDATE 
                    status = VALUES(status),
                    whatsapp_message_id = VALUES(whatsapp_message_id),
                    error_message = VALUES(error_message),
                    attempt_count = attempt_count + 1,
                    sent_at = VALUES(sent_at),
                    last_attempt_at = NOW()";

            @mysqli_query($conn, $logSql);
        }

        return [
            'success'            => $success,
            'status'             => $success ? 'sent' : 'failed',
            'whatsapp_message_id' => $wamid,
            'error'              => $errorMsg
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

/* ════════════════════════════════════════════════════════════
   EVENT-SPECIFIC WRAPPER FUNCTIONS (ADMIN-ONLY NOTIFICATIONS)
   ════════════════════════════════════════════════════════════ */

/**
 * Helper to try primary event template with language fallback (en_US / en).
 * Direct template delivery only — NO hello_world sample message fallback.
 */
function send_template_with_fallback($primaryTpl, array $params, $recipient, $eventCode, $docId = null, $docNo = null, $idemKey = null, $primaryLang = 'en_US') {
    // 1. Attempt Primary Specific Template (e.g. purchase_order_created) with primaryLang
    $res = send_whatsapp_template($primaryTpl, $params, $recipient, $eventCode, $docId, $docNo, $idemKey, $primaryLang);
    
    // 2. If Meta returns 132001 (language code mismatch), retry with alternate language (en vs en_US)
    if (!$res['success'] && strpos($res['error'] ?? '', '132001') !== false) {
        $altLang = ($primaryLang === 'en_US') ? 'en' : 'en_US';
        $altIdem = ($idemKey ?: ($eventCode . '_' . ($docId ?: $docNo))) . '_ALT_LANG';
        $res = send_whatsapp_template($primaryTpl, $params, $recipient, $eventCode, $docId, $docNo, $altIdem, $altLang);
    }
    
    return $res;
}

/**
 * 1. JOB CARD CREATED
 * Template: job_card_created
 * Parameters: {{1}} CardNo, {{2}} Customer Name, {{3}} Phone, {{4}} City
 */
function send_job_card_notification($jobCardNumber, $customerName, $primaryPhone, $city = null, $jobCardId = null) {
    $safeCity = !empty($city) ? $city : 'Not provided';
    $parameters = [
        (string)$jobCardNumber,
        (string)($customerName ?: 'Customer'),
        (string)($primaryPhone ?: 'N/A'),
        (string)$safeCity
    ];

    return send_template_with_fallback(
        'job_card_created',
        $parameters,
        ADMIN_WHATSAPP_NUMBER,
        'JOB_CARD_CREATED',
        $jobCardId,
        $jobCardNumber,
        null,
        'en_US'
    );
}

/**
 * 2. STOCK REORDER REMINDER
 * Template: stock_reorder_alert
 * Parameters: {{1}} Item Name, {{2}} Barcode, {{3}} Current Stock, {{4}} Reorder Level
 */
function send_stock_reorder_notification($itemName, $barcode, $currentStock, $reorderLevel, $stockId = null) {
    $parameters = [
        (string)$itemName,
        (string)($barcode ?: 'N/A'),
        (string)$currentStock . ' Pcs',
        (string)$reorderLevel . ' Pcs'
    ];

    $idempotencyKey = "STOCK_REORDER_" . ($stockId ?: preg_replace('/[^A-Za-z0-9]/', '', $barcode ?: $itemName)) . "_QTY" . $currentStock;

    return send_template_with_fallback(
        'stock_reorder_alert',
        $parameters,
        ADMIN_WHATSAPP_NUMBER,
        'STOCK_REORDER',
        $stockId,
        $barcode,
        $idempotencyKey,
        'en_US'
    );
}

/**
 * 3. SALES ORDER CREATED
 * Template: sales_order_created
 * Parameters: {{1}} Order ID, {{2}} Customer Name, {{3}} Phone, {{4}} WhatsApp, {{5}} Total Amount
 */
function send_sales_order_notification($orderId, $customerName, $primaryPhone, $whatsappNumber, $totalAmount, $salesId = null) {
    $formattedAmount = number_format((float)$totalAmount, 2, '.', '');
    $safePhone = !empty($primaryPhone) ? $primaryPhone : 'N/A';
    $safeWhatsApp = !empty($whatsappNumber) ? $whatsappNumber : $safePhone;

    $parameters = [
        (string)$orderId,
        (string)($customerName ?: 'Customer'),
        (string)$safePhone,
        (string)$safeWhatsApp,
        (string)$formattedAmount
    ];

    return send_template_with_fallback(
        'sales_order_created',
        $parameters,
        ADMIN_WHATSAPP_NUMBER,
        'SALES_ORDER_CREATED',
        $salesId,
        $orderId,
        null,
        'en_US'
    );
}

/**
 * 4. PURCHASE ORDER CREATED
 * Template: purchase_order_created
 * Parameters: {{1}} Order ID, {{2}} Supplier Name, {{3}} Product Summary, {{4}} Total Amount
 */
function send_purchase_notification($orderId, $supplierName, $productSummary, $totalAmount, $purchaseId = null) {
    $formattedAmount = number_format((float)$totalAmount, 2, '.', '');
    $parameters = [
        (string)$orderId,
        (string)($supplierName ?: 'Supplier'),
        (string)($productSummary ?: 'Purchase Items'),
        (string)$formattedAmount
    ];

    return send_template_with_fallback(
        'purchase_order_created',
        $parameters,
        ADMIN_WHATSAPP_NUMBER,
        'PURCHASE_ORDER_CREATED',
        $purchaseId,
        $orderId,
        null,
        'en_US'
    );
}

/**
 * 5. DAILY SALES REPORT
 * Template: daily_sales_report
 * Parameters: {{1}} Date, {{2}} Orders, {{3}} Total Sales, {{4}} Cost, {{5}} Gross Profit
 */
function send_daily_sales_report_notification($reportDate, $orderCount, $totalSales, $cost, $grossProfit) {
    $formattedDate = date('d M Y', strtotime($reportDate));
    $parameters = [
        (string)$formattedDate,
        (string)$orderCount,
        number_format((float)$totalSales, 2, '.', ''),
        number_format((float)$cost, 2, '.', ''),
        number_format((float)$grossProfit, 2, '.', '')
    ];

    $idempotencyKey = "DAILY_SALES_REPORT_" . date('Y-m-d', strtotime($reportDate)) . "_" . ADMIN_WHATSAPP_NUMBER;

    return send_template_with_fallback(
        'daily_sales_report',
        $parameters,
        ADMIN_WHATSAPP_NUMBER,
        'DAILY_SALES_REPORT',
        null,
        $formattedDate,
        $idempotencyKey,
        'en'
    );
}

/**
 * Backward compatibility wrapper for generic legacy calls
 */
function send_erp_whatsapp_notification($eventType, $docNumber, $details, $recipientPhone = null) {
    $parameters = [
        (string)$eventType,
        (string)$docNumber,
        (string)$details
    ];

    return send_whatsapp_template('erp_alerts', $parameters, $recipientPhone, 'GENERIC_ALERT', null, $docNumber, null, 'en');
}

/**
 * Pre-approved hello_world template fallback for instant connection check
 */
function send_whatsapp_hello_world($recipientPhone = null) {
    $res = send_whatsapp_template('hello_world', [], $recipientPhone, 'TEST_HELLO_WORLD', null, 'HELLO_WORLD', null, 'en_US');
    return $res['success'];
}
