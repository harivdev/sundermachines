<?php
/**
 * Sanruth ERP — Quick Meta WhatsApp API Connection Test
 * Location: whatsapp/test_api.php
 */

require_once(__DIR__ . "/config.php");

echo "<h2>Meta WhatsApp Business Cloud API Integration Test</h2>";

if (META_PHONE_NUMBER_ID === 'YOUR_PHONE_NUMBER_ID' || META_ACCESS_TOKEN === 'YOUR_META_PERMANENT_OR_SYSTEM_USER_ACCESS_TOKEN') {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Please configure your Meta credentials in <code>whatsapp/config.php</code> first.</p>";
    exit;
}

$event = $_GET['event'] ?? 'JOB_CARD_CREATED';
$docNo = $_GET['doc'] ?? 'JC-TEST-001';
$details = $_GET['details'] ?? 'Customer Name: Ramesh | Mobile: 9876543210 | City: Erode';

if (isset($_GET['hello']) && $_GET['hello'] == '1') {
    echo "<p>Attempting INSTANT test sending pre-approved <strong>hello_world</strong> template to: <strong>" . htmlspecialchars(ADMIN_WHATSAPP_NUMBER) . "</strong>...</p>";
    $res = send_whatsapp_hello_world();
    if ($res) {
        echo "<p style='color: green; font-weight: bold; font-size: 16px;'>🎉 SUCCESS! Meta WhatsApp Message delivered to your phone (+91 7418735076)!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold; font-size: 16px;'>❌ FAILED! Check error log or verify your Phone ID & Access Token.</p>";
    }
} else {
    echo "<p>Attempting to send custom template <strong>erp_alerts</strong> to: <strong>" . htmlspecialchars(ADMIN_WHATSAPP_NUMBER) . "</strong>...</p>";
    $res = send_erp_whatsapp_notification($event, $docNo, $details);
    if ($res) {
        echo "<p style='color: green; font-weight: bold; font-size: 16px;'>🎉 SUCCESS! Custom ERP Alert sent to WhatsApp successfully!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold; font-size: 16px;'>❌ FAILED to send custom template. Ensure template <code>erp_alerts</code> is created and APPROVED in Meta WhatsApp Manager.</p>";
    }
}
