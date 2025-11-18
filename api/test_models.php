<?php
require_once '../config.php';

// API key is loaded from config.php - never hardcode keys in version control!

$ch = curl_init("https://api.anthropic.com/v1/models");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-api-key: $CLAUDE_API_KEY",
        "anthropic-version: 2023-06-01"
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";
