<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Requirements Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .check-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #ddd;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .check-item.success {
            border-left-color: #28a745;
        }
        .check-item.error {
            border-left-color: #dc3545;
        }
        .check-item.warning {
            border-left-color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="check-container">
            <div class="text-center mb-4">
                <h2><i class="fas fa-clipboard-check text-primary"></i> System Requirements Check</h2>
                <p class="text-muted">Bus Tracking System - Makumbura MMC</p>
            </div>

            <?php
            $all_ok = true;
            
            // Check PHP Version
            $php_version = phpversion();
            $php_ok = version_compare($php_version, '7.4.0', '>=');
            if (!$php_ok) $all_ok = false;
            ?>
            
            <div class="check-item <?php echo $php_ok ? 'success' : 'error'; ?>">
                <strong><i class="fas fa-<?php echo $php_ok ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i> PHP Version:</strong> 
                <?php echo $php_version; ?>
                <?php if (!$php_ok): ?>
                    <br><small class="text-danger">⚠ PHP 7.4 or higher required</small>
                <?php else: ?>
                    <br><small class="text-success">✓ PHP version is compatible</small>
                <?php endif; ?>
            </div>

            <?php
            // Check PDO Extension
            $pdo_ok = extension_loaded('PDO');
            if (!$pdo_ok) $all_ok = false;
            ?>
            
            <div class="check-item <?php echo $pdo_ok ? 'success' : 'error'; ?>">
                <strong><i class="fas fa-<?php echo $pdo_ok ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i> PDO Extension:</strong> 
                <?php echo $pdo_ok ? 'Installed' : 'Not Installed'; ?>
                <?php if (!$pdo_ok): ?>
                    <br><small class="text-danger">⚠ PDO extension is required</small>
                <?php endif; ?>
            </div>

            <?php
            // Check PDO MySQL
            $pdo_mysql_ok = extension_loaded('pdo_mysql');
            if (!$pdo_mysql_ok) $all_ok = false;
            ?>
            
            <div class="check-item <?php echo $pdo_mysql_ok ? 'success' : 'error'; ?>">
                <strong><i class="fas fa-<?php echo $pdo_mysql_ok ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i> PDO MySQL Driver:</strong> 
                <?php echo $pdo_mysql_ok ? 'Installed' : 'Not Installed'; ?>
                <?php if (!$pdo_mysql_ok): ?>
                    <br><small class="text-danger">⚠ PDO MySQL driver is required</small>
                <?php endif; ?>
            </div>

            <?php
            // Check mbstring
            $mbstring_ok = extension_loaded('mbstring');
            ?>
            
            <div class="check-item <?php echo $mbstring_ok ? 'success' : 'warning'; ?>">
                <strong><i class="fas fa-<?php echo $mbstring_ok ? 'check-circle text-success' : 'exclamation-triangle text-warning'; ?>"></i> mbstring Extension:</strong> 
                <?php echo $mbstring_ok ? 'Installed' : 'Not Installed'; ?>
                <?php if (!$mbstring_ok): ?>
                    <br><small class="text-warning">⚠ Recommended for better string handling</small>
                <?php endif; ?>
            </div>

            <?php
            // Check JSON
            $json_ok = extension_loaded('json');
            if (!$json_ok) $all_ok = false;
            ?>
            
            <div class="check-item <?php echo $json_ok ? 'success' : 'error'; ?>">
                <strong><i class="fas fa-<?php echo $json_ok ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i> JSON Extension:</strong> 
                <?php echo $json_ok ? 'Installed' : 'Not Installed'; ?>
                <?php if (!$json_ok): ?>
                    <br><small class="text-danger">⚠ JSON extension is required</small>
                <?php endif; ?>
            </div>

            <?php
            // Check Session
            $session_ok = extension_loaded('session');
            if (!$session_ok) $all_ok = false;
            ?>
            
            <div class="check-item <?php echo $session_ok ? 'success' : 'error'; ?>">
                <strong><i class="fas fa-<?php echo $session_ok ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i> Session Support:</strong> 
                <?php echo $session_ok ? 'Enabled' : 'Disabled'; ?>
                <?php if (!$session_ok): ?>
                    <br><small class="text-danger">⚠ Session support is required</small>
                <?php endif; ?>
            </div>

            <?php
            // Check Database Connection
            $db_ok = false;
            $db_message = '';
            
            if (file_exists('includes/config.php')) {
                try {
                    require_once 'includes/config.php';
                    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                    $pdo = new PDO($dsn, DB_USER, DB_PASS);
                    $db_ok = true;
                    $db_message = 'Connected successfully';
                } catch (PDOException $e) {
                    $db_message = $e->getMessage();
                }
            } else {
                $db_message = 'config.php not found';
            }
            ?>
            
            <div class="check-item <?php echo $db_ok ? 'success' : 'error'; ?>">
                <strong><i class="fas fa-<?php echo $db_ok ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i> Database Connection:</strong> 
                <?php echo $db_ok ? 'Working' : 'Failed'; ?>
                <br><small class="<?php echo $db_ok ? 'text-success' : 'text-danger'; ?>">
                    <?php echo htmlspecialchars($db_message); ?>
                </small>
            </div>

            <?php
            // Check Write Permissions (for logs, uploads, etc.)
            $writable_ok = is_writable(__DIR__);
            ?>
            
            <div class="check-item <?php echo $writable_ok ? 'success' : 'warning'; ?>">
                <strong><i class="fas fa-<?php echo $writable_ok ? 'check-circle text-success' : 'exclamation-triangle text-warning'; ?>"></i> Directory Permissions:</strong> 
                <?php echo $writable_ok ? 'Writable' : 'Read-only'; ?>
                <?php if (!$writable_ok): ?>
                    <br><small class="text-warning">⚠ Some features may require write permissions</small>
                <?php endif; ?>
            </div>

            <hr class="my-4">

            <?php if ($all_ok && $db_ok): ?>
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> All Requirements Met!</h5>
                    <p class="mb-0">Your system meets all requirements. You can proceed with the installation.</p>
                </div>
                <div class="text-center">
                    <a href="login.php" class="btn btn-success btn-lg">
                        <i class="fas fa-arrow-right"></i> Proceed to Login
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Some Requirements Not Met</h5>
                    <p class="mb-0">Please resolve the issues marked in red above before proceeding.</p>
                </div>
                <div class="text-center">
                    <button onclick="location.reload()" class="btn btn-primary">
                        <i class="fas fa-redo"></i> Recheck
                    </button>
                </div>
            <?php endif; ?>

            <hr class="my-4">

            <div class="text-center">
                <h6>System Information</h6>
                <div class="row text-center small text-muted">
                    <div class="col-md-4">
                        <strong>PHP Version:</strong><br><?php echo $php_version; ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Server Software:</strong><br><?php echo $_SERVER['SERVER_SOFTWARE']; ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Server OS:</strong><br><?php echo php_uname('s'); ?>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="README.md" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-book"></i> View Documentation
                </a>
                <a href="QUICKSTART.md" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-bolt"></i> Quick Start Guide
                </a>
            </div>
        </div>
    </div>
</body>
</html>
