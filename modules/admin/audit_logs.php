<?php
/**
 * Audit Logs
 * View system activity logs
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Require super admin role
requireRole(ROLE_SUPER_ADMIN);

$db = new Database();
$page_title = 'Audit Logs';

// Get filters
$filter_user = $_GET['user_id'] ?? '';
$filter_action = $_GET['action_type'] ?? '';
$filter_date = $_GET['date'] ?? date('Y-m-d');

// Build query
$sql = "SELECT al.*, u.username, u.full_name 
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        WHERE DATE(al.action_datetime) = ?";
$params = [$filter_date];

if ($filter_user) {
    $sql .= " AND al.user_id = ?";
    $params[] = $filter_user;
}

if ($filter_action) {
    $sql .= " AND al.action_type = ?";
    $params[] = $filter_action;
}

$sql .= " ORDER BY al.action_datetime DESC LIMIT 500";

$logs = $db->resultSet($sql, $params);

// Get all users for filter
$users = $db->resultSet("SELECT user_id, username, full_name FROM users ORDER BY username");

// Get distinct action types
$action_types = $db->resultSet("SELECT DISTINCT action_type FROM audit_logs ORDER BY action_type");

include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-clipboard-list"></i> Audit Logs</h2>

            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="date" value="<?php echo $filter_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">User</label>
                            <select class="form-select" name="user_id">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['user_id']; ?>" 
                                            <?php echo $filter_user == $user['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['username']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Action Type</label>
                            <select class="form-select" name="action_type">
                                <option value="">All Actions</option>
                                <?php foreach ($action_types as $type): ?>
                                    <option value="<?php echo $type['action_type']; ?>" 
                                            <?php echo $filter_action == $type['action_type'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['action_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="audit_logs.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Activity Log 
                        (<?php echo count($logs); ?> records on <?php echo date('M d, Y', strtotime($filter_date)); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Record ID</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No logs found for selected filters</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo date('H:i:s', strtotime($log['action_datetime'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['username'] ?? '-'); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = 'bg-secondary';
                                                if (strpos($log['action_type'], 'LOGIN') !== false) $badge_class = 'bg-success';
                                                if (strpos($log['action_type'], 'DELETE') !== false) $badge_class = 'bg-danger';
                                                if (strpos($log['action_type'], 'UPDATE') !== false) $badge_class = 'bg-info';
                                                if (strpos($log['action_type'], 'CREATE') !== false) $badge_class = 'bg-primary';
                                                if (strpos($log['action_type'], 'FAILED') !== false) $badge_class = 'bg-warning';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo htmlspecialchars($log['action_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($log['record_id'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($log['action_description']); ?></td>
                                            <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
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

<?php include '../../includes/footer.php'; ?>
