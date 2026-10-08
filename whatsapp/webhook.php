<?php

define('WEBHOOK_VERIFY_TOKEN', 'sanruth_webhook_token_2024');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

    if ($mode === 'subscribe' && $token === WEBHOOK_VERIFY_TOKEN) {
        http_response_code(200);
        header('Content-Type: text/plain');
        echo $challenge;
        exit;
    } else {
        http_response_code(403);
        echo "Verification token mismatch or invalid request.";
        exit;
    }
}

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');

    $logFile = __DIR__ . '/webhook.log';
    $logEntry = date('[Y-m-d H:i:s] ') . $rawInput . PHP_EOL;
    @file_put_contents($logFile, $logEntry, FILE_APPEND);

    $data = json_decode($rawInput, true);

    http_response_code(200);
    echo "EVENT_RECEIVED";
    exit;
}

http_response_code(400);
echo "Invalid Request";
exit;

