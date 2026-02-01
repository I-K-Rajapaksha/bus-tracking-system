<?php
/**
 * Azure Deployment Test Script
 * Test database connection and environment configuration
 */

// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚌 Bus Tracking System - Deployment Test</h1>";
echo "<hr>";

// Test 1: PHP Version
echo "<h2>1. PHP Environment</h2>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Required:</strong> 7.4+<br>";
echo "<strong>Status:</strong> " . (version_compare(phpversion(), '7.4', '>=') ? '✅ OK' : '❌ FAILED') . "<br><br>";

// Test 2: Required Extensions
echo "<h2>2. Required PHP Extensions</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'mbstring', 'openssl'];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<strong>{$ext}:</strong> " . ($loaded ? '✅ Loaded' : '❌ Not Loaded') . "<br>";
}
echo "<br>";

// Test 3: Environment Variables
echo "<h2>3. Azure Environment Variables</h2>";
echo "<strong>WEBSITE_SITE_NAME:</strong> " . (getenv('WEBSITE_SITE_NAME') ?: 'Not set (running locally)') . "<br>";
echo "<strong>DB_HOST:</strong> " . (getenv('DB_HOST') ?: 'Not set') . "<br>";
echo "<strong>DB_NAME:</strong> " . (getenv('DB_NAME') ?: 'Not set') . "<br>";
echo "<strong>DB_USER:</strong> " . (getenv('DB_USER') ?: 'Not set') . "<br>";
echo "<strong>DB_PASS:</strong> " . (getenv('DB_PASS') ? '****** (set)' : 'Not set') . "<br>";
echo "<strong>DB_PORT:</strong> " . (getenv('DB_PORT') ?: 'Not set (using default 3306)') . "<br><br>";

// Test 4: Config File
echo "<h2>4. Configuration File</h2>";
if (file_exists('includes/config.php')) {
    echo "✅ includes/config.php exists<br>";
    require_once 'includes/config.php';
    echo "<strong>DB_HOST:</strong> " . DB_HOST . "<br>";
    echo "<strong>DB_NAME:</strong> " . DB_NAME . "<br>";
    echo "<strong>DB_USER:</strong> " . DB_USER . "<br>";
    echo "<strong>DB_PORT:</strong> " . DB_PORT . "<br>";
    echo "<strong>SITE_URL:</strong> " . SITE_URL . "<br>";
} else {
    echo "❌ includes/config.php not found<br>";
}
echo "<br>";

// Test 5: Database Connection
echo "<h2>5. Database Connection Test</h2>";
try {
    require_once 'includes/database.php';
    $db = new Database();
    
    if ($db->conn) {
        echo "✅ Database connection successful!<br><br>";
        
        // Test query
        echo "<strong>Testing database query...</strong><br>";
        $result = $db->single("SELECT COUNT(*) as count FROM users");
        if ($result) {
            echo "✅ Query successful! Found {$result['count']} user(s)<br>";
            
            // Get admin user
            $admin = $db->single("SELECT username, full_name, user_role FROM users WHERE username = 'admin'");
            if ($admin) {
                echo "✅ Admin user found:<br>";
                echo "&nbsp;&nbsp;&nbsp;Username: {$admin['username']}<br>";
                echo "&nbsp;&nbsp;&nbsp;Name: {$admin['full_name']}<br>";
                echo "&nbsp;&nbsp;&nbsp;Role: {$admin['user_role']}<br>";
            } else {
                echo "⚠️ Admin user not found<br>";
            }
            
            // Check routes
            $routes = $db->single("SELECT COUNT(*) as count FROM routes");
            echo "✅ Routes in database: {$routes['count']}<br>";
            
            // Check buses
            $buses = $db->single("SELECT COUNT(*) as count FROM buses");
            echo "✅ Buses in database: {$buses['count']}<br>";
            
        } else {
            echo "❌ Query failed: " . $db->error . "<br>";
        }
    } else {
        echo "❌ Database connection failed<br>";
        echo "Error: " . $db->error . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
echo "<br>";

// Test 6: File Permissions
echo "<h2>6. File System</h2>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<strong>Current Directory:</strong> " . __DIR__ . "<br>";
$key_files = ['login.php', 'dashboard.php', 'includes/config.php', 'includes/database.php'];
foreach ($key_files as $file) {
    $exists = file_exists($file);
    $readable = is_readable($file);
    echo "<strong>{$file}:</strong> " . ($exists ? '✅ Exists' : '❌ Missing');
    echo " | " . ($readable ? '✅ Readable' : '❌ Not Readable') . "<br>";
}
echo "<br>";

// Test 7: Session
echo "<h2>7. Session Test</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session is active<br>";
    echo "<strong>Session ID:</strong> " . session_id() . "<br>";
} else {
    echo "⚠️ Session not started<br>";
}
echo "<br>";

// Test 8: Role-Based Access
echo "<h2>8. Role-Based Access Control Test</h2>";
echo "<strong>Checking role definitions...</strong><br>";
$roles = [
    'ROLE_SUPER_ADMIN' => defined('ROLE_SUPER_ADMIN') ? ROLE_SUPER_ADMIN : 'Not defined',
    'ROLE_TERMINAL_IN' => defined('ROLE_TERMINAL_IN') ? ROLE_TERMINAL_IN : 'Not defined',
    'ROLE_TERMINAL_OUT' => defined('ROLE_TERMINAL_OUT') ? ROLE_TERMINAL_OUT : 'Not defined',
    'ROLE_REPORT_VIEWER' => defined('ROLE_REPORT_VIEWER') ? ROLE_REPORT_VIEWER : 'Not defined'
];
foreach ($roles as $constant => $value) {
    echo "<strong>{$constant}:</strong> {$value} " . ($value !== 'Not defined' ? '✅' : '❌') . "<br>";
}
echo "<br>";

// Test 9: Critical Pages Exist
echo "<h2>9. Critical Pages Check</h2>";
$critical_pages = [
    'login.php' => 'Login page',
    'dashboard.php' => 'Dashboard',
    'modules/terminal_in/index.php' => 'Terminal IN',
    'modules/terminal_out/index.php' => 'Terminal OUT',
    'modules/reports/daily.php' => 'Daily Report',
    'modules/reports/hourly.php' => 'Hourly Report',
    'modules/reports/weekly.php' => 'Weekly Report',
    'modules/reports/monthly.php' => 'Monthly Report',
    'modules/reports/yearly.php' => 'Yearly Report'
];
foreach ($critical_pages as $page => $description) {
    $exists = file_exists($page);
    echo "<strong>{$description}:</strong> " . ($exists ? "✅ {$page}" : "❌ Missing") . "<br>";
}
echo "<br>";

// Final Summary
echo "<hr>";
echo "<h2>📊 Test Summary</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
echo "<h3 style='color: #155724; margin-top: 0;'>✅ Deployment Status</h3>";
echo "<p style='color: #155724;'><strong>If all tests above show ✅, your system is fully operational!</strong></p>";
echo "<p style='color: #155724;'>You can now login at: <a href='login.php' style='color: #0056b3;'><strong>login.php</strong></a></p>";
echo "<p style='color: #155724;'><strong>Default credentials:</strong><br>Username: admin<br>Password: Admin@123</p>";
echo "<br>";
echo "<p style='color: #155724;'><strong>Available Pages After Login:</strong></p>";
echo "<ul style='color: #155724;'>";
echo "<li><strong>Dashboard</strong> - Overview and real-time statistics</li>";
echo "<li><strong>Terminal IN</strong> - Record bus arrivals (Super Admin & Terminal IN operators)</li>";
echo "<li><strong>Terminal OUT</strong> - Record bus departures (Super Admin & Terminal OUT operators)</li>";
echo "<li><strong>Reports</strong> - View all reports (Super Admin & Report Viewers)</li>";
echo "<li><strong>Administration</strong> - Manage users, routes, buses (Super Admin only)</li>";
echo "</ul>";
echo "</div>";

echo "<br>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffeeba;'>";
echo "<p style='color: #856404; margin: 0;'><strong>⚠️ Security Notice:</strong> Delete this test file (test_connection.php) and fix_password.php before going to production!</p>";
echo "</div>";
?>
