<?php
/**
 * Dashboard - Main Overview Page
 */

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Require login
requireLogin();

$db = new Database();
$page_title = 'Dashboard';

// Get statistics
$buses_in_terminal = getBusesInTerminalCount($db);

// Today's arrivals
$sql_today_arrivals = "SELECT COUNT(*) as count FROM bus_arrivals WHERE DATE(arrival_datetime) = CURDATE()";
$today_arrivals = $db->single($sql_today_arrivals);
$today_arrivals_count = $today_arrivals ? $today_arrivals['count'] : 0;

// Today's departures
$sql_today_departures = "SELECT COUNT(*) as count FROM bus_departures WHERE DATE(departure_datetime) = CURDATE()";
$today_departures = $db->single($sql_today_departures);
$today_departures_count = $today_departures ? $today_departures['count'] : 0;

// Registered vs Manual entries today
$sql_entry_methods = "SELECT 
                        SUM(CASE WHEN entry_method = 'registered' THEN 1 ELSE 0 END) as registered,
                        SUM(CASE WHEN entry_method = 'manual' THEN 1 ELSE 0 END) as manual
                      FROM bus_arrivals 
                      WHERE DATE(arrival_datetime) = CURDATE()";
$entry_methods = $db->single($sql_entry_methods);

// Recent arrivals (last 10)
$sql_recent_arrivals = "SELECT ba.*, r.route_number, r.route_name, u.full_name as recorded_by_name
                        FROM bus_arrivals ba
                        LEFT JOIN routes r ON ba.route_id = r.route_id
                        LEFT JOIN users u ON ba.recorded_by = u.user_id
                        ORDER BY ba.arrival_datetime DESC
                        LIMIT 10";
$recent_arrivals = $db->resultSet($sql_recent_arrivals);

// Buses currently in terminal
$sql_in_terminal = "SELECT * FROM vw_buses_in_terminal ORDER BY arrival_datetime DESC LIMIT 10";
$buses_in_terminal_list = $db->resultSet($sql_in_terminal);

// Hourly stats for today
$sql_hourly = "SELECT 
                DATE_FORMAT(arrival_datetime, '%H:00') as hour,
                COUNT(*) as count
               FROM bus_arrivals
               WHERE DATE(arrival_datetime) = CURDATE()
               GROUP BY DATE_FORMAT(arrival_datetime, '%H:00')
               ORDER BY hour";
$hourly_stats = $db->resultSet($sql_hourly);

include 'includes/header.php';
?>

<!-- Flash Messages -->
<?php flashMessage('success'); ?>
<?php flashMessage('error'); ?>

<div class="row">
    <!-- Page Header -->
    <div class="col-12 mb-4">
        <h2><i class="fas fa-dashboard"></i> Dashboard</h2>
        <p class="text-muted">Real-time overview of terminal operations</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <!-- Buses in Terminal -->
    <div class="col-md-3">
        <div class="card border-primary shadow-sm">
            <div class="card-body text-center">
                <div class="display-4 text-primary">
                    <i class="fas fa-bus"></i>
                </div>
                <h3 class="mt-3 mb-0"><?php echo $buses_in_terminal; ?></h3>
                <p class="text-muted mb-0">Buses in Terminal</p>
            </div>
        </div>
    </div>
    
    <!-- Today's Arrivals -->
    <div class="col-md-3">
        <div class="card border-success shadow-sm">
            <div class="card-body text-center">
                <div class="display-4 text-success">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                </div>
                <h3 class="mt-3 mb-0"><?php echo $today_arrivals_count; ?></h3>
                <p class="text-muted mb-0">Today's Arrivals</p>
            </div>
        </div>
    </div>
    
    <!-- Today's Departures -->
    <div class="col-md-3">
        <div class="card border-warning shadow-sm">
            <div class="card-body text-center">
                <div class="display-4 text-warning">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </div>
                <h3 class="mt-3 mb-0"><?php echo $today_departures_count; ?></h3>
                <p class="text-muted mb-0">Today's Departures</p>
            </div>
        </div>
    </div>
    
    <!-- Registered Entries -->
    <div class="col-md-3">
        <div class="card border-info shadow-sm">
            <div class="card-body text-center">
                <div class="display-4 text-info">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h3 class="mt-3 mb-0"><?php echo $entry_methods['registered'] ?? 0; ?></h3>
                <p class="text-muted mb-0">Registered Entries</p>
                <small class="text-muted"><?php echo $entry_methods['manual'] ?? 0; ?> Manual</small>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <?php if (hasRole([ROLE_SUPER_ADMIN, ROLE_TERMINAL_IN])): ?>
                    <div class="col-md-3">
                        <a href="modules/terminal_in/index.php" class="btn btn-success w-100 btn-lg">
                            <i class="fas fa-arrow-right-to-bracket"></i><br>
                            <small>Record Arrival</small>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasRole([ROLE_SUPER_ADMIN, ROLE_TERMINAL_OUT])): ?>
                    <div class="col-md-3">
                        <a href="modules/terminal_out/index.php" class="btn btn-warning w-100 btn-lg">
                            <i class="fas fa-arrow-right-from-bracket"></i><br>
                            <small>Record Departure</small>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasRole([ROLE_SUPER_ADMIN, ROLE_REPORT_VIEWER])): ?>
                    <div class="col-md-3">
                        <a href="modules/reports/daily.php" class="btn btn-info w-100 btn-lg">
                            <i class="fas fa-chart-bar"></i><br>
                            <small>View Reports</small>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasRole(ROLE_SUPER_ADMIN)): ?>
                    <div class="col-md-3">
                        <a href="modules/admin/users.php" class="btn btn-secondary w-100 btn-lg">
                            <i class="fas fa-users"></i><br>
                            <small>Manage Users</small>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Buses Currently in Terminal -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-parking"></i> Buses in Terminal</h5>
            </div>
            <div class="card-body">
                <?php if ($buses_in_terminal_list && count($buses_in_terminal_list) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Bus Number</th>
                                    <th>Route</th>
                                    <th>Time In</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buses_in_terminal_list as $bus): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($bus['route_number']); ?></td>
                                    <td><?php echo formatTime($bus['arrival_datetime']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo formatDwellTime($bus['minutes_in_terminal']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No buses currently in terminal</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Arrivals -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Arrivals</h5>
            </div>
            <div class="card-body">
                <?php if ($recent_arrivals && count($recent_arrivals) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Bus Number</th>
                                    <th>Route</th>
                                    <th>Arrival Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_arrivals as $arrival): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($arrival['bus_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($arrival['route_number']); ?></td>
                                    <td><?php echo formatDateTime($arrival['arrival_datetime'], 'H:i'); ?></td>
                                    <td>
                                        <?php if ($arrival['status'] === 'in_terminal'): ?>
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
                        <p>No recent arrivals</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Hourly Activity Chart -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Today's Hourly Activity</h5>
            </div>
            <div class="card-body">
                <?php if ($hourly_stats && count($hourly_stats) > 0): ?>
                    <canvas id="hourlyChart" height="80"></canvas>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                        <p>No activity data for today</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Hourly Chart
    const hourlyData = ' . json_encode($hourly_stats) . ';
    
    if (hourlyData && hourlyData.length > 0) {
        const ctx = document.getElementById("hourlyChart");
        new Chart(ctx, {
            type: "bar",
            data: {
                labels: hourlyData.map(item => item.hour),
                datasets: [{
                    label: "Bus Arrivals",
                    data: hourlyData.map(item => item.count),
                    backgroundColor: "rgba(13, 110, 253, 0.5)",
                    borderColor: "rgba(13, 110, 253, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
</script>
';

include 'includes/footer.php';
?>
