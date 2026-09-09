<?php
/**
 * Sanruth ERP — Master Admin WhatsApp Notification System Test Suite & Diagnostic Log Viewer
 * Location: whatsapp/test_admin_events.php
 * URL: http://localhost:1000/whatsapp/test_admin_events.php
 */

require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/config.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$actionResult = null;
$metaTemplatesStatus = null;

// Function to fetch WABA ID and list all templates live from Meta Graph API
function fetch_meta_templates_live() {
    $token = META_ACCESS_TOKEN;
    $phoneId = META_PHONE_NUMBER_ID;

    // 1. Get WABA ID
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer {$token}\r\n",
            'timeout' => 15,
            'ignore_errors' => true
        ],
        'ssl' => [ 'verify_peer' => false, 'verify_peer_name' => false ]
    ];
    $context = stream_context_create($opts);

    $urlPhone = "https://graph.facebook.com/" . META_API_VERSION . "/{$phoneId}?fields=whatsapp_business_account";
    $resPhone = @file_get_contents($urlPhone, false, $context);
    
    if (!$resPhone) {
        $cmd = 'curl.exe -k -s -H "Authorization: Bearer ' . addslashes($token) . '" ' . escapeshellarg($urlPhone);
        $resPhone = @shell_exec($cmd);
    }

    $phoneData = json_decode($resPhone, true);
    $wabaId = $phoneData['whatsapp_business_account']['id'] ?? null;

    if (!$wabaId) {
        return ['error' => 'Could not fetch WABA ID from Phone ID: ' . ($resPhone ?: 'No response')];
    }

    // 2. Get Message Templates
    $urlTpl = "https://graph.facebook.com/" . META_API_VERSION . "/{$wabaId}/message_templates?limit=100";
    $resTpl = @file_get_contents($urlTpl, false, $context);
    
    if (!$resTpl) {
        $cmd = 'curl.exe -k -s -H "Authorization: Bearer ' . addslashes($token) . '" ' . escapeshellarg($urlTpl);
        $resTpl = @shell_exec($cmd);
    }

    return json_decode($resTpl, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'fetch_meta_status':
            $metaTemplatesStatus = fetch_meta_templates_live();
            break;

        case 'test_job_card':
            $actionResult = send_job_card_notification(
                "JC-2026-0045",
                "Ramesh Kumar",
                "9876543210",
                "Coimbatore",
                99901
            );
            break;

        case 'test_stock_reorder':
            $actionResult = send_stock_reorder_notification(
                "Clutch Plate 120mm",
                "SP-402",
                2,
                10,
                88801
            );
            break;

        case 'test_sales_order':
            $actionResult = send_sales_order_notification(
                "SO-2026-0125",
                "Sri Garments",
                "9876543210",
                "919876543210",
                12450.00,
                77701
            );
            break;

        case 'test_purchase_order':
            $actionResult = send_purchase_notification(
                "PO-2026-0088",
                "Auto Parts Ltd",
                "1. Brake Pad, 2. Clutch Plate, 3. Oil Filter",
                35000.00,
                66601
            );
            break;

        case 'test_daily_report':
            $actionResult = send_daily_sales_report_notification(
                date('Y-m-d'),
                18,
                124500.00,
                96200.00,
                28300.00
            );
            break;

        case 'test_erp_alerts':
            $actionResult = send_whatsapp_template(
                'erp_alerts',
                ["System Test Event", "DOC-9999", "Testing erp_alerts custom template delivery"],
                ADMIN_WHATSAPP_NUMBER,
                'ERP_ALERTS_TEST',
                99999,
                'DOC-9999',
                null,
                'en'
            );
            break;

        case 'test_duplicate_suppression':
            $r1 = send_job_card_notification("JC-DUP-TEST", "Duplicate Test Customer", "9876543210", "Coimbatore", 99999);
            $r2 = send_job_card_notification("JC-DUP-TEST", "Duplicate Test Customer", "9876543210", "Coimbatore", 99999);
            $actionResult = [
                'success' => true,
                'status'  => 'tested_duplicate_suppression',
                'first_call' => $r1,
                'second_call' => $r2
            ];
            break;

        case 'test_hello_world':
            $hw = send_whatsapp_hello_world();
            $actionResult = [
                'success' => $hw,
                'status' => $hw ? 'sent' : 'failed',
                'message' => 'Instant connection test with pre-approved hello_world template'
            ];
            break;

        case 'clear_logs':
            mysqli_query($conn, "TRUNCATE TABLE whatsapp_notification_log");
            $actionResult = ['success' => true, 'status' => 'logs_cleared', 'message' => 'Notification log table cleared.'];
            break;
    }
}

// Fetch recent logs
$logsRes = mysqli_query($conn, "SELECT * FROM whatsapp_notification_log ORDER BY id DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sanruth ERP - Admin WhatsApp Test Suite</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 24px; }
        h1 { font-size: 24px; margin-top: 0; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-bottom: 24px; }
        button { background: #2563eb; color: #fff; border: none; padding: 12px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; text-align: left; width: 100%; box-sizing: border-box; }
        button:hover { background: #1d4ed8; }
        button.btn-green { background: #059669; } button.btn-green:hover { background: #047857; }
        button.btn-amber { background: #d97706; } button.btn-amber:hover { background: #b45309; }
        button.btn-purple { background: #7c3aed; } button.btn-purple:hover { background: #6d28d9; }
        button.btn-red { background: #dc2626; } button.btn-red:hover { background: #b91c1c; }
        .result-box { background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
        pre { font-family: monospace; white-space: pre-wrap; word-break: break-all; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 13px; }
        th, td { padding: 10px 12px; border: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; font-weight: 600; color: #475569; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-SENT, .badge-APPROVED { background: #dcfce7; color: #15803d; }
        .badge-FAILED, .badge-REJECTED { background: #fee2e2; color: #b91c1c; }
        .badge-SKIPPED, .badge-IN_REVIEW, .badge-PENDING { background: #fef3c7; color: #b45309; }
    </style>
</head>
<body>

<div class="container">
    <h1>📲 Sanruth ERP — Admin WhatsApp Notification Test Suite</h1>
    
    <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div>
            <strong>Target Admin Number:</strong> <code><?= htmlspecialchars(ADMIN_WHATSAPP_NUMBER) ?></code> | 
            <strong>Phone ID:</strong> <code><?= htmlspecialchars(META_PHONE_NUMBER_ID) ?></code>
        </div>
        <form method="POST" style="margin:0;">
            <button type="submit" name="action" value="fetch_meta_status" class="btn-purple" style="padding: 6px 14px; font-size: 13px; width:auto;">
                🔍 Fetch Live Meta Template Approval Status
            </button>
        </form>
    </div>

    <?php if ($metaTemplatesStatus !== null): ?>
        <div class="result-box" style="background:#f3e8ff; border-color:#d8b4fe;">
            <h3 style="margin-top:0; color:#6b21a8;">🔍 Live Meta Account Templates Approval Status:</h3>
            <?php if (!empty($metaTemplatesStatus['data']) && is_array($metaTemplatesStatus['data'])): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Template Name</th>
                            <th>Category</th>
                            <th>Language</th>
                            <th>Approval Status</th>
                            <th>ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($metaTemplatesStatus['data'] as $tpl): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($tpl['name']) ?></strong></td>
                                <td><?= htmlspecialchars($tpl['category']) ?></td>
                                <td><code><?= htmlspecialchars($tpl['language']) ?></code></td>
                                <td>
                                    <span class="badge badge-<?= htmlspecialchars($tpl['status']) ?>">
                                        <?= htmlspecialchars($tpl['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($tpl['id']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <pre><?= htmlspecialchars(json_encode($metaTemplatesStatus, JSON_PRETTY_PRINT)) ?></pre>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($actionResult !== null): ?>
        <div class="result-box">
            <h3 style="margin-top:0;">Execution Result:</h3>
            <pre><?= htmlspecialchars(json_encode($actionResult, JSON_PRETTY_PRINT)) ?></pre>
        </div>
    <?php endif; ?>

    <h3>Trigger Admin Event Tests:</h3>
    <form method="POST" class="grid">
        <button type="submit" name="action" value="test_hello_world" class="btn-green">
            ⚡ 1. hello_world<br><small style="font-weight:normal;">Pre-approved Meta Test Template</small>
        </button>

        <button type="submit" name="action" value="test_job_card">
            📋 2. job_card_created<br><small style="font-weight:normal;">Job Card Created Event</small>
        </button>

        <button type="submit" name="action" value="test_stock_reorder">
            ⚠️ 3. stock_reorder_alert<br><small style="font-weight:normal;">Low Stock Reminder Event</small>
        </button>

        <button type="submit" name="action" value="test_sales_order">
            🛒 4. sales_order_created<br><small style="font-weight:normal;">Sales Invoice Created Event</small>
        </button>

        <button type="submit" name="action" value="test_purchase_order">
            📦 5. purchase_order_created<br><small style="font-weight:normal;">Purchase Order Created Event</small>
        </button>

        <button type="submit" name="action" value="test_daily_report">
            📊 6. daily_sales_report<br><small style="font-weight:normal;">Daily Sales Report Event</small>
        </button>

        <button type="submit" name="action" value="test_erp_alerts" class="btn-purple">
            🔔 7. erp_alerts<br><small style="font-weight:normal;">Generic ERP System Alert</small>
        </button>

        <button type="submit" name="action" value="test_duplicate_suppression" class="btn-amber">
            🛡️ Test Duplicate Suppression<br><small style="font-weight:normal;">Sends 2 identical triggers</small>
        </button>

        <button type="submit" name="action" value="clear_logs" class="btn-red" onclick="return confirm('Clear all notification log entries?');">
            🗑️ Clear Notification Logs
        </button>
    </form>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:30px;">
        <h3 style="margin:0;">📋 Live Notification Database Log (`whatsapp_notification_log`)</h3>
        <a href="?refresh=1" style="color:#2563eb; text-decoration:none; font-weight:600;">🔄 Refresh Logs</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Event Code</th>
                <th>Doc #</th>
                <th>Recipient</th>
                <th>Template</th>
                <th>Status</th>
                <th>Message ID / Error</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($logsRes && mysqli_num_rows($logsRes) > 0): ?>
                <?php while ($log = mysqli_fetch_assoc($logsRes)): ?>
                    <tr>
                        <td><?= (int)$log['id'] ?></td>
                        <td><strong><?= htmlspecialchars($log['event_code']) ?></strong></td>
                        <td><?= htmlspecialchars($log['document_number'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($log['recipient_phone']) ?></td>
                        <td><code><?= htmlspecialchars($log['template_name']) ?></code></td>
                        <td>
                            <span class="badge badge-<?= htmlspecialchars($log['status']) ?>">
                                <?= htmlspecialchars($log['status']) ?>
                            </span>
                        </td>
                        <td style="max-width: 320px; font-family: monospace; font-size: 11px;">
                            <?php if (!empty($log['whatsapp_message_id'])): ?>
                                <span style="color:#15803d; font-weight:bold;"><?= htmlspecialchars($log['whatsapp_message_id']) ?></span>
                            <?php else: ?>
                                <span style="color:#b91c1c;"><?= htmlspecialchars($log['error_message'] ?? 'Failed') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($log['created_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align:center; color:#64748b; padding:20px;">No notification logs recorded yet. Click one of the test buttons above to generate logs.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
