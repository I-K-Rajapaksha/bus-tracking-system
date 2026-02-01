<?php
/**
 * Create Operator Accounts Script
 * This creates 3 separate accounts with different roles
 * 
 * Run this once to create the operator accounts
 * Then DELETE this file for security
 */

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = new Database();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Create Operator Accounts</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <div class='row justify-content-center'>
        <div class='col-md-8'>
            <div class='card'>
                <div class='card-header bg-primary text-white'>
                    <h4 class='mb-0'><i class='fas fa-users'></i> Create Operator Accounts</h4>
                </div>
                <div class='card-body'>";

// 3 Operator Accounts to Create
$accounts = [
    [
        'username' => 'admin',
        'password' => 'Admin@123',
        'full_name' => 'System Administrator',
        'role' => 'super_admin',
        'description' => 'Full system access - can manage users, routes, buses, view all reports'
    ],
    [
        'username' => 'terminal_in',
        'password' => 'TerminalIn@123',
        'full_name' => 'Terminal IN Operator',
        'role' => 'terminal_in_operator',
        'description' => 'Can ONLY record bus arrivals (Terminal IN function)'
    ],
    [
        'username' => 'terminal_out',
        'password' => 'TerminalOut@123',
        'full_name' => 'Terminal OUT Operator',
        'role' => 'terminal_out_operator',
        'description' => 'Can ONLY record bus departures (Terminal OUT function)'
    ]
];

echo "<h5>Creating 3 Operator Accounts...</h5>";
echo "<div class='alert alert-info'><strong>Note:</strong> Existing accounts will be updated with new passwords.</div>";

foreach ($accounts as $account) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-body'>";
    echo "<h6><strong>Account: {$account['username']}</strong></h6>";
    echo "<p class='mb-2'><strong>Full Name:</strong> {$account['full_name']}</p>";
    echo "<p class='mb-2'><strong>Role:</strong> {$account['role']}</p>";
    echo "<p class='mb-2'><strong>Description:</strong> {$account['description']}</p>";
    
    try {
        // Check if user exists
        $check_sql = "SELECT user_id FROM users WHERE username = :username";
        $existing = $db->single($check_sql, ['username' => $account['username']]);
        
        if ($existing) {
            // Update existing user
            $password_hash = password_hash($account['password'], PASSWORD_BCRYPT);
            $update_sql = "UPDATE users 
                          SET password_hash = :password_hash,
                              full_name = :full_name,
                              user_role = :user_role,
                              is_active = 1,
                              updated_at = NOW()
                          WHERE username = :username";
            
            $db->execute($update_sql, [
                'password_hash' => $password_hash,
                'full_name' => $account['full_name'],
                'user_role' => $account['role'],
                'username' => $account['username']
            ]);
            
            echo "<div class='alert alert-warning'>";
            echo "<strong>✓ UPDATED</strong> - User '{$account['username']}' already existed. Password updated.";
            echo "</div>";
        } else {
            // Create new user
            $password_hash = password_hash($account['password'], PASSWORD_BCRYPT);
            $insert_sql = "INSERT INTO users (username, password_hash, full_name, user_role, is_active) 
                          VALUES (:username, :password_hash, :full_name, :user_role, 1)";
            
            $db->execute($insert_sql, [
                'username' => $account['username'],
                'password_hash' => $password_hash,
                'full_name' => $account['full_name'],
                'user_role' => $account['role']
            ]);
            
            echo "<div class='alert alert-success'>";
            echo "<strong>✓ CREATED</strong> - New user '{$account['username']}' created successfully.";
            echo "</div>";
        }
        
        // Show login credentials
        echo "<div class='alert alert-primary'>";
        echo "<strong>Login Credentials:</strong><br>";
        echo "<strong>Username:</strong> {$account['username']}<br>";
        echo "<strong>Password:</strong> {$account['password']}<br>";
        echo "<strong>Login URL:</strong> <a href='".SITE_URL."/login.php' target='_blank'>".SITE_URL."/login.php</a>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "<strong>✗ ERROR:</strong> " . $e->getMessage();
        echo "</div>";
    }
    
    echo "</div></div>";
}

echo "<hr>";
echo "<h5>Access Control Summary</h5>";
echo "<table class='table table-bordered'>";
echo "<thead class='table-dark'>";
echo "<tr><th>Account</th><th>Dashboard</th><th>Terminal IN</th><th>Terminal OUT</th><th>Reports</th><th>Administration</th></tr>";
echo "</thead>";
echo "<tbody>";
echo "<tr>";
echo "<td><strong>admin</strong><br><small>System Administrator</small></td>";
echo "<td class='table-success'>✓ Full Access</td>";
echo "<td class='table-success'>✓ Can Record</td>";
echo "<td class='table-success'>✓ Can Record</td>";
echo "<td class='table-success'>✓ All Reports</td>";
echo "<td class='table-success'>✓ Full Control</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>terminal_in</strong><br><small>Terminal IN Operator</small></td>";
echo "<td class='table-warning'>✓ View Only</td>";
echo "<td class='table-success'>✓ Can Record</td>";
echo "<td class='table-danger'>✗ No Access</td>";
echo "<td class='table-danger'>✗ No Access</td>";
echo "<td class='table-danger'>✗ No Access</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>terminal_out</strong><br><small>Terminal OUT Operator</small></td>";
echo "<td class='table-warning'>✓ View Only</td>";
echo "<td class='table-danger'>✗ No Access</td>";
echo "<td class='table-success'>✓ Can Record</td>";
echo "<td class='table-danger'>✗ No Access</td>";
echo "<td class='table-danger'>✗ No Access</td>";
echo "</tr>";
echo "</tbody>";
echo "</table>";

echo "<div class='alert alert-danger mt-4'>";
echo "<h5><i class='fas fa-exclamation-triangle'></i> IMPORTANT SECURITY NOTICE</h5>";
echo "<p><strong>DELETE THIS FILE IMMEDIATELY AFTER RUNNING!</strong></p>";
echo "<p>For security reasons, remove this file from your server:</p>";
echo "<code>delete create_operator_accounts.php</code>";
echo "</div>";

echo "<div class='text-center mt-4'>";
echo "<a href='login.php' class='btn btn-primary btn-lg'><i class='fas fa-sign-in-alt'></i> Go to Login Page</a>";
echo "</div>";

echo "</div></div></div></div></div>
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?>
