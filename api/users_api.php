<?php
/**
 * Users API
 * Handle user management operations
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Require super admin role
requireRole(ROLE_SUPER_ADMIN);

header('Content-Type: application/json');

$db = new Database();
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            // Add new user
            $username = trim($_POST['username'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $password = $_POST['password'] ?? '';
            $user_role = $_POST['user_role'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Validation
            if (empty($username) || empty($full_name) || empty($password) || empty($user_role)) {
                throw new Exception('All fields are required');
            }
            
            if (strlen($password) < 6) {
                throw new Exception('Password must be at least 6 characters');
            }
            
            // Check if username exists
            $check = $db->single("SELECT user_id FROM users WHERE username = ?", [$username]);
            if ($check) {
                throw new Exception('Username already exists');
            }
            
            // Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert user
            $sql = "INSERT INTO users (username, password_hash, full_name, user_role, is_active) 
                    VALUES (?, ?, ?, ?, ?)";
            $result = $db->query($sql, [$username, $password_hash, $full_name, $user_role, $is_active]);
            
            if ($result) {
                $user_id = $db->lastInsertId();
                logAudit($db, 'USER_CREATED', 'users', $user_id, "Created user: $username");
                echo json_encode(['success' => true, 'message' => 'User created successfully']);
            } else {
                throw new Exception('Failed to create user');
            }
            break;
            
        case 'edit':
            // Edit user
            $user_id = intval($_POST['user_id'] ?? 0);
            $full_name = trim($_POST['full_name'] ?? '');
            $user_role = $_POST['user_role'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Validation
            if ($user_id <= 0 || empty($full_name) || empty($user_role)) {
                throw new Exception('All fields are required');
            }
            
            // Update user
            $sql = "UPDATE users SET full_name = ?, user_role = ?, is_active = ? WHERE user_id = ?";
            $result = $db->query($sql, [$full_name, $user_role, $is_active, $user_id]);
            
            if ($result) {
                logAudit($db, 'USER_UPDATED', 'users', $user_id, "Updated user");
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                throw new Exception('Failed to update user');
            }
            break;
            
        case 'reset_password':
            // Reset password
            $user_id = intval($_POST['user_id'] ?? 0);
            $new_password = $_POST['new_password'] ?? '';
            
            // Validation
            if ($user_id <= 0 || empty($new_password)) {
                throw new Exception('User ID and new password are required');
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception('Password must be at least 6 characters');
            }
            
            // Hash new password
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Update password
            $sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
            $result = $db->query($sql, [$password_hash, $user_id]);
            
            if ($result) {
                logAudit($db, 'PASSWORD_RESET', 'users', $user_id, "Password reset by admin");
                echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
            } else {
                throw new Exception('Failed to reset password');
            }
            break;
            
        case 'toggle':
            // Toggle active status
            $user_id = intval($_POST['user_id'] ?? 0);
            
            if ($user_id <= 0) {
                throw new Exception('Invalid user ID');
            }
            
            // Prevent deactivating yourself
            if ($user_id == $_SESSION['user_id']) {
                throw new Exception('You cannot deactivate your own account');
            }
            
            $sql = "UPDATE users SET is_active = NOT is_active WHERE user_id = ?";
            $result = $db->query($sql, [$user_id]);
            
            if ($result) {
                logAudit($db, 'USER_STATUS_CHANGED', 'users', $user_id, "Toggled user status");
                echo json_encode(['success' => true, 'message' => 'User status updated']);
            } else {
                throw new Exception('Failed to update status');
            }
            break;
            
        case 'delete':
            // Delete user
            $user_id = intval($_POST['user_id'] ?? 0);
            
            if ($user_id <= 0) {
                throw new Exception('Invalid user ID');
            }
            
            // Prevent deleting yourself
            if ($user_id == $_SESSION['user_id']) {
                throw new Exception('You cannot delete your own account');
            }
            
            // Get username for audit
            $user = $db->single("SELECT username FROM users WHERE user_id = ?", [$user_id]);
            
            // Delete user
            $sql = "DELETE FROM users WHERE user_id = ?";
            $result = $db->query($sql, [$user_id]);
            
            if ($result) {
                logAudit($db, 'USER_DELETED', 'users', $user_id, "Deleted user: " . $user['username']);
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                throw new Exception('Failed to delete user');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
