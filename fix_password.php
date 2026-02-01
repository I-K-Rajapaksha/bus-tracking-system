<?php
/**
 * Password Debug Script
 * Check admin user and password hash
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h1>🔐 Password Verification Test</h1>";
echo "<hr>";

try {
    $db = new Database();
    
    echo "<h2>1. Admin User Check</h2>";
    $sql = "SELECT user_id, username, full_name, user_role, password_hash, is_active, created_at FROM users WHERE username = 'admin'";
    $admin = $db->single($sql);
    
    if ($admin) {
        echo "✅ Admin user found!<br><br>";
        echo "<strong>User ID:</strong> {$admin['user_id']}<br>";
        echo "<strong>Username:</strong> {$admin['username']}<br>";
        echo "<strong>Full Name:</strong> {$admin['full_name']}<br>";
        echo "<strong>Role:</strong> {$admin['user_role']}<br>";
        echo "<strong>Active:</strong> " . ($admin['is_active'] ? 'Yes' : 'No') . "<br>";
        echo "<strong>Created:</strong> {$admin['created_at']}<br>";
        echo "<strong>Password Hash:</strong> " . substr($admin['password_hash'], 0, 20) . "...<br><br>";
        
        echo "<h2>2. Password Verification Test</h2>";
        
        $test_password = 'Admin@123';
        $result = password_verify($test_password, $admin['password_hash']);
        
        echo "<strong>Testing password:</strong> {$test_password}<br>";
        echo "<strong>Result:</strong> " . ($result ? '✅ PASSWORD MATCHES!' : '❌ PASSWORD DOES NOT MATCH') . "<br><br>";
        
        if (!$result) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffeeba; margin: 20px 0;'>";
            echo "<h3 style='color: #856404; margin-top: 0;'>⚠️ Password Mismatch - Fixing Now...</h3>";
            echo "<p style='color: #856404;'>The password hash in the database doesn't match 'Admin@123'.</p>";
            echo "<p style='color: #856404;'>Generating new password hash...</p>";
            
            // Generate new password hash
            $new_hash = password_hash('Admin@123', PASSWORD_BCRYPT);
            echo "<strong>New Hash:</strong> " . substr($new_hash, 0, 30) . "...<br>";
            
            // Update database
            $update_sql = "UPDATE users SET password_hash = ? WHERE username = 'admin'";
            $updated = $db->query($update_sql, [$new_hash]);
            
            if ($updated) {
                echo "<p style='color: #155724; font-weight: bold;'>✅ PASSWORD UPDATED SUCCESSFULLY!</p>";
                echo "<p style='color: #155724;'>You can now login with:<br>Username: admin<br>Password: Admin@123</p>";
                
                // Verify the fix
                $admin_check = $db->single("SELECT password_hash FROM users WHERE username = 'admin'");
                $verify_new = password_verify('Admin@123', $admin_check['password_hash']);
                echo "<p style='color: #155724;'>Verification: " . ($verify_new ? '✅ Confirmed working!' : '❌ Still not working') . "</p>";
            } else {
                echo "<p style='color: #721c24;'>❌ Update failed: " . $db->error . "</p>";
            }
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
            echo "<p style='color: #155724; margin: 0;'><strong>✅ Password is correct!</strong> You should be able to login.</p>";
            echo "</div>";
        }
        
    } else {
        echo "❌ Admin user NOT found in database!<br><br>";
        
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
        echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Creating Admin User...</h3>";
        
        // Create admin user
        $password_hash = password_hash('Admin@123', PASSWORD_BCRYPT);
        $insert_sql = "INSERT INTO users (username, password_hash, full_name, user_role, is_active) 
                       VALUES (?, ?, ?, ?, ?)";
        $created = $db->query($insert_sql, [
            'admin',
            $password_hash,
            'System Administrator',
            'super_admin',
            1
        ]);
        
        if ($created) {
            echo "<p style='color: #155724; font-weight: bold;'>✅ ADMIN USER CREATED!</p>";
            echo "<p style='color: #155724;'>You can now login with:<br>Username: admin<br>Password: Admin@123</p>";
        } else {
            echo "<p style='color: #721c24;'>❌ Failed to create admin: " . $db->error . "</p>";
        }
        echo "</div>";
    }
    
    echo "<br><h2>3. All Users in Database</h2>";
    $all_users = $db->resultSet("SELECT user_id, username, full_name, user_role, is_active FROM users");
    if ($all_users) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Active</th></tr>";
        foreach ($all_users as $u) {
            echo "<tr>";
            echo "<td>{$u['user_id']}</td>";
            echo "<td>{$u['username']}</td>";
            echo "<td>{$u['full_name']}</td>";
            echo "<td>{$u['user_role']}</td>";
            echo "<td>" . ($u['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No users found.";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "<p style='color: #721c24;'><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<br><hr>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; border: 1px solid #bee5eb;'>";
echo "<p style='color: #0c5460; margin: 0;'><strong>Next Step:</strong> Go back to <a href='login.php'>login.php</a> and try logging in again.</p>";
echo "</div>";

echo "<br>";
echo "<p><a href='test_connection.php'>← Back to Diagnostics</a> | <a href='login.php'>Go to Login →</a></p>";
?>
