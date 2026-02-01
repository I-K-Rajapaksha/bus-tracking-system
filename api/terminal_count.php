<?php
/**
 * API: Get Terminal Count
 * Returns count of buses currently in terminal
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

$db = new Database();
$count = getBusesInTerminalCount($db);

echo json_encode(['success' => true, 'count' => $count]);
?>
