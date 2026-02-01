<?php
/**
 * Hourly Report
 * Hour-by-hour breakdown of bus movements for a selected date
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Require appropriate role
requireRole([ROLE_SUPER_ADMIN, ROLE_REPORT_VIEWER]);

$db = new Database();
$page_title = 'Hourly Report';

// Get selected date (default to today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Export to CSV if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql = "SELECT 
                HOUR(ba.arrival_datetime) as hour,
                COUNT(DISTINCT ba.arrival_id) as arrivals,
                COUNT(DISTINCT bd.departure_id) as departures
            FROM bus_arrivals ba
            LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id 
                AND DATE(bd.departure_datetime) = ?
            WHERE DATE(ba.arrival_datetime) = ?
            GROUP BY HOUR(ba.arrival_datetime)
            ORDER BY hour";
    
    $data = $db->resultSet($sql, [$selected_date, $selected_date]);
    
    $headers = ['Hour', 'Arrivals', 'Departures'];
    
    exportToCSV($data, 'hourly_report_' . $selected_date, $headers);
}

// Get hourly breakdown for the entire day (0-23 hours)
$hourly_data = [];
for ($hour = 0; $hour < 24; $hour++) {
    $hourly_data[$hour] = [
        'hour' => $hour,
        'arrivals' => 0,
        'departures' => 0,
        'buses_in_terminal' => 0
    ];
}

// Get arrival counts by hour
$sql_arrivals = "SELECT 
                    HOUR(arrival_datetime) as hour,
                    COUNT(*) as count
                FROM bus_arrivals
                WHERE DATE(arrival_datetime) = ?
                GROUP BY HOUR(arrival_datetime)";
$arrivals = $db->resultSet($sql_arrivals, [$selected_date]);

foreach ($arrivals as $arr) {
    $hourly_data[$arr['hour']]['arrivals'] = $arr['count'];
}

// Get departure counts by hour
$sql_departures = "SELECT 
                      HOUR(departure_datetime) as hour,
                      COUNT(*) as count
                  FROM bus_departures
                  WHERE DATE(departure_datetime) = ?
                  GROUP BY HOUR(departure_datetime)";
$departures = $db->resultSet($sql_departures, [$selected_date]);

foreach ($departures as $dep) {
    $hourly_data[$dep['hour']]['departures'] = $dep['count'];
}

// Calculate buses in terminal at end of each hour
$running_total = 0;
foreach ($hourly_data as $hour => $data) {
    $running_total += $data['arrivals'] - $data['departures'];
    $hourly_data[$hour]['buses_in_terminal'] = max(0, $running_total);
}

// Get summary statistics
$sql_summary = "SELECT 
                    COUNT(DISTINCT ba.arrival_id) as total_arrivals,
                    COUNT(DISTINCT bd.departure_id) as total_departures,
                    HOUR(ba.arrival_datetime) as peak_hour,
                    COUNT(*) as peak_count
                FROM bus_arrivals ba
                LEFT JOIN bus_departures bd ON ba.arrival_id = bd.arrival_id
                WHERE DATE(ba.arrival_datetime) = ?
                GROUP BY HOUR(ba.arrival_datetime)
                ORDER BY peak_count DESC
                LIMIT 1";
$summary = $db->single($sql_summary, [$selected_date]);

$total_arrivals = array_sum(array_column($hourly_data, 'arrivals'));
$total_departures = array_sum(array_column($hourly_data, 'departures'));
$peak_hour = $summary['peak_hour'] ?? 0;
$peak_count = $summary['peak_count'] ?? 0;

// Prepare chart data
$chart_hours = [];
$chart_arrivals = [];
$chart_departures = [];
$chart_terminal = [];

foreach ($hourly_data as $data) {
    $hour_label = sprintf("%02d:00", $data['hour']);
    $chart_hours[] = $hour_label;
    $chart_arrivals[] = $data['arrivals'];
    $chart_departures[] = $data['departures'];
    $chart_terminal[] = $data['buses_in_terminal'];
}

include '../../includes/header.php';
?>

<div class="row no-print">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-clock"></i> Hourly Report</h2>
                <p class="text-muted">Hour-by-hour breakdown of bus movements</p>
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
        <h4>Hourly Bus Movement Report</h4>
        <p>Date: <?php echo formatDate($selected_date); ?></p>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-primary shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-primary"><?php echo $total_arrivals; ?></h3>
                <p class="text-muted mb-0">Total Arrivals</p>
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
        <div class="card border-warning shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-warning"><?php echo sprintf("%02d:00", $peak_hour); ?></h3>
                <p class="text-muted mb-0">Peak Hour</p>
                <small class="text-muted"><?php echo $peak_count; ?> arrivals</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-info"><?php echo max($chart_terminal); ?></h3>
                <p class="text-muted mb-0">Max in Terminal</p>
            </div>
        </div>
    </div>
</div>

<!-- Hourly Chart -->
<div class="row mb-4 no-print">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Hourly Activity Chart</h5>
            </div>
            <div class="card-body">
                <canvas id="hourlyChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Hourly Breakdown Table -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-table"></i> Hour-by-Hour Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Time Period</th>
                                <th class="text-end">Arrivals</th>
                                <th class="text-end">Departures</th>
                                <th class="text-end">Net Change</th>
                                <th class="text-end">Buses in Terminal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hourly_data as $data): ?>
                            <tr>
                                <td>
                                    <strong><?php echo sprintf("%02d:00 - %02d:59", $data['hour'], $data['hour']); ?></strong>
                                </td>
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
                                    <span class="badge bg-info"><?php echo $data['buses_in_terminal']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th class="text-end"><strong><?php echo $total_arrivals; ?></strong></th>
                                <th class="text-end"><strong><?php echo $total_departures; ?></strong></th>
                                <th class="text-end">
                                    <strong><?php echo ($total_arrivals - $total_departures); ?></strong>
                                </th>
                                <th class="text-end">-</th>
                            </tr>
                        </tfoot>
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
// Hourly Activity Chart
const ctx = document.getElementById('hourlyChart').getContext('2d');
const hourlyChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_hours); ?>,
        datasets: [
            {
                label: 'Arrivals',
                data: <?php echo json_encode($chart_arrivals); ?>,
                borderColor: 'rgb(13, 110, 253)',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4
            },
            {
                label: 'Departures',
                data: <?php echo json_encode($chart_departures); ?>,
                borderColor: 'rgb(25, 135, 84)',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4
            },
            {
                label: 'Buses in Terminal',
                data: <?php echo json_encode($chart_terminal); ?>,
                borderColor: 'rgb(255, 193, 7)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
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
                text: 'Hourly Bus Activity - <?php echo formatDate($selected_date); ?>'
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
