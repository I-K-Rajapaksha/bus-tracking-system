<?php
/**
 * User Profile Page
 * View and edit personal information
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

$db = new Database();
$page_title = 'My Profile';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validate
    if (empty($full_name)) {
        $error_message = 'Full name is required.';
    } else {
        try {
            $sql = "UPDATE users 
                    SET full_name = :full_name,
                        email = :email,
                        phone = :phone,
                        updated_at = NOW()
                    WHERE user_id = :user_id";
            
            $db->execute($sql, [
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'user_id' => $user_id
            ]);
            
            // Update session
            $_SESSION['full_name'] = $full_name;
            
            // Log action
            logActivity($db, 'PROFILE_UPDATED', 'users', $user_id, 'Updated profile information');
            
            $success_message = 'Profile updated successfully!';
        } catch (Exception $e) {
            $error_message = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

// Get user data
$sql = "SELECT * FROM users WHERE user_id = :user_id";
$user = $db->single($sql, ['user_id' => $user_id]);

if (!$user) {
    redirect('logout.php');
}

// Role names mapping
$role_names = [
    'super_admin' => 'Super Administrator',
    'terminal_in_operator' => 'Terminal IN Operator',
    'terminal_out_operator' => 'Terminal OUT Operator',
    'report_viewer' => 'Report Viewer'
];

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-user-circle"></i> My Profile</h2>
                <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
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

            <!-- Profile Information Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-id-card"></i> Account Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        
                        <!-- Username (Read-only) -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user"></i> Username</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($user['username']); ?>" readonly>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>

                        <!-- Full Name -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-signature"></i> Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo sanitize($user['full_name']); ?>" required>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo sanitize($user['email']); ?>">
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-phone"></i> Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo sanitize($user['phone']); ?>">
                        </div>

                        <!-- Role (Read-only) -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-shield-alt"></i> Role</label>
                            <input type="text" class="form-control" value="<?php echo $role_names[$user['user_role']] ?? $user['user_role']; ?>" readonly>
                            <small class="text-muted">Contact administrator to change role</small>
                        </div>

                        <!-- Account Status (Read-only) -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-check-circle"></i> Account Status</label>
                            <input type="text" class="form-control" value="<?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>" readonly>
                        </div>

                        <!-- Created Date (Read-only) -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-calendar-plus"></i> Account Created</label>
                            <input type="text" class="form-control" value="<?php echo date('F j, Y g:i A', strtotime($user['created_at'])); ?>" readonly>
                        </div>

                        <!-- Last Login (Read-only) -->
                        <?php if ($user['last_login']): ?>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-clock"></i> Last Login</label>
                            <input type="text" class="form-control" value="<?php echo date('F j, Y g:i A', strtotime($user['last_login'])); ?>" readonly>
                        </div>
                        <?php endif; ?>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Additional Actions -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Account Settings</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo SITE_URL; ?>/change_password.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
