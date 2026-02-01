<?php
/**
 * Change Password Page
 * Allows users to change their own password
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

$db = new Database();
$page_title = 'Change Password';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'All fields are required.';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'New password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New password and confirmation do not match.';
    } else {
        // Get current user
        $sql = "SELECT password_hash FROM users WHERE user_id = :user_id";
        $user = $db->single($sql, ['user_id' => $user_id]);
        
        // Verify current password
        if (!password_verify($current_password, $user['password_hash'])) {
            $error_message = 'Current password is incorrect.';
        } else {
            // Update password
            try {
                $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $update_sql = "UPDATE users 
                              SET password_hash = :password_hash,
                                  updated_at = NOW()
                              WHERE user_id = :user_id";
                
                $db->execute($update_sql, [
                    'password_hash' => $new_hash,
                    'user_id' => $user_id
                ]);
                
                // Log action
                logActivity($db, 'PASSWORD_CHANGED', 'users', $user_id, 'User changed their own password');
                
                $success_message = 'Password changed successfully! Please remember your new password.';
            } catch (Exception $e) {
                $error_message = 'Error changing password: ' . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-key"></i> Change Password</h2>
                <a href="<?php echo SITE_URL; ?>/profile.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Change Password Form -->
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-lock"></i> Update Your Password</h5>
                </div>
                <div class="card-body">
                    
                    <!-- Security Notice -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Password Requirements:</h6>
                        <ul class="mb-0">
                            <li>Minimum 6 characters</li>
                            <li>Use a strong, unique password</li>
                            <li>Don't share your password with anyone</li>
                        </ul>
                    </div>

                    <form method="POST" action="" id="changePasswordForm">
                        
                        <!-- Current Password -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-lock"></i> Current Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="current_password" id="current_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                        </div>

                        <hr>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-key"></i> New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="new_password" id="new_password" minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye" id="new_password_icon"></i>
                                </button>
                            </div>
                            <small class="text-muted">Must be at least 6 characters</small>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-check-double"></i> Confirm New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm_password_icon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="fas fa-save"></i> Change Password
                            </button>
                            <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Security Tips -->
            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <h6><i class="fas fa-shield-alt"></i> Security Tips:</h6>
                    <ul class="small mb-0">
                        <li>Change your password regularly</li>
                        <li>Don't use the same password for multiple accounts</li>
                        <li>Use a combination of letters, numbers, and symbols</li>
                        <li>Never share your password via email or phone</li>
                        <li>Log out when using shared computers</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Validate passwords match before submit
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New password and confirmation do not match!');
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
});
</script>

<?php include '../includes/footer.php'; ?>
