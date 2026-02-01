<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Require login - allow all authenticated users
requireLogin();
// Allow super admin and terminal operators to manage routes
if (!hasRole(ROLE_SUPER_ADMIN) && !hasRole(ROLE_TERMINAL_IN) && !hasRole(ROLE_TERMINAL_OUT)) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

$db = new Database();
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            // Add new route
            $route_name = trim($_POST['route_name'] ?? '');
            $origin = trim($_POST['origin'] ?? '');
            $destination = trim($_POST['destination'] ?? '');
            $distance_km = floatval($_POST['distance_km'] ?? 0);
            $estimated_duration_minutes = intval($_POST['estimated_duration_minutes'] ?? 0);
            $is_active = intval($_POST['is_active'] ?? 1);
            
            // Validation
            if (empty($route_name) || empty($origin) || empty($destination)) {
                echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
                exit;
            }
            
            if ($distance_km <= 0) {
                echo json_encode(['success' => false, 'message' => 'Distance must be greater than 0']);
                exit;
            }
            
            if ($estimated_duration_minutes <= 0) {
                echo json_encode(['success' => false, 'message' => 'Duration must be greater than 0']);
                exit;
            }
            
            // Check for duplicate route name
            $existing = $db->query(
                "SELECT route_id FROM routes WHERE route_name = :route_name",
                ['route_name' => $route_name]
            )->fetch();
            
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'Route name already exists']);
                exit;
            }
            
            // Insert route
            $db->query(
                "INSERT INTO routes (route_name, origin, destination, distance_km, estimated_duration_minutes, is_active, created_at) 
                 VALUES (:route_name, :origin, :destination, :distance_km, :estimated_duration_minutes, :is_active, NOW())",
                [
                    'route_name' => $route_name,
                    'origin' => $origin,
                    'destination' => $destination,
                    'distance_km' => $distance_km,
                    'estimated_duration_minutes' => $estimated_duration_minutes,
                    'is_active' => $is_active
                ]
            );
            
            $route_id = $db->lastInsertId();
            
            // Log audit
            logAudit($db, 'route_add', 'routes', $route_id, 
                     "Added new route: $route_name");
            
            echo json_encode(['success' => true, 'message' => 'Route added successfully', 'route_id' => $route_id]);
            break;
            
        case 'edit':
            // Edit existing route
            $route_id = intval($_POST['route_id'] ?? 0);
            $route_name = trim($_POST['route_name'] ?? '');
            $origin = trim($_POST['origin'] ?? '');
            $destination = trim($_POST['destination'] ?? '');
            $distance_km = floatval($_POST['distance_km'] ?? 0);
            $estimated_duration_minutes = intval($_POST['estimated_duration_minutes'] ?? 0);
            $is_active = intval($_POST['is_active'] ?? 1);
            
            // Validation
            if ($route_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid route ID']);
                exit;
            }
            
            if (empty($route_name) || empty($origin) || empty($destination)) {
                echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
                exit;
            }
            
            if ($distance_km <= 0) {
                echo json_encode(['success' => false, 'message' => 'Distance must be greater than 0']);
                exit;
            }
            
            if ($estimated_duration_minutes <= 0) {
                echo json_encode(['success' => false, 'message' => 'Duration must be greater than 0']);
                exit;
            }
            
            // Check for duplicate route name (excluding current route)
            $existing = $db->query(
                "SELECT route_id FROM routes WHERE route_name = :route_name AND route_id != :route_id",
                ['route_name' => $route_name, 'route_id' => $route_id]
            )->fetch();
            
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'Route name already exists']);
                exit;
            }
            
            // Update route
            $db->query(
                "UPDATE routes SET 
                    route_name = :route_name,
                    origin = :origin,
                    destination = :destination,
                    distance_km = :distance_km,
                    estimated_duration_minutes = :estimated_duration_minutes,
                    is_active = :is_active,
                    updated_at = NOW()
                 WHERE route_id = :route_id",
                [
                    'route_id' => $route_id,
                    'route_name' => $route_name,
                    'origin' => $origin,
                    'destination' => $destination,
                    'distance_km' => $distance_km,
                    'estimated_duration_minutes' => $estimated_duration_minutes,
                    'is_active' => $is_active
                ]
            );
            
            // Log audit
            logAudit($db, 'route_edit', 'routes', $route_id, 
                     "Updated route: $route_name");
            
            echo json_encode(['success' => true, 'message' => 'Route updated successfully']);
            break;
            
        case 'toggle':
            // Toggle route status
            $route_id = intval($_POST['route_id'] ?? 0);
            
            if ($route_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid route ID']);
                exit;
            }
            
            // Get current status
            $route = $db->query(
                "SELECT route_name, is_active FROM routes WHERE route_id = :route_id",
                ['route_id' => $route_id]
            )->fetch();
            
            if (!$route) {
                echo json_encode(['success' => false, 'message' => 'Route not found']);
                exit;
            }
            
            // Toggle status
            $new_status = $route['is_active'] ? 0 : 1;
            
            $db->query(
                "UPDATE routes SET is_active = :is_active, updated_at = NOW() WHERE route_id = :route_id",
                ['is_active' => $new_status, 'route_id' => $route_id]
            );
            
            // Log audit
            $status_text = $new_status ? 'activated' : 'deactivated';
            logAudit($db, 'route_toggle', 'routes', $route_id, 
                     "Route $status_text: {$route['route_name']}");
            
            echo json_encode(['success' => true, 'message' => 'Route status updated']);
            break;
            
        case 'delete':
            // Delete route
            $route_id = intval($_POST['route_id'] ?? 0);
            
            if ($route_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid route ID']);
                exit;
            }
            
            // Get route details
            $route = $db->query(
                "SELECT route_name FROM routes WHERE route_id = :route_id",
                ['route_id' => $route_id]
            )->fetch();
            
            if (!$route) {
                echo json_encode(['success' => false, 'message' => 'Route not found']);
                exit;
            }
            
            // Check if route is in use by buses
            $buses_count = $db->query(
                "SELECT COUNT(*) as count FROM buses WHERE route_id = :route_id",
                ['route_id' => $route_id]
            )->fetch();
            
            if ($buses_count['count'] > 0) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cannot delete route: ' . $buses_count['count'] . ' bus(es) are assigned to this route'
                ]);
                exit;
            }
            
            // Check if route has arrival/departure records
            $arrivals_count = $db->query(
                "SELECT COUNT(*) as count FROM bus_arrivals WHERE route_id = :route_id",
                ['route_id' => $route_id]
            )->fetch();
            
            if ($arrivals_count['count'] > 0) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cannot delete route: Has historical arrival records. Consider deactivating instead.'
                ]);
                exit;
            }
            
            // Delete route
            $db->query("DELETE FROM routes WHERE route_id = :route_id", ['route_id' => $route_id]);
            
            // Log audit
            logAudit($db, 'route_delete', 'routes', $route_id, 
                     "Deleted route: {$route['route_name']}");
            
            echo json_encode(['success' => true, 'message' => 'Route deleted successfully']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Route API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
