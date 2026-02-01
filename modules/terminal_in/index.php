<?php
/**
 * Terminal IN - Bus Arrival Recording Module
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Require specific role
requireRole([ROLE_SUPER_ADMIN, ROLE_TERMINAL_IN]);

$db = new Database();
$page_title = 'Terminal IN - Record Arrival';

// Get all active routes
$sql_routes = "SELECT * FROM routes WHERE is_active = 1 ORDER BY route_number";
$routes = $db->resultSet($sql_routes);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_POST['ajax'] === 'get_buses') {
        // Get buses for selected route
        $route_id = intval($_POST['route_id']);
        $sql = "SELECT * FROM buses WHERE route_id = ? AND is_active = 1 ORDER BY bus_number";
        $buses = $db->resultSet($sql, [$route_id]);
        echo json_encode(['success' => true, 'buses' => $buses ?: []]);
        exit;
    }
    
    if ($_POST['ajax'] === 'record_arrival') {
        try {
            $bus_number = strtoupper(trim($_POST['bus_number']));
            $route_id = intval($_POST['route_id']);
            $entry_method = $_POST['entry_method'];
            $operator_name = sanitize($_POST['operator_name'] ?? '');
            $remarks = sanitize($_POST['remarks'] ?? '');
            
            // Validate
            if (empty($bus_number) || empty($route_id)) {
                throw new Exception('Bus number and route are required.');
            }
            
            // Check if bus is already in terminal
            if (isBusInTerminal($db, $bus_number)) {
                throw new Exception('This bus is already in the terminal.');
            }
            
            // Use stored procedure
            $sql = "CALL sp_record_arrival(?, ?, ?, ?, ?, ?)";
            $result = $db->single($sql, [
                $bus_number,
                $route_id,
                $entry_method,
                $operator_name,
                $remarks,
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                logAudit($db, 'ARRIVAL_RECORDED', 'bus_arrivals', $result['arrival_id'], 
                         "Bus {$bus_number} arrival recorded");
                
                echo json_encode([
                    'success' => true,
                    'message' => "Bus {$bus_number} arrival recorded successfully!",
                    'arrival_id' => $result['arrival_id']
                ]);
            } else {
                throw new Exception('Failed to record arrival.');
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
                <h2><i class="fas fa-arrow-right-to-bracket text-success"></i> Terminal IN</h2>
                <p class="text-muted">Record bus arrivals at the terminal</p>
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

<!-- Main Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Record Bus Arrival</h5>
            </div>
            <div class="card-body">
                <!-- Route Selection -->
                <div class="mb-4">
                    <label for="route_id" class="form-label fw-bold">
                        <i class="fas fa-route"></i> Select Route *
                    </label>
                    <select class="form-select form-select-lg" id="route_id" required>
                        <option value="">-- Select Route --</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?php echo $route['route_id']; ?>">
                                Route <?php echo htmlspecialchars($route['route_number']); ?> - 
                                <?php echo htmlspecialchars($route['route_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Entry Method -->
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-list"></i> Entry Method *
                    </label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="entry_method" id="entry_registered" 
                               value="registered" checked>
                        <label class="btn btn-outline-primary" for="entry_registered">
                            <i class="fas fa-clipboard-check"></i> Registered Bus
                        </label>
                        
                        <input type="radio" class="btn-check" name="entry_method" id="entry_manual" 
                               value="manual">
                        <label class="btn btn-outline-warning" for="entry_manual">
                            <i class="fas fa-keyboard"></i> Manual Entry
                        </label>
                    </div>
                </div>
                
                <!-- Registered Bus Selection (shown by default) -->
                <div id="registered_section" class="mb-4">
                    <label for="registered_bus" class="form-label fw-bold">
                        <i class="fas fa-bus"></i> Select Bus *
                    </label>
                    <select class="form-select form-select-lg" id="registered_bus" disabled>
                        <option value="">-- First select a route --</option>
                    </select>
                    <small class="text-muted">Select a route first to see registered buses</small>
                </div>
                
                <!-- Manual Entry Section (hidden by default) -->
                <div id="manual_section" class="mb-4" style="display: none;">
                    <label for="manual_bus_number" class="form-label fw-bold">
                        <i class="fas fa-bus"></i> Bus Number / License Plate *
                    </label>
                    <input type="text" class="form-control form-control-lg" id="manual_bus_number" 
                           placeholder="e.g., WP-CAA-1234" style="text-transform: uppercase;">
                    
                    <div class="mt-3">
                        <label for="operator_name" class="form-label">
                            <i class="fas fa-building"></i> Operator Name (Optional)
                        </label>
                        <input type="text" class="form-control" id="operator_name" 
                               placeholder="e.g., SLTB Western Province">
                    </div>
                </div>
                
                <!-- Remarks -->
                <div class="mb-4">
                    <label for="remarks" class="form-label">
                        <i class="fas fa-comment"></i> Remarks (Optional)
                    </label>
                    <textarea class="form-control" id="remarks" rows="2" 
                              placeholder="Any special notes about this arrival..."></textarea>
                </div>
                
                <!-- Submit Button -->
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success btn-lg" id="btnSubmit">
                        <i class="fas fa-check-circle"></i> Record Arrival
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnReset">
                        <i class="fas fa-redo"></i> Reset Form
                    </button>
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
                    <li class="mb-2">Select the bus route</li>
                    <li class="mb-2">Choose entry method:
                        <ul>
                            <li><strong>Registered Bus:</strong> Select from list</li>
                            <li><strong>Manual Entry:</strong> Type bus number</li>
                        </ul>
                    </li>
                    <li class="mb-2">Add remarks if needed</li>
                    <li class="mb-2">Click "Record Arrival"</li>
                </ol>
            </div>
        </div>
        
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-parking"></i> Buses in Terminal</h6>
            </div>
            <div class="card-body">
                <div class="display-6 text-center text-primary" id="busesInTerminal">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <p class="text-center text-muted mb-0 small">Currently present</p>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script>
$(document).ready(function() {
    // Load buses in terminal count
    loadTerminalCount();
    
    // Entry method toggle
    $("input[name=entry_method]").change(function() {
        if ($(this).val() === "registered") {
            $("#registered_section").show();
            $("#manual_section").hide();
            $("#manual_bus_number").val("");
        } else {
            $("#registered_section").hide();
            $("#manual_section").show();
            $("#registered_bus").val("");
        }
    });
    
    // Route selection change
    $("#route_id").change(function() {
        const routeId = $(this).val();
        if (routeId && $("input[name=entry_method]:checked").val() === "registered") {
            loadBuses(routeId);
        }
    });
    
    // Load buses for route
    function loadBuses(routeId) {
        $("#registered_bus").html("<option value=\"\">Loading...</option>").prop("disabled", true);
        
        $.ajax({
            url: "",
            method: "POST",
            data: { ajax: "get_buses", route_id: routeId },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    let options = "<option value=\"\">-- Select Bus --</option>";
                    response.buses.forEach(function(bus) {
                        options += `<option value="${bus.bus_number}">${bus.bus_number}</option>`;
                    });
                    $("#registered_bus").html(options).prop("disabled", false);
                    
                    if (response.buses.length === 0) {
                        $("#registered_bus").html("<option value=\"\">No registered buses for this route</option>");
                    }
                } else {
                    showAlert("error", "Failed to load buses");
                }
            },
            error: function() {
                showAlert("error", "Network error occurred");
                $("#registered_bus").html("<option value=\"\">Error loading buses</option>");
            }
        });
    }
    
    // Submit form
    $("#btnSubmit").click(function() {
        const routeId = $("#route_id").val();
        const entryMethod = $("input[name=entry_method]:checked").val();
        const remarks = $("#remarks").val();
        
        let busNumber = "";
        let operatorName = "";
        
        if (!routeId) {
            showAlert("error", "Please select a route");
            return;
        }
        
        if (entryMethod === "registered") {
            busNumber = $("#registered_bus").val();
            if (!busNumber) {
                showAlert("error", "Please select a bus");
                return;
            }
        } else {
            busNumber = $("#manual_bus_number").val().trim().toUpperCase();
            operatorName = $("#operator_name").val().trim();
            
            if (!busNumber) {
                showAlert("error", "Please enter bus number");
                return;
            }
        }
        
        // Disable button
        $(this).prop("disabled", true).html("<span class=\"spinner-border spinner-border-sm\"></span> Recording...");
        
        // Submit
        $.ajax({
            url: "",
            method: "POST",
            data: {
                ajax: "record_arrival",
                bus_number: busNumber,
                route_id: routeId,
                entry_method: entryMethod,
                operator_name: operatorName,
                remarks: remarks
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    showAlert("success", response.message);
                    resetForm();
                    loadTerminalCount();
                } else {
                    showAlert("error", response.message);
                }
                $("#btnSubmit").prop("disabled", false).html("<i class=\"fas fa-check-circle\"></i> Record Arrival");
            },
            error: function() {
                showAlert("error", "Network error occurred");
                $("#btnSubmit").prop("disabled", false).html("<i class=\"fas fa-check-circle\"></i> Record Arrival");
            }
        });
    });
    
    // Reset form
    $("#btnReset").click(function() {
        resetForm();
    });
    
    function resetForm() {
        $("#route_id").val("");
        $("#registered_bus").html("<option value=\"\">-- First select a route --</option>").prop("disabled", true);
        $("#manual_bus_number").val("");
        $("#operator_name").val("");
        $("#remarks").val("");
        $("#entry_registered").prop("checked", true);
        $("#registered_section").show();
        $("#manual_section").hide();
    }
    
    function loadTerminalCount() {
        // Refresh page data (simple reload for count)
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
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $(".alert").fadeOut();
        }, 5000);
    }
});
</script>
';

include '../../includes/footer.php';
?>
