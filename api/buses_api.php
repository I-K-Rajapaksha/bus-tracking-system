<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Require login - allow all authenticated users
requireLogin();
// Allow super admin and terminal operators to manage buses
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
            // Add new bus
            $bus_number = strtoupper(trim($_POST['bus_number'] ?? ''));
            $route_id = !empty($_POST['route_id']) ? intval($_POST['route_id']) : null;
            $operator_name = trim($_POST['operator_name'] ?? '');
            $operator_contact = trim($_POST['operator_contact'] ?? '');
            $bus_capacity = !empty($_POST['bus_capacity']) ? intval($_POST['bus_capacity']) : null;
            $registration_number = strtoupper(trim($_POST['registration_number'] ?? ''));
            $is_active = intval($_POST['is_active'] ?? 1);
            
            // Validation
            if (empty($bus_number)) {
                echo json_encode(['success' => false, 'message' => 'License plate number is required']);
                exit;
            }
            
            // Check for duplicate bus number
            $existing = $db->query(
                "SELECT bus_id FROM buses WHERE bus_number = :bus_number",
                ['bus_number' => $bus_number]
            )->fetch();
            
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'Bus with this license plate already exists']);
                exit;
            }
            
            // Validate route if provided
            if ($route_id) {
                $route = $db->query(
                    "SELECT route_id FROM routes WHERE route_id = :route_id",
                    ['route_id' => $route_id]
                )->fetch();
                
                if (!$route) {
                    echo json_encode(['success' => false, 'message' => 'Invalid route selected']);
                    exit;
                }
            }
            
            // Insert bus
            $db->query(
                "INSERT INTO buses (
                    bus_number, 
                    route_id, 
                    operator_name, 
                    operator_contact, 
                    bus_capacity, 
                    registration_number, 
                    is_active, 
                    created_at
                ) VALUES (
                    :bus_number, 
                    :route_id, 
                    :operator_name, 
                    :operator_contact, 
                    :bus_capacity, 
                    :registration_number, 
                    :is_active, 
                    NOW()
                )",
                [
                    'bus_number' => $bus_number,
                    'route_id' => $route_id,
                    'operator_name' => $operator_name ?: null,
                    'operator_contact' => $operator_contact ?: null,
                    'bus_capacity' => $bus_capacity,
                    'registration_number' => $registration_number ?: null,
                    'is_active' => $is_active
                ]
            );
            
            $bus_id = $db->lastInsertId();
            
            // Log audit
            logAudit($db, 'bus_add', 'buses', $bus_id, 
                     "Registered new bus: $bus_number");
            
            echo json_encode(['success' => true, 'message' => 'Bus registered successfully', 'bus_id' => $bus_id]);
            break;
            
        case 'edit':
            // Edit existing bus
            $bus_id = intval($_POST['bus_id'] ?? 0);
            $bus_number = strtoupper(trim($_POST['bus_number'] ?? ''));
            $route_id = !empty($_POST['route_id']) ? intval($_POST['route_id']) : null;
            $operator_name = trim($_POST['operator_name'] ?? '');
            $operator_contact = trim($_POST['operator_contact'] ?? '');
            $bus_capacity = !empty($_POST['bus_capacity']) ? intval($_POST['bus_capacity']) : null;
            $registration_number = strtoupper(trim($_POST['registration_number'] ?? ''));
            $is_active = intval($_POST['is_active'] ?? 1);
            
            // Validation
            if ($bus_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid bus ID']);
                exit;
            }
            
            if (empty($bus_number)) {
                echo json_encode(['success' => false, 'message' => 'License plate number is required']);
                exit;
            }
            
            // Check for duplicate bus number (excluding current bus)
            $existing = $db->query(
                "SELECT bus_id FROM buses WHERE bus_number = :bus_number AND bus_id != :bus_id",
                ['bus_number' => $bus_number, 'bus_id' => $bus_id]
            )->fetch();
            
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'Another bus with this license plate already exists']);
                exit;
            }
            
            // Validate route if provided
            if ($route_id) {
                $route = $db->query(
                    "SELECT route_id FROM routes WHERE route_id = :route_id",
                    ['route_id' => $route_id]
                )->fetch();
                
                if (!$route) {
                    echo json_encode(['success' => false, 'message' => 'Invalid route selected']);
                    exit;
                }
            }
            
            // Update bus
            $db->query(
                "UPDATE buses SET 
                    bus_number = :bus_number,
                    route_id = :route_id,
                    operator_name = :operator_name,
                    operator_contact = :operator_contact,
                    bus_capacity = :bus_capacity,
                    registration_number = :registration_number,
                    is_active = :is_active,
                    updated_at = NOW()
                 WHERE bus_id = :bus_id",
                [
                    'bus_id' => $bus_id,
                    'bus_number' => $bus_number,
                    'route_id' => $route_id,
                    'operator_name' => $operator_name ?: null,
                    'operator_contact' => $operator_contact ?: null,
                    'bus_capacity' => $bus_capacity,
                    'registration_number' => $registration_number ?: null,
                    'is_active' => $is_active
                ]
            );
            
            // Log audit
            logAudit($db, 'bus_edit', 'buses', $bus_id, 
                     "Updated bus: $bus_number");
            
            echo json_encode(['success' => true, 'message' => 'Bus updated successfully']);
            break;
            
        case 'toggle':
            // Toggle bus status
            $bus_id = intval($_POST['bus_id'] ?? 0);
            
            if ($bus_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid bus ID']);
                exit;
            }
            
            // Get current status
            $bus = $db->query(
                "SELECT bus_number, is_active FROM buses WHERE bus_id = :bus_id",
                ['bus_id' => $bus_id]
            )->fetch();
            
            if (!$bus) {
                echo json_encode(['success' => false, 'message' => 'Bus not found']);
                exit;
            }
            
            // Toggle status
            $new_status = $bus['is_active'] ? 0 : 1;
            
            $db->query(
                "UPDATE buses SET is_active = :is_active, updated_at = NOW() WHERE bus_id = :bus_id",
                ['is_active' => $new_status, 'bus_id' => $bus_id]
            );
            
            // Log audit
            $status_text = $new_status ? 'activated' : 'deactivated';
            logAudit($db, 'bus_toggle', 'buses', $bus_id, 
                     "Bus $status_text: {$bus['bus_number']}");
            
            echo json_encode(['success' => true, 'message' => 'Bus status updated']);
            break;
            
        case 'delete':
            // Delete bus
            $bus_id = intval($_POST['bus_id'] ?? 0);
            
            if ($bus_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid bus ID']);
                exit;
            }
            
            // Get bus details
            $bus = $db->query(
                "SELECT bus_number FROM buses WHERE bus_id = :bus_id",
                ['bus_id' => $bus_id]
            )->fetch();
            
            if (!$bus) {
                echo json_encode(['success' => false, 'message' => 'Bus not found']);
                exit;
            }
            
            // Check if bus has arrival records
            $arrivals_count = $db->query(
                "SELECT COUNT(*) as count FROM bus_arrivals WHERE bus_id = :bus_id",
                ['bus_id' => $bus_id]
            )->fetch();
            
            if ($arrivals_count['count'] > 0) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cannot delete bus: Has ' . $arrivals_count['count'] . ' historical arrival records. Consider deactivating instead.'
                ]);
                exit;
            }
            
            // Check if bus has departure records (using bus_number since bus_departures table doesn't have bus_id)
            $departures_count = $db->query(
                "SELECT COUNT(*) as count FROM bus_departures WHERE bus_number = :bus_number",
                ['bus_number' => $bus['bus_number']]
            )->fetch();
            
            if ($departures_count['count'] > 0) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cannot delete bus: Has ' . $departures_count['count'] . ' historical departure records. Consider deactivating instead.'
                ]);
                exit;
            }
            
            // Delete bus
            $db->query("DELETE FROM buses WHERE bus_id = :bus_id", ['bus_id' => $bus_id]);
            
            // Log audit
            logAudit($db, 'bus_delete', 'buses', $bus_id, 
                     "Deleted bus: {$bus['bus_number']}");
            
            echo json_encode(['success' => true, 'message' => 'Bus deleted successfully']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Bus API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
