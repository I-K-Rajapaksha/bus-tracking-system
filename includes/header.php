<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>
    
<?php if (isLoggedIn()): ?>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/dashboard.php">
                <i class="fas fa-bus"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/dashboard.php">
                            <i class="fas fa-dashboard"></i> Dashboard
                        </a>
                    </li>
                    
                    <?php if (hasRole([ROLE_SUPER_ADMIN, ROLE_TERMINAL_IN])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/modules/terminal_in/index.php">
                            <i class="fas fa-arrow-right-to-bracket"></i> Terminal IN
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole([ROLE_SUPER_ADMIN, ROLE_TERMINAL_OUT])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/modules/terminal_out/index.php">
                            <i class="fas fa-arrow-right-from-bracket"></i> Terminal OUT
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole([ROLE_SUPER_ADMIN, ROLE_REPORT_VIEWER])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/reports/hourly.php">Hourly Report</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/reports/daily.php">Daily Report</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/reports/weekly.php">Weekly Report</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/reports/monthly.php">Monthly Report</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/reports/yearly.php">Yearly Report</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/reports/summary.php">Summary Report</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole(ROLE_SUPER_ADMIN)): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Administration
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/admin/users.php"><i class="fas fa-users"></i> User Management</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/master_data/routes.php"><i class="fas fa-route"></i> Route Management</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/master_data/buses.php"><i class="fas fa-bus"></i> Bus Registration</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/admin/audit_logs.php"><i class="fas fa-clipboard-list"></i> Audit Logs</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo sanitize($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">
                                <i class="fas fa-user-circle"></i> My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/change_password.php">
                                <i class="fas fa-key"></i> Change Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<?php endif; ?>
    
    <!-- Main Content -->
    <div class="<?php echo isLoggedIn() ? 'container-fluid mt-4' : ''; ?>">
