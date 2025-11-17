<?php
// Prevent any output before JSON response
ob_start();

// Set headers FIRST before any output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Clean any output buffer
ob_end_clean();

// Start fresh output buffer for JSON only
ob_start();

try {
    // Load the database connection
    require_once __DIR__ . '/../app/init.php';
    
    // Create database instance
    $database = new Database();
    $db = $database->conn;
    
    // Check connection
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Query to get all active users with fingerprints
    $sql = "SELECT id, fingerprint_data 
            FROM users 
            WHERE fingerprint_data IS NOT NULL 
            AND fingerprint_data != '' 
            AND status = 'active'";
    
    $result = $db->query($sql);
    
    if (!$result) {
        throw new Exception("Query failed: " . $db->error);
    }
    
    // Build the templates array
    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $templates[] = [
            'id' => (int)$row['id'],
            'fingerprint_template' => $row['fingerprint_data']
        ];
    }
    
    // Return success response
    $response = [
        'success' => true,
        'message' => 'Templates fetched successfully',
        'data' => $templates,
        'count' => count($templates)
    ];
    
    // Clean output buffer and send JSON
    ob_end_clean();
    echo json_encode($response);
    
} catch (Exception $e) {
    // Clean output buffer
    ob_end_clean();
    
    // Return error response
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => []
    ];
    
    echo json_encode($response);
}

// Ensure script stops here
exit;