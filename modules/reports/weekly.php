<?php
/**
 * Weekly Report
 * 7-day breakdown of bus movements with daily trends
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Require appropriate role
requireRole([ROLE_SUPER_ADMIN, ROLE_REPORT_VIEWER]);

$db = new Database();
$page_title = 'Weekly Report';

// Get selected start date (default to Monday of current week)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d', strtotime('monday this week'));

// Calculate end date (6 days after start date)
$start_date = $selected_date;
$end_date = date('Y-m-d', strtotime($start_date . ' +6 days'));

// Export to CSV if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql = "SELECT 
                DATE(ba.arrival_datetime) as date,
                COUNT(DISTINCT ba.arrival_id) as arrivals,
                COUNT(DISTINCT bd.departure_id) as departures,
                AVG(bd.dwell_time_minutes) as avg_dwell
            FROM bus_arrivals ba
            LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
            WHERE DATE(ba.arrival_datetime) BETWEEN ? AND ?
            GROUP BY DATE(ba.arrival_datetime)
            ORDER BY date";
    
    $data = $db->resultSet($sql, [$start_date, $end_date]);
    
    $headers = ['Date', 'Arrivals', 'Departures', 'Avg Dwell Time (min)'];
    
    exportToCSV($data, 'weekly_report_' . $start_date . '_to_' . $end_date, $headers);
}

// Get daily breakdown for the week
$daily_data = [];
$current_date = $start_date;

for ($i = 0; $i < 7; $i++) {
    $sql_daily = "SELECT 
                      DATE(ba.arrival_datetime) as date,
                      COUNT(DISTINCT ba.arrival_id) as arrivals,
                      COUNT(DISTINCT bd.departure_id) as departures,
                      AVG(bd.dwell_time_minutes) as avg_dwell_time
                  FROM bus_arrivals ba
                  LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
                  WHERE DATE(ba.arrival_datetime) = ?
                  GROUP BY DATE(ba.arrival_datetime)";
    
    $day_stats = $db->single($sql_daily, [$current_date]);
    
    $daily_data[] = [
        'date' => $current_date,
        'day_name' => date('l', strtotime($current_date)),
        'arrivals' => $day_stats['arrivals'] ?? 0,
        'departures' => $day_stats['departures'] ?? 0,
        'avg_dwell_time' => $day_stats['avg_dwell_time'] ?? 0
    ];
    
    $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
}

// Calculate weekly totals
$total_arrivals = array_sum(array_column($daily_data, 'arrivals'));
$total_departures = array_sum(array_column($daily_data, 'departures'));

// Calculate average dwell time (excluding zeros)
$dwell_times = array_filter(array_column($daily_data, 'avg_dwell_time'));
$avg_dwell_time = count($dwell_times) > 0 ? array_sum($dwell_times) / count($dwell_times) : 0;

// Find busiest day
$busiest_day = $daily_data[0];
foreach ($daily_data as $day) {
    if ($day['arrivals'] > $busiest_day['arrivals']) {
        $busiest_day = $day;
    }
}

// Get route performance for the week
$sql_routes = "SELECT 
                  r.route_number,
                  r.route_name,
                  r.origin,
                  r.destination,
                  r.distance_km,
                  r.estimated_duration_minutes,
                  COUNT(DISTINCT ba.arrival_id) as total_arrivals,
                  AVG(bd.dwell_time_minutes) as avg_dwell_time
              FROM bus_arrivals ba
              LEFT JOIN routes r ON ba.route_id = r.route_id
              LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
              WHERE DATE(ba.arrival_datetime) BETWEEN ? AND ?
              GROUP BY r.route_id, r.route_number, r.route_name, r.origin, r.destination, r.distance_km, r.estimated_duration_minutes
              ORDER BY total_arrivals DESC";
$route_performance = $db->resultSet($sql_routes, [$start_date, $end_date]);

// Prepare chart data
$chart_dates = [];
$chart_arrivals = [];
$chart_departures = [];

foreach ($daily_data as $day) {
    $chart_dates[] = date('D, M j', strtotime($day['date']));
    $chart_arrivals[] = $day['arrivals'];
    $chart_departures[] = $day['departures'];
}

include '../../includes/header.php';
?>

<div class="row no-print">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-calendar-week"></i> Weekly Report</h2>
                <p class="text-muted">7-day bus movement analysis</p>
            </div>
            <div>
                <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Week Selection -->
<div class="row no-print mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <label for="date" class="form-label fw-bold">Select Week Start Date (Monday)</label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?php echo $start_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                        <small class="text-muted">Week: <?php echo formatDate($start_date) . ' to ' . formatDate($end_date); ?></small>
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
                    <a href="?date=<?php echo $start_date; ?>&export=csv" class="btn btn-success">
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
        <h4>Weekly Bus Movement Report</h4>
        <p>Week: <?php echo formatDate($start_date) . ' to ' . formatDate($end_date); ?></p>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-primary shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-primary"><?php echo $total_arrivals; ?></h3>
                <p class="text-muted mb-0">Total Arrivals</p>
                <small class="text-muted"><?php echo round($total_arrivals / 7, 1); ?> per day</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-success"><?php echo $total_departures; ?></h3>
                <p class="text-muted mb-0">Total Departures</p>
                <small class="text-muted"><?php echo round($total_departures / 7, 1); ?> per day</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-warning"><?php echo $busiest_day['day_name']; ?></h3>
                <p class="text-muted mb-0">Busiest Day</p>
                <small class="text-muted"><?php echo $busiest_day['arrivals']; ?> arrivals</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-info"><?php echo round($avg_dwell_time); ?> min</h3>
                <p class="text-muted mb-0">Avg Dwell Time</p>
            </div>
        </div>
    </div>
</div>

<!-- Weekly Chart -->
<div class="row mb-4 no-print">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Daily Trend Analysis</h5>
            </div>
            <div class="card-body">
                <canvas id="weeklyChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Daily Breakdown Table -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-table"></i> Daily Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th class="text-end">Arrivals</th>
                                <th class="text-end">Departures</th>
                                <th class="text-end">Net Change</th>
                                <th class="text-end">Avg Dwell Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_data as $day): ?>
                            <tr>
                                <td><strong><?php echo formatDate($day['date']); ?></strong></td>
                                <td><?php echo $day['day_name']; ?></td>
                                <td class="text-end">
                                    <span class="badge bg-primary"><?php echo $day['arrivals']; ?></span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-success"><?php echo $day['departures']; ?></span>
                                </td>
                                <td class="text-end">
                                    <?php 
                                    $net = $day['arrivals'] - $day['departures'];
                                    if ($net > 0) {
                                        echo '<span class="text-success">+' . $net . '</span>';
                                    } elseif ($net < 0) {
                                        echo '<span class="text-danger">' . $net . '</span>';
                                    } else {
                                        echo '<span class="text-muted">0</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($day['avg_dwell_time'] > 0): ?>
                                        <span class="badge bg-info"><?php echo round($day['avg_dwell_time']); ?> min</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2">Total / Average</th>
                                <th class="text-end"><strong><?php echo $total_arrivals; ?></strong></th>
                                <th class="text-end"><strong><?php echo $total_departures; ?></strong></th>
                                <th class="text-end"><strong><?php echo ($total_arrivals - $total_departures); ?></strong></th>
                                <th class="text-end"><strong><?php echo round($avg_dwell_time); ?> min</strong></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Route Performance -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-route"></i> Route Performance (Weekly)</h5>
            </div>
            <div class="card-body">
                <?php if ($route_performance && count($route_performance) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Route Number</th>
                                    <th>Route Name</th>
                                    <th>Origin → Destination</th>
                                    <th class="text-end">Distance</th>
                                    <th class="text-end">Est. Duration</th>
                                    <th class="text-end">Total Arrivals</th>
                                    <th class="text-end">Avg Dwell Time</th>
                                    <th class="text-end">Daily Average</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($route_performance as $route): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($route['route_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($route['route_name']); ?></td>
                                    <td><small><?php echo htmlspecialchars($route['origin'] ?? '-') . ' → ' . htmlspecialchars($route['destination'] ?? '-'); ?></small></td>
                                    <td class="text-end"><small><?php echo $route['distance_km'] ? number_format($route['distance_km'], 1) . ' km' : '-'; ?></small></td>
                                    <td class="text-end"><small><?php echo $route['estimated_duration_minutes'] ? $route['estimated_duration_minutes'] . ' min' : '-'; ?></small></td>
                                    <td class="text-end">
                                        <span class="badge bg-primary"><?php echo $route['total_arrivals']; ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($route['avg_dwell_time']): ?>
                                            <span class="badge bg-info"><?php echo round($route['avg_dwell_time']); ?> min</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo round($route['total_arrivals'] / 7, 1); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No data available for selected week</p>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Weekly Trend Chart
const ctx = document.getElementById('weeklyChart').getContext('2d');
const weeklyChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_dates); ?>,
        datasets: [
            {
                label: 'Arrivals',
                data: <?php echo json_encode($chart_arrivals); ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgb(13, 110, 253)',
                borderWidth: 1
            },
            {
                label: 'Departures',
                data: <?php echo json_encode($chart_departures); ?>,
                backgroundColor: 'rgba(25, 135, 84, 0.7)',
                borderColor: 'rgb(25, 135, 84)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            title: {
                display: true,
                text: 'Weekly Bus Activity - <?php echo formatDate($start_date) . " to " . formatDate($end_date); ?>'
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
</script>

<?php include '../../includes/footer.php'; ?>
