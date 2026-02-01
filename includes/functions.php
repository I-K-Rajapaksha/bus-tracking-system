<?php
/**
 * Common Functions
 * Helper functions used throughout the application
 */

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has required role
 */
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_array($required_role)) {
        return in_array($_SESSION['user_role'], $required_role);
    }
    
    return $_SESSION['user_role'] === $required_role;
}

/**
 * Redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

/**
 * Redirect if user doesn't have required role
 */
function requireRole($required_role) {
    requireLogin();
    
    if (!hasRole($required_role)) {
        $_SESSION['error'] = 'Access denied. Insufficient permissions.';
        header('Location: ' . SITE_URL . '/dashboard.php');
        exit;
    }
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date/time
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) return '-';
    $date = new DateTime($datetime);
    return $date->format($format);
}

/**
 * Format date only
 */
function formatDate($datetime) {
    return formatDateTime($datetime, 'Y-m-d');
}

/**
 * Format time only
 */
function formatTime($datetime) {
    return formatDateTime($datetime, 'H:i:s');
}

/**
 * Get user's IP address
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Get user's browser info
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Log audit trail
 */
function logAudit($db, $action_type, $table_name = null, $record_id = null, $description = null) {
    if (!isLoggedIn()) return false;
    
    $sql = "INSERT INTO audit_logs (user_id, action_type, table_name, record_id, action_description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    return $db->query($sql, [
        $_SESSION['user_id'],
        $action_type,
        $table_name,
        $record_id,
        $description,
        getUserIP(),
        getUserAgent()
    ]);
}

/**
 * Display flash message
 */
function flashMessage($type = 'success') {
    if (isset($_SESSION[$type])) {
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        
        $class = $type === 'success' ? 'alert-success' : 'alert-danger';
        echo "<div class='alert {$class} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get role display name
 */
function getRoleName($role) {
    $roles = [
        ROLE_SUPER_ADMIN => 'Super Administrator',
        ROLE_TERMINAL_IN => 'Terminal IN Operator',
        ROLE_TERMINAL_OUT => 'Terminal OUT Operator',
        ROLE_REPORT_VIEWER => 'Report Viewer'
    ];
    
    return $roles[$role] ?? 'Unknown';
}

/**
 * Calculate dwell time in minutes
 */
function calculateDwellTime($arrival_time, $departure_time = null) {
    $arrival = new DateTime($arrival_time);
    $departure = $departure_time ? new DateTime($departure_time) : new DateTime();
    
    $interval = $arrival->diff($departure);
    return ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
}

/**
 * Format minutes to hours and minutes
 */
function formatDwellTime($minutes) {
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    return $hours . 'h ' . $mins . 'm';
}

/**
 * Export data to CSV
 */
function exportToCSV($data, $filename, $headers = []) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    if (!empty($headers)) {
        fputcsv($output, $headers);
    } elseif (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Check if bus is currently in terminal
 */
function isBusInTerminal($db, $bus_number) {
    $sql = "SELECT COUNT(*) as count FROM bus_arrivals 
            WHERE bus_number = ? AND status = 'in_terminal'";
    $result = $db->single($sql, [$bus_number]);
    return $result && $result['count'] > 0;
}

/**
 * Get current buses in terminal count
 */
function getBusesInTerminalCount($db) {
    $sql = "SELECT COUNT(*) as count FROM bus_arrivals WHERE status = 'in_terminal'";
    $result = $db->single($sql);
    return $result ? $result['count'] : 0;
}

/**
 * Pagination helper
 */
function getPagination($current_page, $total_records, $records_per_page = 20) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'limit' => $records_per_page,
        'total_records' => $total_records
    ];
}
?>
