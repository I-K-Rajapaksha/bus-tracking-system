<?php
/**
 * User Management
 * Manage system users and their roles
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Require super admin role
requireRole(ROLE_SUPER_ADMIN);

$db = new Database();
$page_title = 'User Management';

// Fetch all users
$users = $db->resultSet("SELECT * FROM users ORDER BY created_at DESC");

include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-users"></i> User Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus"></i> Add New User
                </button>
            </div>

            <!-- Users Table -->
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> All Users</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No users found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['user_id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo getRoleName($user['user_role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info edit-user" 
                                                        data-user-id="<?php echo $user['user_id']; ?>"
                                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                        data-fullname="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                        data-role="<?php echo $user['user_role']; ?>"
                                                        data-active="<?php echo $user['is_active']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning reset-password" 
                                                        data-user-id="<?php echo $user['user_id']; ?>"
                                                        data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                    <?php if ($user['is_active']): ?>
                                                        <button class="btn btn-sm btn-secondary toggle-status" 
                                                                data-user-id="<?php echo $user['user_id']; ?>"
                                                                data-action="deactivate">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-success toggle-status" 
                                                                data-user-id="<?php echo $user['user_id']; ?>"
                                                                data-action="activate">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-danger delete-user" 
                                                            data-user-id="<?php echo $user['user_id']; ?>"
                                                            data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Current User</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">User Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="user_role" required>
                            <option value="">Select Role</option>
                            <option value="<?php echo ROLE_SUPER_ADMIN; ?>">Super Administrator</option>
                            <option value="<?php echo ROLE_TERMINAL_IN; ?>">Terminal IN Operator</option>
                            <option value="<?php echo ROLE_TERMINAL_OUT; ?>">Terminal OUT Operator</option>
                            <option value="<?php echo ROLE_REPORT_VIEWER; ?>">Report Viewer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" checked>
                            <label class="form-check-label">Active User</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" id="edit_username" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">User Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="user_role" id="edit_user_role" required>
                            <option value="<?php echo ROLE_SUPER_ADMIN; ?>">Super Administrator</option>
                            <option value="<?php echo ROLE_TERMINAL_IN; ?>">Terminal IN Operator</option>
                            <option value="<?php echo ROLE_TERMINAL_OUT; ?>">Terminal OUT Operator</option>
                            <option value="<?php echo ROLE_REPORT_VIEWER; ?>">Report Viewer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                            <label class="form-check-label">Active User</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save"></i> Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-key"></i> Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="resetPasswordForm">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="modal-body">
                    <p>Reset password for: <strong id="reset_username"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add User
    $('#addUserForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=add';
        
        $.post('../../api/users_api.php', formData, function(response) {
            if (response.success) {
                alert('User created successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json').fail(function() {
            alert('Failed to create user. Please try again.');
        });
    });
    
    // Edit User - Load
    $(document).on('click', '.edit-user', function() {
        $('#edit_user_id').val($(this).data('user-id'));
        $('#edit_username').val($(this).data('username'));
        $('#edit_full_name').val($(this).data('fullname'));
        $('#edit_user_role').val($(this).data('role'));
        $('#edit_is_active').prop('checked', $(this).data('active') == 1);
        $('#editUserModal').modal('show');
    });
    
    // Edit User - Submit
    $('#editUserForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=edit';
        
        $.post('../../api/users_api.php', formData, function(response) {
            if (response.success) {
                alert('User updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json').fail(function() {
            alert('Failed to update user. Please try again.');
        });
    });
    
    // Reset Password - Load
    $(document).on('click', '.reset-password', function() {
        $('#reset_user_id').val($(this).data('user-id'));
        $('#reset_username').text($(this).data('username'));
        $('#resetPasswordModal').modal('show');
    });
    
    // Reset Password - Submit
    $('#resetPasswordForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=reset_password';
        
        $.post('../../api/users_api.php', formData, function(response) {
            if (response.success) {
                alert('Password reset successfully!');
                $('#resetPasswordModal').modal('hide');
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json').fail(function() {
            alert('Failed to reset password. Please try again.');
        });
    });
    
    // Toggle Status
    $(document).on('click', '.toggle-status', function() {
        const userId = $(this).data('user-id');
        const action = $(this).data('action');
        
        if (confirm('Are you sure you want to ' + action + ' this user?')) {
            $.post('../../api/users_api.php', {
                action: 'toggle',
                user_id: userId
            }, function(response) {
                if (response.success) {
                    alert('User status updated!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json');
        }
    });
    
    // Delete User
    $(document).on('click', '.delete-user', function() {
        const userId = $(this).data('user-id');
        const username = $(this).data('username');
        
        if (confirm('Are you sure you want to delete user "' + username + '"?\n\nThis action cannot be undone!')) {
            $.post('../../api/users_api.php', {
                action: 'delete',
                user_id: userId
            }, function(response) {
                if (response.success) {
                    alert('User deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json');
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
