<?php

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/../includes/auth.php');
requireAdmin();

$result = null;
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$phone = trim($_POST['phone'] ?? $_GET['phone'] ?? ADMIN_WHATSAPP_NUMBER);

if ($action === 'test_hello_world') {
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) === 10) {
        $cleanPhone = '91' . $cleanPhone;
    }

    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => $cleanPhone,
        'type'              => 'template',
        'template'          => [
            'name'     => 'hello_world',
            'language' => ['code' => 'en_US']
        ]
    ];

    $url = "https://graph.facebook.com/" . META_API_VERSION . "/" . META_PHONE_NUMBER_ID . "/messages";
    list($httpCode, $rawBody) = meta_whatsapp_http_post($url, $payload, META_ACCESS_TOKEN);
    $response = json_decode($rawBody, true);

    $result = [
        'type'       => 'hello_world',
        'target'     => $cleanPhone,
        'httpCode'   => $httpCode,
        'raw'        => $rawBody,
        'response'   => $response,
        'isSuccess'  => ($httpCode >= 200 && $httpCode < 300 && !empty($response['messages'][0]['id'])),
        'message_id' => $response['messages'][0]['id'] ?? null,
        'error_msg'  => $response['error']['message'] ?? null
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WhatsApp Cloud API Test Console</title>

    <link rel="icon" type="image/png" href="../img/logo.png">
    <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico">
    <link rel="apple-touch-icon" href="../img/logo.png">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f2f5; margin: 0; padding: 30px; }
        .card { max-width: 650px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 28px; }
        h1 { font-size: 20px; color: #111; margin-top: 0; display: flex; align-items: center; gap: 8px; }
        .badge { background: #25D366; color: #fff; font-size: 12px; padding: 3px 8px; border-radius: 6px; font-weight: bold; }
        .config-table { width: 100%; border-collapse: collapse; margin: 15px 0 20px 0; font-size: 13px; }
        .config-table td { padding: 8px 10px; border-bottom: 1px solid #eee; }
        .config-table td:first-child { font-weight: 600; color: #555; width: 35%; }
        .config-table code { background: #eef1f6; padding: 2px 6px; border-radius: 4px; font-size: 12px; word-break: break-all; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: #333; }
        input[type="text"] { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
        .btn { background: #25D366; color: #fff; border: none; padding: 12px 20px; font-size: 14px; font-weight: bold; border-radius: 6px; cursor: pointer; width: 100%; transition: background 0.2s; }
        .btn:hover { background: #1eb956; }
        .alert { padding: 14px 16px; border-radius: 8px; margin-top: 20px; font-size: 13px; line-height: 1.5; }
        .alert-success { background: #e8f7ee; color: #0d6832; border: 1px solid #b7ebd0; }
        .alert-error { background: #fdebee; color: #9c1c2e; border: 1px solid #f9c7ce; }
        pre { background: #282c34; color: #abb2bf; padding: 12px; border-radius: 6px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>
    <div class="card">
        <h1><span>WhatsApp Cloud API Test Console</span> <span class="badge">Meta Graph v20.0</span></h1>

        <p style="font-size: 13px; color: #666;">Current configuration loaded from <code>whatsapp/config.php</code>:</p>

        <table class="config-table">
            <tr>
                <td>Phone Number ID:</td>
                <td><code><?= htmlspecialchars(META_PHONE_NUMBER_ID) ?></code></td>
            </tr>
            <tr>
                <td>Access Token:</td>
                <td><code><?= htmlspecialchars(substr(META_ACCESS_TOKEN, 0, 15) . '...' . substr(META_ACCESS_TOKEN, -10)) ?></code></td>
            </tr>
            <tr>
                <td>Admin Test Number:</td>
                <td><code><?= htmlspecialchars(ADMIN_WHATSAPP_NUMBER) ?></code></td>
            </tr>
        </table>

        <form method="POST">
            <input type="hidden" name="action" value="test_hello_world">
            <div class="form-group">
                <label for="phone">Send Test Message To (Recipient Number):</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="e.g. 917418735076" required>
                <small style="color:#777; display:block; margin-top:4px;">Must be registered under the Meta "To" whitelist in Development Mode.</small>
            </div>
            <button type="submit" class="btn">🚀 Send "hello_world" Test Template</button>
        </form>

        <?php if ($result !== null): ?>
            <?php if ($result['isSuccess']): ?>
                <div class="alert alert-success">
                    <strong>✅ Test Message Sent Successfully!</strong><br>
                    WhatsApp Message ID (wamid): <code><?= htmlspecialchars($result['message_id']) ?></code><br>
                    HTTP Status: <?= $result['httpCode'] ?><br>
                    Check WhatsApp on <strong>+<?= htmlspecialchars($result['target']) ?></strong> to see the message!
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    <strong>❌ Delivery Failed (HTTP <?= $result['httpCode'] ?>)</strong><br>
                    <?= htmlspecialchars($result['error_msg'] ?? 'Unknown error') ?>
                </div>
            <?php endif; ?>
            <h4 style="margin-top:20px; font-size:13px;">Meta Raw API Response:</h4>
            <pre><?= htmlspecialchars($result['raw']) ?></pre>
        <?php endif; ?>
    </div>
</body>
</html>

