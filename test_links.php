<?php
/**
 * Link Verification Page
 * Test all navigation links and pages
 * DELETE THIS FILE AFTER TESTING
 */

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Require login and admin
requireLogin();
requireRole(ROLE_SUPER_ADMIN);

$db = new Database();

// Define all pages to test
$pages = [
    'Core Pages' => [
        'Login Page' => 'login.php',
        'Dashboard' => 'dashboard.php',
        'Logout' => 'logout.php',
        'Profile' => 'profile.php',
        'Change Password' => 'change_password.php',
    ],
    'Terminal Operations' => [
        'Terminal IN' => 'modules/terminal_in/index.php',
        'Terminal OUT' => 'modules/terminal_out/index.php',
    ],
    'Reports' => [
        'Hourly Report' => 'modules/reports/hourly.php',
        'Daily Report' => 'modules/reports/daily.php',
        'Weekly Report' => 'modules/reports/weekly.php',
        'Monthly Report' => 'modules/reports/monthly.php',
        'Yearly Report' => 'modules/reports/yearly.php',
    ],
    'Administration' => [
        'User Management' => 'modules/admin/users.php',
        'Route Management' => 'modules/master_data/routes.php',
        'Bus Registration' => 'modules/master_data/buses.php',
        'Audit Logs' => 'modules/admin/audit_logs.php',
    ],
    'API Endpoints' => [
        'Terminal Count API' => 'api/terminal_count.php',
        'Routes API' => 'api/routes_api.php',
        'Buses API' => 'api/buses_api.php',
        'Users API' => 'api/users_api.php',
    ]
];

$page_title = 'Link Verification Test';
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            
            <!-- Warning Banner -->
            <div class="alert alert-danger">
                <h4><i class="fas fa-exclamation-triangle"></i> TESTING PAGE - DELETE AFTER USE</h4>
                <p class="mb-0">This page is for testing purposes only. Delete <code>test_links.php</code> after verifying all links work.</p>
            </div>

            <!-- Page Header -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-link"></i> System Link Verification</h3>
                </div>
                <div class="card-body">
                    <p class="lead">Click each link below to verify it works correctly.</p>
                    <p class="mb-0">
                        <strong>Expected Result:</strong> Each link should open without errors. 
                        <span class="badge bg-success">Working</span> means page loads correctly.
                    </p>
                </div>
            </div>

            <?php foreach ($pages as $category => $links): ?>
            <!-- Category Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-folder"></i> <?php echo $category; ?></h4>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($links as $name => $path): ?>
                            <?php
                            $full_url = SITE_URL . '/' . $path;
                            $file_path = __DIR__ . '/' . $path;
                            $exists = file_exists($file_path);
                            ?>
                            <a href="<?php echo $full_url; ?>" 
                               target="_blank" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-<?php echo $exists ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i>
                                    <strong><?php echo $name; ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo $path; ?></small>
                                </div>
                                <div>
                                    <?php if ($exists): ?>
                                        <span class="badge bg-success">File Exists</span>
                                        <i class="fas fa-external-link-alt"></i>
                                    <?php else: ?>
                                        <span class="badge bg-danger">File Missing!</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Database Connection Test -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-database"></i> Database Connection Test</h4>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        // Test queries
                        $users_count = $db->single("SELECT COUNT(*) as count FROM users")['count'];
                        $routes_count = $db->single("SELECT COUNT(*) as count FROM routes")['count'];
                        $buses_count = $db->single("SELECT COUNT(*) as count FROM buses")['count'];
                        $arrivals_count = $db->single("SELECT COUNT(*) as count FROM bus_arrivals")['count'];
                        $departures_count = $db->single("SELECT COUNT(*) as count FROM bus_departures")['count'];
                        
                        echo '<div class="alert alert-success">';
                        echo '<h5><i class="fas fa-check-circle"></i> Database Connection: OK</h5>';
                        echo '<ul class="mb-0">';
                        echo '<li>Users: ' . $users_count . '</li>';
                        echo '<li>Routes: ' . $routes_count . '</li>';
                        echo '<li>Buses: ' . $buses_count . '</li>';
                        echo '<li>Total Arrivals: ' . $arrivals_count . '</li>';
                        echo '<li>Total Departures: ' . $departures_count . '</li>';
                        echo '</ul>';
                        echo '</div>';
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">';
                        echo '<h5><i class="fas fa-times-circle"></i> Database Connection: FAILED</h5>';
                        echo '<p>' . $e->getMessage() . '</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Navigation Test -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-bars"></i> Navigation Menu Test</h4>
                </div>
                <div class="card-body">
                    <p><strong>Test these navigation elements:</strong></p>
                    <ol>
                        <li><strong>Dashboard Link</strong> - Click logo or "Dashboard" menu item</li>
                        <li><strong>Terminal IN</strong> - Should appear if you're admin or Terminal IN operator</li>
                        <li><strong>Terminal OUT</strong> - Should appear if you're admin or Terminal OUT operator</li>
                        <li><strong>Reports Dropdown</strong> - Click to expand, test all 5 report links</li>
                        <li><strong>Administration Dropdown</strong> - Click to expand, test all 4 links</li>
                        <li><strong>User Profile Dropdown</strong> - Click your name, test Profile/Change Password/Logout</li>
                        <li><strong>Mobile Menu</strong> - Resize browser to mobile size, test hamburger menu</li>
                    </ol>
                    <div class="alert alert-info mb-0">
                        <strong>Expected:</strong> All dropdowns should open on click, all links should work without errors.
                    </div>
                </div>
            </div>

            <!-- Role-Based Access Test -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0"><i class="fas fa-shield-alt"></i> Role-Based Access Test</h4>
                </div>
                <div class="card-body">
                    <p><strong>To test role-based access:</strong></p>
                    <ol>
                        <li>Login as <code>admin</code> - Verify ALL menus visible</li>
                        <li>Logout and login as <code>terminal_in</code> - Verify ONLY Dashboard + Terminal IN visible</li>
                        <li>Logout and login as <code>terminal_out</code> - Verify ONLY Dashboard + Terminal OUT visible</li>
                    </ol>
                    <div class="alert alert-success">
                        <strong>Current User:</strong> <?php echo $_SESSION['full_name']; ?> 
                        (<?php echo $_SESSION['user_role']; ?>)
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0"><i class="fas fa-info-circle"></i> System Information</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Site URL</th>
                            <td><?php echo SITE_URL; ?></td>
                        </tr>
                        <tr>
                            <th>Application Name</th>
                            <td><?php echo APP_NAME; ?></td>
                        </tr>
                        <tr>
                            <th>PHP Version</th>
                            <td><?php echo phpversion(); ?></td>
                        </tr>
                        <tr>
                            <th>Database Host</th>
                            <td><?php echo DB_HOST; ?></td>
                        </tr>
                        <tr>
                            <th>Database Name</th>
                            <td><?php echo DB_NAME; ?></td>
                        </tr>
                        <tr>
                            <th>Environment</th>
                            <td>
                                <?php 
                                $is_azure = getenv('WEBSITE_SITE_NAME');
                                echo $is_azure ? '<span class="badge bg-info">Azure Cloud</span>' : '<span class="badge bg-secondary">Local Development</span>';
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Final Actions -->
            <div class="text-center mb-5">
                <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-success btn-lg ms-2">
                    <i class="fas fa-check"></i> All Links Verified - Ready to Delete This Page
                </a>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
