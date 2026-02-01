<?php
/**
 * Login Page
 * Bus Arrival and Departure Tracking System
 */

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$timeout_message = '';

// Check for timeout parameter
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $timeout_message = 'Your session has expired. Please login again.';
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = new Database();
        
        // Get user from database
        $sql = "SELECT * FROM users WHERE username = ? AND is_active = 1";
        $user = $db->single($sql, [$username]);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['last_activity'] = time();
            
            // Update last login time
            $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
            $db->query($update_sql, [$user['user_id']]);
            
            // Log the login
            logAudit($db, 'LOGIN', 'users', $user['user_id'], 'User logged in');
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
            
            // Log failed login attempt
            if ($user) {
                logAudit($db, 'LOGIN_FAILED', 'users', $user['user_id'], 'Failed login attempt');
            }
        }
    }
}

$page_title = 'Login';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
        }
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .bus-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card login-card">
            <div class="login-header">
                <div class="bus-icon">
                    <i class="fas fa-bus"></i>
                </div>
                <h3 class="mb-0"><?php echo APP_NAME; ?></h3>
                <p class="mb-0 mt-2">Makumbura Multimodal Center</p>
            </div>
            
            <div class="login-body">
                <h4 class="text-center mb-4">Sign In</h4>
                
                <?php if ($timeout_message): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-clock"></i> <?php echo $timeout_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Enter your username" required autofocus
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Default credentials: admin / Admin@123
                    </small>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3 text-white">
            <small>&copy; <?php echo date('Y'); ?> Makumbura Multimodal Center. All rights reserved.</small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
