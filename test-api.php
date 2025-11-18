<?php
require_once 'config.php';

echo "<h1>API Test - Company Endpoint</h1>\n";

// Test GET request
echo "<h2>Testing GET Request</h2>\n";

try {
    // Simulate the same request the JavaScript makes
    ob_start();
    include 'api/company.php';
    $api_output = ob_get_clean();
    
    echo "<h3>Raw API Response:</h3>\n";
    echo "<pre>" . htmlspecialchars($api_output) . "</pre>\n";
    
    $decoded = json_decode($api_output, true);
    if ($decoded) {
        echo "<h3>Decoded JSON:</h3>\n";
        echo "<pre>" . print_r($decoded, true) . "</pre>\n";
    } else {
        echo "<h3>JSON Decode Error:</h3>\n";
        echo "<p>Could not decode JSON response</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}

// Test direct database query
echo "<h2>Testing Direct Database Query</h2>\n";

try {
    $stmt = $pdo->query("SELECT id, company_name, location, company_type, created_at FROM companies ORDER BY created_at DESC");
    $companies = $stmt->fetchAll();
    
    echo "<h3>Companies in Database:</h3>\n";
    if (empty($companies)) {
        echo "<p>No companies found in database</p>\n";
        
        // Let's create a test company
        echo "<h3>Creating Test Company:</h3>\n";
        $stmt = $pdo->prepare("INSERT INTO companies (company_name, location, hours, company_type) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Test HVAC Company', 'Test City, TX', 'Mon-Fri: 8AM-6PM', 'HVAC']);
        echo "<p>✅ Test company created with ID: " . $pdo->lastInsertId() . "</p>\n";
        
        // Query again
        $stmt = $pdo->query("SELECT id, company_name, location, company_type, created_at FROM companies ORDER BY created_at DESC");
        $companies = $stmt->fetchAll();
    }
    
    foreach ($companies as $company) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px;'>";
        echo "<strong>ID:</strong> " . $company['id'] . "<br>";
        echo "<strong>Name:</strong> " . htmlspecialchars($company['company_name']) . "<br>";
        echo "<strong>Location:</strong> " . htmlspecialchars($company['location']) . "<br>";
        echo "<strong>Type:</strong> " . htmlspecialchars($company['company_type']) . "<br>";
        echo "<strong>Created:</strong> " . $company['created_at'] . "<br>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>\n";
}

echo "<br><p><a href='dashboard.php'>← Back to Dashboard</a></p>";
?>