<?php
// Database Configuration
$DB_HOST = 'localhost';
$DB_NAME = 'hvac_keywords';
$DB_USER = 'your_username';
$DB_PASS = 'your_password';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/* --------------------------
   CLAUDE API CONFIG
--------------------------- */

// Your Claude API key from Anthropic
$CLAUDE_API_KEY = "your_claude_api_key_here";

// Required by Anthropic (Claude)
$CLAUDE_API_VERSION = "2023-06-01";
?>