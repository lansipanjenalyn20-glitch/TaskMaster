<?php
// Turn off error display so HTML notices don't break JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    // Adjust relative path depending on where api folder is located (root/api/get_tasks.php)
    require_once __DIR__ . 'conn.php';

    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'all';
    $priority = $_GET['priority'] ?? 'all';
    $tag = $_GET['tag'] ?? 'all';

    // Ensure functions exist
    if (function_exists('getTasks') && isset($pdo)) {
        $tasks = getTasks($pdo, $search, $status, $priority, $tag);
        echo json_encode([
            'status' => 'success',
            'tasks' => $tasks
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection or getTasks function not found.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit;