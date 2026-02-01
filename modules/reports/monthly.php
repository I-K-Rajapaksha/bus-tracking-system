<?php
/**
 * Monthly Report
 * Complete month analysis with route performance and trends
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Require appropriate role
requireRole([ROLE_SUPER_ADMIN, ROLE_REPORT_VIEWER]);

$db = new Database();
$page_title = 'Monthly Report';

// Get selected month (default to current month)
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$year = date('Y', strtotime($selected_month . '-01'));
$month = date('m', strtotime($selected_month . '-01'));
$month_name = date('F Y', strtotime($selected_month . '-01'));

// Calculate start and end dates
$start_date = $selected_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

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
    
    exportToCSV($data, 'monthly_report_' . $selected_month, $headers);
}

// Get monthly summary statistics
$sql_summary = "SELECT 
                    COUNT(DISTINCT ba.arrival_id) as total_arrivals,
                    COUNT(DISTINCT bd.departure_id) as total_departures,
                    COUNT(DISTINCT ba.bus_number) as unique_buses,
                    AVG(bd.dwell_time_minutes) as avg_dwell_time,
                    COUNT(DISTINCT DATE(ba.arrival_datetime)) as active_days
                FROM bus_arrivals ba
                LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
                WHERE DATE(ba.arrival_datetime) BETWEEN ? AND ?";
$summary = $db->single($sql_summary, [$start_date, $end_date]);

// Get daily statistics for the month
$sql_daily = "SELECT 
                  DATE(ba.arrival_datetime) as date,
                  DAY(ba.arrival_datetime) as day,
                  COUNT(DISTINCT ba.arrival_id) as arrivals,
                  COUNT(DISTINCT bd.departure_id) as departures
              FROM bus_arrivals ba
              LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
              WHERE DATE(ba.arrival_datetime) BETWEEN ? AND ?
              GROUP BY DATE(ba.arrival_datetime), DAY(ba.arrival_datetime)
              ORDER BY date";
$daily_stats = $db->resultSet($sql_daily, [$start_date, $end_date]);

// Create complete daily array for the month
$days_in_month = date('t', strtotime($start_date));
$daily_data = [];

for ($day = 1; $day <= $days_in_month; $day++) {
    $date = sprintf("%s-%02d", $selected_month, $day);
    $daily_data[$day] = [
        'date' => $date,
        'arrivals' => 0,
        'departures' => 0
    ];
}

// Fill in actual data
foreach ($daily_stats as $stat) {
    $day = (int)$stat['day'];
    $daily_data[$day]['arrivals'] = $stat['arrivals'];
    $daily_data[$day]['departures'] = $stat['departures'];
}

// Get route performance
$sql_routes = "SELECT 
                  r.route_number,
                  r.route_name,
                  r.origin,
                  r.destination,
                  r.distance_km,
                  r.estimated_duration_minutes,
                  COUNT(DISTINCT ba.arrival_id) as total_arrivals,
                  AVG(bd.dwell_time_minutes) as avg_dwell_time,
                  MAX(DATE(ba.arrival_datetime)) as last_arrival
              FROM bus_arrivals ba
              LEFT JOIN routes r ON ba.route_id = r.route_id
              LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
              WHERE DATE(ba.arrival_datetime) BETWEEN ? AND ?
              GROUP BY r.route_id, r.route_number, r.route_name, r.origin, r.destination, r.distance_km, r.estimated_duration_minutes
              ORDER BY total_arrivals DESC";
$route_performance = $db->resultSet($sql_routes, [$start_date, $end_date]);

// Get week-by-week comparison
$sql_weekly = "SELECT 
                  WEEK(ba.arrival_datetime) - WEEK(?) + 1 as week_num,
                  COUNT(DISTINCT ba.arrival_id) as arrivals,
                  COUNT(DISTINCT bd.departure_id) as departures
              FROM bus_arrivals ba
              LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
              WHERE DATE(ba.arrival_datetime) BETWEEN ? AND ?
              GROUP BY WEEK(ba.arrival_datetime)
              ORDER BY week_num";
$weekly_comparison = $db->resultSet($sql_weekly, [$start_date, $start_date, $end_date]);

// Prepare chart data
$chart_days = [];
$chart_arrivals = [];
$chart_departures = [];

foreach ($daily_data as $day => $data) {
    $chart_days[] = $day;
    $chart_arrivals[] = $data['arrivals'];
    $chart_departures[] = $data['departures'];
}

// Calculate percentages
$total_arrivals = $summary['total_arrivals'] ?? 0;
$total_departures = $summary['total_departures'] ?? 0;
$unique_buses = $summary['unique_buses'] ?? 0;
$avg_dwell_time = $summary['avg_dwell_time'] ?? 0;
$active_days = $summary['active_days'] ?? 0;

$daily_avg = $active_days > 0 ? round($total_arrivals / $active_days, 1) : 0;

include '../../includes/header.php';
?>

<div class="row no-print">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-calendar-alt"></i> Monthly Report</h2>
                <p class="text-muted">Complete month analysis and trends</p>
            </div>
            <div>
                <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Month Selection -->
<div class="row no-print mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <label for="month" class="form-label fw-bold">Select Month</label>
                        <input type="month" class="form-control" id="month" name="month" 
                               value="<?php echo $selected_month; ?>" max="<?php echo date('Y-m'); ?>">
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
                    <a href="?month=<?php echo $selected_month; ?>&export=csv" class="btn btn-success">
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
        <h4>Monthly Bus Movement Report</h4>
        <p>Month: <?php echo $month_name; ?></p>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-primary shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-primary"><?php echo $total_arrivals; ?></h3>
                <p class="text-muted mb-0">Total Arrivals</p>
                <small class="text-muted"><?php echo $daily_avg; ?> per day</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-success"><?php echo $total_departures; ?></h3>
                <p class="text-muted mb-0">Total Departures</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-info"><?php echo $unique_buses; ?></h3>
                <p class="text-muted mb-0">Unique Buses</p>
                <small class="text-muted"><?php echo $active_days; ?> active days</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-warning"><?php echo round($avg_dwell_time); ?> min</h3>
                <p class="text-muted mb-0">Avg Dwell Time</p>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Trend Chart -->
<div class="row mb-4 no-print">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-area"></i> Daily Trend - <?php echo $month_name; ?></h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Week-by-Week Comparison -->
<?php if ($weekly_comparison && count($weekly_comparison) > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Week-by-Week Comparison</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Week</th>
                                <th class="text-end">Arrivals</th>
                                <th class="text-end">Departures</th>
                                <th class="text-end">Daily Average</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weekly_comparison as $week): ?>
                            <tr>
                                <td><strong>Week <?php echo $week['week_num']; ?></strong></td>
                                <td class="text-end">
                                    <span class="badge bg-primary"><?php echo $week['arrivals']; ?></span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-success"><?php echo $week['departures']; ?></span>
                                </td>
                                <td class="text-end">
                                    <?php echo round($week['arrivals'] / 7, 1); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Route Performance -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-route"></i> Route Performance (Monthly)</h5>
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
                                    <th>Last Service</th>
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
                                        <?php echo round($route['total_arrivals'] / $active_days, 1); ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo formatDate($route['last_arrival']); ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No data available for selected month</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Daily Statistics Summary Table -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-table"></i> Daily Statistics</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead class="table-light" style="position: sticky; top: 0;">
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th class="text-end">Arrivals</th>
                                <th class="text-end">Departures</th>
                                <th class="text-end">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_data as $day => $data): ?>
                            <?php 
                            $day_name = date('D', strtotime($data['date']));
                            $is_weekend = in_array($day_name, ['Sat', 'Sun']);
                            ?>
                            <tr class="<?php echo $is_weekend ? 'table-secondary' : ''; ?>">
                                <td><?php echo formatDate($data['date']); ?></td>
                                <td><?php echo $day_name; ?></td>
                                <td class="text-end">
                                    <?php if ($data['arrivals'] > 0): ?>
                                        <span class="badge bg-primary"><?php echo $data['arrivals']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($data['departures'] > 0): ?>
                                        <span class="badge bg-success"><?php echo $data['departures']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php 
                                    $net = $data['arrivals'] - $data['departures'];
                                    if ($net != 0) {
                                        $color = $net > 0 ? 'success' : 'danger';
                                        $sign = $net > 0 ? '+' : '';
                                        echo "<span class='text-{$color}'>{$sign}{$net}</span>";
                                    } else {
                                        echo '<span class="text-muted">0</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
// Monthly Trend Chart
const ctx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_days); ?>,
        datasets: [
            {
                label: 'Arrivals',
                data: <?php echo json_encode($chart_arrivals); ?>,
                borderColor: 'rgb(13, 110, 253)',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Departures',
                data: <?php echo json_encode($chart_departures); ?>,
                borderColor: 'rgb(25, 135, 84)',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4,
                fill: true
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
                text: 'Daily Bus Activity - <?php echo $month_name; ?>'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Day of Month'
                }
            }
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
