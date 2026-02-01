<?php
/**
 * Terminal OUT - Bus Departure Recording Module
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Require specific role
requireRole([ROLE_SUPER_ADMIN, ROLE_TERMINAL_OUT]);

$db = new Database();
$page_title = 'Terminal OUT - Record Departure';

// Get all active routes
$sql_routes = "SELECT * FROM routes WHERE is_active = 1 ORDER BY route_number";
$routes = $db->resultSet($sql_routes);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_POST['ajax'] === 'get_buses_in_terminal') {
        // Get buses currently in terminal for selected route
        $route_id = isset($_POST['route_id']) ? intval($_POST['route_id']) : 0;
        
        $sql = "SELECT ba.arrival_id, ba.bus_number, ba.arrival_datetime, 
                       r.route_number, r.route_name,
                       TIMESTAMPDIFF(MINUTE, ba.arrival_datetime, NOW()) as minutes_in_terminal
                FROM bus_arrivals ba
                LEFT JOIN routes r ON ba.route_id = r.route_id
                WHERE ba.status = 'in_terminal'";
        
        if ($route_id > 0) {
            $sql .= " AND ba.route_id = ?";
            $buses = $db->resultSet($sql, [$route_id]);
        } else {
            $sql .= " ORDER BY ba.arrival_datetime ASC";
            $buses = $db->resultSet($sql);
        }
        
        echo json_encode(['success' => true, 'buses' => $buses ?: []]);
        exit;
    }
    
    if ($_POST['ajax'] === 'record_departure') {
        try {
            $arrival_id = intval($_POST['arrival_id']);
            
            if (empty($arrival_id)) {
                throw new Exception('Invalid arrival record.');
            }
            
            // Use stored procedure
            $sql = "CALL sp_record_departure(?, ?)";
            $result = $db->single($sql, [$arrival_id, $_SESSION['user_id']]);
            
            if ($result) {
                // Get bus number for logging
                $bus_sql = "SELECT bus_number FROM bus_arrivals WHERE arrival_id = ?";
                $bus_info = $db->single($bus_sql, [$arrival_id]);
                
                logAudit($db, 'DEPARTURE_RECORDED', 'bus_departures', $result['departure_id'], 
                         "Bus {$bus_info['bus_number']} departure recorded");
                
                echo json_encode([
                    'success' => true,
                    'message' => "Departure recorded successfully! Dwell time: " . 
                                 formatDwellTime($result['dwell_time']),
                    'departure_id' => $result['departure_id'],
                    'dwell_time' => $result['dwell_time']
                ]);
            } else {
                throw new Exception('Failed to record departure.');
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-arrow-right-from-bracket text-warning"></i> Terminal OUT</h2>
                <p class="text-muted">Record bus departures from the terminal</p>
            </div>
            <div>
                <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer"></div>

<!-- Main Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Record Bus Departure</h5>
            </div>
            <div class="card-body">
                <!-- Filter by Route -->
                <div class="mb-4">
                    <label for="filter_route" class="form-label fw-bold">
                        <i class="fas fa-filter"></i> Filter by Route (Optional)
                    </label>
                    <select class="form-select form-select-lg" id="filter_route">
                        <option value="">-- All Routes --</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?php echo $route['route_id']; ?>">
                                Route <?php echo htmlspecialchars($route['route_number']); ?> - 
                                <?php echo htmlspecialchars($route['route_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Buses in Terminal List -->
                <div id="busesListContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading buses in terminal...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Info Panel -->
    <div class="col-lg-4">
        <div class="card shadow mb-3">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Instructions</h6>
            </div>
            <div class="card-body">
                <ol class="small">
                    <li class="mb-2">View all buses currently in terminal</li>
                    <li class="mb-2">Optionally filter by route</li>
                    <li class="mb-2">Click "Record Departure" for the departing bus</li>
                    <li class="mb-2">Dwell time is calculated automatically</li>
                </ol>
            </div>
        </div>
        
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-parking"></i> Status</h6>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <div class="display-6 text-primary" id="busesInTerminal">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <p class="text-muted mb-0 small">Buses in Terminal</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script>
$(document).ready(function() {
    // Load initial data
    loadBusesInTerminal();
    loadTerminalCount();
    
    // Filter change
    $("#filter_route").change(function() {
        loadBusesInTerminal();
    });
    
    // Load buses in terminal
    function loadBusesInTerminal() {
        const routeId = $("#filter_route").val();
        
        $("#busesListContainer").html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading...</p>
            </div>
        `);
        
        $.ajax({
            url: "",
            method: "POST",
            data: { ajax: "get_buses_in_terminal", route_id: routeId },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    displayBuses(response.buses);
                } else {
                    showAlert("error", "Failed to load buses");
                }
            },
            error: function() {
                showAlert("error", "Network error occurred");
            }
        });
    }
    
    // Display buses list
    function displayBuses(buses) {
        if (buses.length === 0) {
            $("#busesListContainer").html(`
                <div class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-4x mb-3"></i>
                    <h5>No Buses in Terminal</h5>
                    <p>All clear! No buses currently waiting for departure.</p>
                </div>
            `);
            return;
        }
        
        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Bus Number</th>
                            <th>Route</th>
                            <th>Arrival Time</th>
                            <th>Duration</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        buses.forEach(function(bus) {
            const arrivalTime = new Date(bus.arrival_datetime);
            const timeStr = arrivalTime.toLocaleTimeString("en-US", { 
                hour: "2-digit", 
                minute: "2-digit" 
            });
            
            const minutes = parseInt(bus.minutes_in_terminal);
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            const duration = hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
            
            // Color code by duration
            let durationClass = "text-success";
            if (minutes > 60) durationClass = "text-warning";
            if (minutes > 120) durationClass = "text-danger";
            
            html += `
                <tr>
                    <td><strong>${bus.bus_number}</strong></td>
                    <td>${bus.route_number}</td>
                    <td>${timeStr}</td>
                    <td><span class="badge bg-info">${duration}</span></td>
                    <td>
                        <button class="btn btn-warning btn-sm btnDeparture" data-arrival-id="${bus.arrival_id}" data-bus-number="${bus.bus_number}">
                            <i class="fas fa-arrow-right"></i> Depart
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        $("#busesListContainer").html(html);
        
        // Attach click handlers
        $(".btnDeparture").click(function() {
            const arrivalId = $(this).data("arrival-id");
            const busNumber = $(this).data("bus-number");
            recordDeparture(arrivalId, busNumber, $(this));
        });
    }
    
    // Record departure
    function recordDeparture(arrivalId, busNumber, button) {
        if (!confirm(`Record departure for bus ${busNumber}?`)) {
            return;
        }
        
        button.prop("disabled", true).html("<span class=\"spinner-border spinner-border-sm\"></span> Processing...");
        
        $.ajax({
            url: "",
            method: "POST",
            data: {
                ajax: "record_departure",
                arrival_id: arrivalId
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    showAlert("success", response.message);
                    loadBusesInTerminal();
                    loadTerminalCount();
                } else {
                    showAlert("error", response.message);
                    button.prop("disabled", false).html("<i class=\"fas fa-arrow-right\"></i> Depart");
                }
            },
            error: function() {
                showAlert("error", "Network error occurred");
                button.prop("disabled", false).html("<i class=\"fas fa-arrow-right\"></i> Depart");
            }
        });
    }
    
    function loadTerminalCount() {
        $.get("' . SITE_URL . '/api/terminal_count.php", function(data) {
            $("#busesInTerminal").html(data.count || 0);
        }).fail(function() {
            $("#busesInTerminal").html("?");
        });
    }
    
    function showAlert(type, message) {
        const alertClass = type === "success" ? "alert-success" : "alert-danger";
        const icon = type === "success" ? "check-circle" : "exclamation-circle";
        
        const alert = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas fa-${icon}"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $("#alertContainer").html(alert);
        $("html, body").animate({ scrollTop: 0 }, 300);
        
        setTimeout(function() {
            $(".alert").fadeOut();
        }, 5000);
    }
    
    // Auto refresh every 30 seconds
    setInterval(function() {
        loadBusesInTerminal();
        loadTerminalCount();
    }, 30000);
});
</script>
';

include '../../includes/footer.php';
?>
