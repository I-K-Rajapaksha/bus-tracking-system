<?php
/**
 * Daily Report
 * Complete report of all bus movements for a selected date
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Require appropriate role
requireRole([ROLE_SUPER_ADMIN, ROLE_REPORT_VIEWER]);

$db = new Database();
$page_title = 'Daily Report';

// Get selected date (default to today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Export to CSV if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql = "SELECT 
                ba.bus_number,
                r.route_number,
                r.route_name,
                ba.arrival_datetime,
                bd.departure_datetime,
                bd.dwell_time_minutes,
                ba.entry_method,
                ba.operator_name,
                u_in.full_name as recorded_in_by,
                u_out.full_name as recorded_out_by
            FROM bus_arrivals ba
            LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
            LEFT JOIN routes r ON ba.route_id = r.route_id
            LEFT JOIN users u_in ON ba.recorded_by = u_in.user_id
            LEFT JOIN users u_out ON bd.recorded_by = u_out.user_id
            WHERE DATE(ba.arrival_datetime) = ?
            ORDER BY ba.arrival_datetime";
    
    $data = $db->resultSet($sql, [$selected_date]);
    
    $headers = ['Bus Number', 'Route Number', 'Route Name', 'Arrival Time', 'Departure Time', 
                'Dwell Time (min)', 'Entry Method', 'Operator', 'Recorded IN By', 'Recorded OUT By'];
    
    exportToCSV($data, 'daily_report_' . $selected_date, $headers);
}

// Get summary statistics
$sql_summary = "SELECT 
                    COUNT(DISTINCT ba.arrival_id) as total_arrivals,
                    COUNT(DISTINCT bd.departure_id) as total_departures,
                    COUNT(DISTINCT CASE WHEN ba.entry_method = 'registered' THEN ba.arrival_id END) as registered_entries,
                    COUNT(DISTINCT CASE WHEN ba.entry_method = 'manual' THEN ba.arrival_id END) as manual_entries,
                    AVG(bd.dwell_time_minutes) as avg_dwell_time
                FROM bus_arrivals ba
                LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
                WHERE DATE(ba.arrival_datetime) = ?";
$summary = $db->single($sql_summary, [$selected_date]);

// Get route-wise breakdown
$sql_routes = "SELECT 
                    r.route_number,
                    r.route_name,
                    r.origin,
                    r.destination,
                    r.distance_km,
                    r.estimated_duration_minutes,
                    COUNT(DISTINCT ba.arrival_id) as arrival_count
                FROM bus_arrivals ba
                LEFT JOIN routes r ON ba.route_id = r.route_id
                WHERE DATE(ba.arrival_datetime) = ?
                GROUP BY r.route_id, r.route_number, r.route_name, r.origin, r.destination, r.distance_km, r.estimated_duration_minutes
                ORDER BY arrival_count DESC";
$route_breakdown = $db->resultSet($sql_routes, [$selected_date]);

// Get detailed movements
$sql_movements = "SELECT 
                    ba.bus_number,
                    r.route_number,
                    r.route_name,
                    r.origin,
                    r.destination,
                    r.distance_km,
                    ba.arrival_datetime,
                    bd.departure_datetime,
                    bd.dwell_time_minutes,
                    ba.entry_method,
                    ba.operator_name,
                    ba.status
                FROM bus_arrivals ba
                LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
                LEFT JOIN routes r ON ba.route_id = r.route_id
                WHERE DATE(ba.arrival_datetime) = ?
                ORDER BY ba.arrival_datetime";
$movements = $db->resultSet($sql_movements, [$selected_date]);

include '../../includes/header.php';
?>

<div class="row no-print">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-calendar-day"></i> Daily Report</h2>
                <p class="text-muted">Complete bus movements for selected date</p>
            </div>
            <div>
                <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Date Selection -->
<div class="row no-print mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <label for="date" class="form-label fw-bold">Select Date</label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?php echo $selected_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> View Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <label class="form-label fw-bold">Export Options</label>
                <div class="d-grid gap-2">
                    <a href="?date=<?php echo $selected_date; ?>&export=csv" class="btn btn-success">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </a>
                    <button onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Header (Print) -->
<div class="row mb-4 d-none d-print-block">
    <div class="col-12 text-center">
        <h2>Makumbura Multimodal Center</h2>
        <h4>Daily Bus Movement Report</h4>
        <p>Date: <?php echo formatDate($selected_date); ?></p>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-primary shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-primary"><?php echo $summary['total_arrivals'] ?? 0; ?></h3>
                <p class="text-muted mb-0">Total Arrivals</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-success"><?php echo $summary['total_departures'] ?? 0; ?></h3>
                <p class="text-muted mb-0">Total Departures</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-info"><?php echo $summary['registered_entries'] ?? 0; ?></h3>
                <p class="text-muted mb-0">Registered Entries</p>
                <small class="text-muted"><?php echo $summary['manual_entries'] ?? 0; ?> Manual</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-warning">
                    <?php echo $summary['avg_dwell_time'] ? round($summary['avg_dwell_time']) : 0; ?> min
                </h3>
                <p class="text-muted mb-0">Avg Dwell Time</p>
            </div>
        </div>
    </div>
</div>

<!-- Route-wise Breakdown -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-route"></i> Route-wise Breakdown</h5>
            </div>
            <div class="card-body">
                <?php if ($route_breakdown && count($route_breakdown) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Route Number</th>
                                    <th>Route Name</th>
                                    <th>Origin</th>
                                    <th>Destination</th>
                                    <th class="text-end">Distance (km)</th>
                                    <th class="text-end">Est. Duration</th>
                                    <th class="text-end">Bus Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($route_breakdown as $route): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($route['route_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($route['route_name']); ?></td>
                                    <td><?php echo htmlspecialchars($route['origin'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($route['destination'] ?? '-'); ?></td>
                                    <td class="text-end"><?php echo $route['distance_km'] ? number_format($route['distance_km'], 1) : '-'; ?></td>
                                    <td class="text-end"><?php echo $route['estimated_duration_minutes'] ? $route['estimated_duration_minutes'] . ' min' : '-'; ?></td>
                                    <td class="text-end">
                                        <span class="badge bg-primary"><?php echo $route['arrival_count']; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No data available for selected date</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Movements -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Detailed Bus Movements</h5>
            </div>
            <div class="card-body">
                <?php if ($movements && count($movements) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="movementsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Bus Number</th>
                                    <th>Route</th>
                                    <th>Origin → Destination</th>
                                    <th>Distance</th>
                                    <th>Arrival Time</th>
                                    <th>Departure Time</th>
                                    <th>Dwell Time</th>
                                    <th>Entry Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movements as $movement): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($movement['bus_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($movement['route_number']); ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($movement['origin'] ?? '-') . ' → ' . htmlspecialchars($movement['destination'] ?? '-'); ?>
                                        </small>
                                    </td>
                                    <td><small><?php echo $movement['distance_km'] ? number_format($movement['distance_km'], 1) . ' km' : '-'; ?></small></td>
                                    <td><?php echo formatTime($movement['arrival_datetime']); ?></td>
                                    <td>
                                        <?php echo $movement['departure_datetime'] ? 
                                                   formatTime($movement['departure_datetime']) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php if ($movement['dwell_time_minutes']): ?>
                                            <span class="badge bg-info">
                                                <?php echo formatDwellTime($movement['dwell_time_minutes']); ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $movement['entry_method'] === 'registered' ? 
                                                                       'primary' : 'warning'; ?>">
                                            <?php echo ucfirst($movement['entry_method']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($movement['status'] === 'in_terminal'): ?>
                                            <span class="badge bg-success">In Terminal</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Departed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No movements recorded for selected date</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Print Footer -->
<div class="row d-none d-print-block">
    <div class="col-12 mt-5">
        <hr>
        <p class="text-muted small">
            Generated on: <?php echo formatDateTime(date('Y-m-d H:i:s')); ?> | 
            Generated by: <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        </p>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
