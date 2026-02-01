<?php
/**
 * Yearly Report
 * Complete year analysis with monthly breakdown and trends
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Require appropriate role
requireRole([ROLE_SUPER_ADMIN, ROLE_REPORT_VIEWER]);

$db = new Database();
$page_title = 'Yearly Report';

// Get selected year (default to current year)
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Calculate start and end dates
$start_date = $selected_year . '-01-01';
$end_date = $selected_year . '-12-31';

// Export to CSV if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql = "SELECT 
                MONTH(ba.arrival_datetime) as month,
                COUNT(DISTINCT ba.arrival_id) as arrivals,
                COUNT(DISTINCT bd.departure_id) as departures,
                AVG(bd.dwell_time_minutes) as avg_dwell
            FROM bus_arrivals ba
            LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
            WHERE YEAR(ba.arrival_datetime) = ?
            GROUP BY MONTH(ba.arrival_datetime)
            ORDER BY month";
    
    $data = $db->resultSet($sql, [$selected_year]);
    
    $headers = ['Month', 'Arrivals', 'Departures', 'Avg Dwell Time (min)'];
    
    exportToCSV($data, 'yearly_report_' . $selected_year, $headers);
}

// Get yearly summary statistics
$sql_summary = "SELECT 
                    COUNT(DISTINCT ba.arrival_id) as total_arrivals,
                    COUNT(DISTINCT bd.departure_id) as total_departures,
                    COUNT(DISTINCT ba.bus_number) as unique_buses,
                    COUNT(DISTINCT ba.route_id) as routes_served,
                    AVG(bd.dwell_time_minutes) as avg_dwell_time,
                    COUNT(DISTINCT DATE(ba.arrival_datetime)) as active_days
                FROM bus_arrivals ba
                LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
                WHERE YEAR(ba.arrival_datetime) = ?";
$summary = $db->single($sql_summary, [$selected_year]);

// Get monthly statistics
$monthly_data = [];
for ($month = 1; $month <= 12; $month++) {
    $sql_monthly = "SELECT 
                        MONTH(ba.arrival_datetime) as month,
                        COUNT(DISTINCT ba.arrival_id) as arrivals,
                        COUNT(DISTINCT bd.departure_id) as departures,
                        AVG(bd.dwell_time_minutes) as avg_dwell_time,
                        COUNT(DISTINCT DATE(ba.arrival_datetime)) as active_days
                    FROM bus_arrivals ba
                    LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
                    WHERE YEAR(ba.arrival_datetime) = ? AND MONTH(ba.arrival_datetime) = ?
                    GROUP BY MONTH(ba.arrival_datetime)";
    
    $month_stats = $db->single($sql_monthly, [$selected_year, $month]);
    
    $monthly_data[$month] = [
        'month' => $month,
        'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
        'arrivals' => $month_stats['arrivals'] ?? 0,
        'departures' => $month_stats['departures'] ?? 0,
        'avg_dwell_time' => $month_stats['avg_dwell_time'] ?? 0,
        'active_days' => $month_stats['active_days'] ?? 0
    ];
}

// Get quarterly breakdown
$quarters = [];
for ($q = 1; $q <= 4; $q++) {
    $start_month = ($q - 1) * 3 + 1;
    $end_month = $q * 3;
    
    $sql_quarter = "SELECT 
                        COUNT(DISTINCT ba.arrival_id) as arrivals,
                        COUNT(DISTINCT bd.departure_id) as departures,
                        AVG(bd.dwell_time_minutes) as avg_dwell
                    FROM bus_arrivals ba
                    LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
                    WHERE YEAR(ba.arrival_datetime) = ? 
                    AND MONTH(ba.arrival_datetime) BETWEEN ? AND ?";
    
    $quarter_stats = $db->single($sql_quarter, [$selected_year, $start_month, $end_month]);
    
    $quarters[$q] = [
        'quarter' => 'Q' . $q,
        'months' => date('M', mktime(0, 0, 0, $start_month, 1)) . ' - ' . date('M', mktime(0, 0, 0, $end_month, 1)),
        'arrivals' => $quarter_stats['arrivals'] ?? 0,
        'departures' => $quarter_stats['departures'] ?? 0,
        'avg_dwell' => $quarter_stats['avg_dwell'] ?? 0
    ];
}

// Get route performance for the year
$sql_routes = "SELECT 
                  r.route_number,
                  r.route_name,
                  r.origin,
                  r.destination,
                  r.distance_km,
                  r.estimated_duration_minutes,
                  COUNT(DISTINCT ba.arrival_id) as total_arrivals,
                  AVG(bd.dwell_time_minutes) as avg_dwell_time,
                  MAX(DATE(ba.arrival_datetime)) as last_arrival,
                  MIN(DATE(ba.arrival_datetime)) as first_arrival
              FROM bus_arrivals ba
              LEFT JOIN routes r ON ba.route_id = r.route_id
              LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
              WHERE YEAR(ba.arrival_datetime) = ?
              GROUP BY r.route_id, r.route_number, r.route_name, r.origin, r.destination, r.distance_km, r.estimated_duration_minutes
              ORDER BY total_arrivals DESC";
$route_performance = $db->resultSet($sql_routes, [$selected_year]);

// Find busiest month
$busiest_month = $monthly_data[1];
foreach ($monthly_data as $month_data) {
    if ($month_data['arrivals'] > $busiest_month['arrivals']) {
        $busiest_month = $month_data;
    }
}

// Prepare chart data
$chart_months = [];
$chart_arrivals = [];
$chart_departures = [];

foreach ($monthly_data as $data) {
    $chart_months[] = $data['month_name'];
    $chart_arrivals[] = $data['arrivals'];
    $chart_departures[] = $data['departures'];
}

// Calculate totals and averages
$total_arrivals = $summary['total_arrivals'] ?? 0;
$total_departures = $summary['total_departures'] ?? 0;
$unique_buses = $summary['unique_buses'] ?? 0;
$routes_served = $summary['routes_served'] ?? 0;
$avg_dwell_time = $summary['avg_dwell_time'] ?? 0;
$active_days = $summary['active_days'] ?? 0;

$daily_avg = $active_days > 0 ? round($total_arrivals / $active_days, 1) : 0;
$monthly_avg = round($total_arrivals / 12, 1);

include '../../includes/header.php';
?>

<div class="row no-print">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-calendar"></i> Yearly Report</h2>
                <p class="text-muted">Annual bus movement analysis and trends</p>
            </div>
            <div>
                <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Year Selection -->
<div class="row no-print mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <label for="year" class="form-label fw-bold">Select Year</label>
                        <select class="form-select" id="year" name="year">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $selected_year ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
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
                    <a href="?year=<?php echo $selected_year; ?>&export=csv" class="btn btn-success">
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
        <h4>Annual Bus Movement Report</h4>
        <p>Year: <?php echo $selected_year; ?></p>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card border-primary shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-primary"><?php echo number_format($total_arrivals); ?></h3>
                <p class="text-muted mb-0">Total Arrivals</p>
                <small class="text-muted"><?php echo $daily_avg; ?> per day</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card border-success shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-success"><?php echo number_format($total_departures); ?></h3>
                <p class="text-muted mb-0">Total Departures</p>
                <small class="text-muted"><?php echo $monthly_avg; ?> per month</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card border-info shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-info"><?php echo $unique_buses; ?></h3>
                <p class="text-muted mb-0">Unique Buses</p>
                <small class="text-muted"><?php echo $routes_served; ?> routes</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card border-warning shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-warning"><?php echo $busiest_month['month_name']; ?></h3>
                <p class="text-muted mb-0">Busiest Month</p>
                <small class="text-muted"><?php echo number_format($busiest_month['arrivals']); ?> arrivals</small>
            </div>
        </div>
    </div>
</div>

<!-- Annual Trend Chart -->
<div class="row mb-4 no-print">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Annual Trend - <?php echo $selected_year; ?></h5>
            </div>
            <div class="card-body">
                <canvas id="yearlyChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Quarterly Comparison -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Quarterly Performance</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Quarter</th>
                                <th>Period</th>
                                <th class="text-end">Arrivals</th>
                                <th class="text-end">Departures</th>
                                <th class="text-end">Avg Dwell Time</th>
                                <th class="text-end">Monthly Average</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quarters as $quarter): ?>
                            <tr>
                                <td><strong><?php echo $quarter['quarter']; ?></strong></td>
                                <td><?php echo $quarter['months']; ?></td>
                                <td class="text-end">
                                    <span class="badge bg-primary"><?php echo number_format($quarter['arrivals']); ?></span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-success"><?php echo number_format($quarter['departures']); ?></span>
                                </td>
                                <td class="text-end">
                                    <?php if ($quarter['avg_dwell'] > 0): ?>
                                        <span class="badge bg-info"><?php echo round($quarter['avg_dwell']); ?> min</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo number_format(round($quarter['arrivals'] / 3, 1)); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2">Annual Total</th>
                                <th class="text-end"><?php echo number_format($total_arrivals); ?></th>
                                <th class="text-end"><?php echo number_format($total_departures); ?></th>
                                <th class="text-end"><?php echo round($avg_dwell_time); ?> min</th>
                                <th class="text-end"><?php echo number_format($monthly_avg); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Breakdown -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Monthly Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Arrivals</th>
                                <th class="text-end">Departures</th>
                                <th class="text-end">Net Change</th>
                                <th class="text-end">Avg Dwell Time</th>
                                <th class="text-end">Active Days</th>
                                <th class="text-end">Daily Average</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_data as $data): ?>
                            <tr>
                                <td><strong><?php echo $data['month_name']; ?></strong></td>
                                <td class="text-end">
                                    <?php if ($data['arrivals'] > 0): ?>
                                        <span class="badge bg-primary"><?php echo number_format($data['arrivals']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($data['departures'] > 0): ?>
                                        <span class="badge bg-success"><?php echo number_format($data['departures']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php 
                                    $net = $data['arrivals'] - $data['departures'];
                                    if ($net > 0) {
                                        echo '<span class="text-success">+' . number_format($net) . '</span>';
                                    } elseif ($net < 0) {
                                        echo '<span class="text-danger">' . number_format($net) . '</span>';
                                    } else {
                                        echo '<span class="text-muted">0</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($data['avg_dwell_time'] > 0): ?>
                                        <span class="badge bg-info"><?php echo round($data['avg_dwell_time']); ?> min</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?php echo $data['active_days']; ?></td>
                                <td class="text-end">
                                    <?php echo $data['active_days'] > 0 ? number_format(round($data['arrivals'] / $data['active_days'], 1)) : '0'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Total / Average</th>
                                <th class="text-end"><?php echo number_format($total_arrivals); ?></th>
                                <th class="text-end"><?php echo number_format($total_departures); ?></th>
                                <th class="text-end"><?php echo number_format($total_arrivals - $total_departures); ?></th>
                                <th class="text-end"><?php echo round($avg_dwell_time); ?> min</th>
                                <th class="text-end"><?php echo $active_days; ?></th>
                                <th class="text-end"><?php echo $daily_avg; ?></th>
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
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-route"></i> Annual Route Performance</h5>
            </div>
            <div class="card-body">
                <?php if ($route_performance && count($route_performance) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Route</th>
                                    <th>Route Name</th>
                                    <th>Origin → Destination</th>
                                    <th class="text-end">Distance</th>
                                    <th class="text-end">Total Arrivals</th>
                                    <th class="text-end">Avg Dwell</th>
                                    <th class="text-end">Monthly Avg</th>
                                    <th>Service Period</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($route_performance as $route): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($route['route_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($route['route_name']); ?></td>
                                    <td><small><?php echo htmlspecialchars($route['origin'] ?? '-') . ' → ' . htmlspecialchars($route['destination'] ?? '-'); ?></small></td>
                                    <td class="text-end"><small><?php echo $route['distance_km'] ? number_format($route['distance_km'], 1) . ' km' : '-'; ?></small></td>
                                    <td class="text-end">
                                        <span class="badge bg-primary"><?php echo number_format($route['total_arrivals']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($route['avg_dwell_time']): ?>
                                            <span class="badge bg-info"><?php echo round($route['avg_dwell_time']); ?> min</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo number_format(round($route['total_arrivals'] / 12, 1)); ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('M', strtotime($route['first_arrival'])) . ' - ' . date('M', strtotime($route['last_arrival'])); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No data available for year <?php echo $selected_year; ?></p>
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
// Annual Trend Chart
const ctx = document.getElementById('yearlyChart').getContext('2d');
const yearlyChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_months); ?>,
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
                text: 'Monthly Trend - Year <?php echo $selected_year; ?>'
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
