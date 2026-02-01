<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Require login and super admin role
requireLogin();
if (!hasRole(ROLE_SUPER_ADMIN)) {
    header('Location: ' . SITE_URL . '/dashboard.php?error=access_denied');
    exit;
}

$page_title = 'Bus Registration';
$db = new Database();

// Fetch all buses with route information
$buses = $db->query("
    SELECT 
        b.bus_id,
        b.bus_number,
        b.route_id,
        r.route_name,
        b.operator_name,
        b.operator_contact,
        b.bus_capacity,
        b.registration_number,
        b.is_active,
        b.created_at
    FROM buses b
    LEFT JOIN routes r ON b.route_id = r.route_id
    ORDER BY b.bus_number ASC
")->fetchAll();

// Fetch all active routes for dropdown
$routes = $db->query("
    SELECT route_id, route_name, origin, destination 
    FROM routes 
    WHERE is_active = 1 
    ORDER BY route_name ASC
")->fetchAll();

include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-bus"></i> Bus Registration</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBusModal">
                    <i class="fas fa-plus"></i> Register New Bus
                </button>
            </div>

            <!-- Buses Table -->
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> All Registered Buses</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="busesTable">
                            <thead>
                                <tr>
                                    <th>License Plate</th>
                                    <th>Assigned Route</th>
                                    <th>Operator Name</th>
                                    <th>Contact</th>
                                    <th>Capacity</th>
                                    <th>Reg. Number</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($buses)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No buses registered</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($buses as $bus): ?>
                                        <tr data-bus-id="<?php echo $bus['bus_id']; ?>">
                                            <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                            <td>
                                                <?php if ($bus['route_name']): ?>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($bus['route_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($bus['operator_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($bus['operator_contact'] ?? '-'); ?></td>
                                            <td><?php echo $bus['bus_capacity'] ?? '-'; ?></td>
                                            <td><?php echo htmlspecialchars($bus['registration_number'] ?? '-'); ?></td>
                                            <td>
                                                <?php if ($bus['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($bus['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info edit-bus" 
                                                        data-bus-id="<?php echo $bus['bus_id']; ?>"
                                                        data-bus-number="<?php echo htmlspecialchars($bus['bus_number']); ?>"
                                                        data-route-id="<?php echo $bus['route_id'] ?? ''; ?>"
                                                        data-operator-name="<?php echo htmlspecialchars($bus['operator_name'] ?? ''); ?>"
                                                        data-operator-contact="<?php echo htmlspecialchars($bus['operator_contact'] ?? ''); ?>"
                                                        data-capacity="<?php echo $bus['bus_capacity'] ?? ''; ?>"
                                                        data-registration="<?php echo htmlspecialchars($bus['registration_number'] ?? ''); ?>"
                                                        data-active="<?php echo $bus['is_active']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($bus['is_active']): ?>
                                                    <button class="btn btn-sm btn-warning toggle-status" 
                                                            data-bus-id="<?php echo $bus['bus_id']; ?>"
                                                            data-action="deactivate">
                                                        <i class="fas fa-toggle-off"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-success toggle-status" 
                                                            data-bus-id="<?php echo $bus['bus_id']; ?>"
                                                            data-action="activate">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-danger delete-bus" 
                                                        data-bus-id="<?php echo $bus['bus_id']; ?>"
                                                        data-bus-number="<?php echo htmlspecialchars($bus['bus_number']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Bus Modal -->
<div class="modal fade" id="addBusModal" tabindex="-1" aria-labelledby="addBusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addBusModalLabel">
                    <i class="fas fa-plus-circle"></i> Register New Bus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addBusForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bus_number" class="form-label">License Plate Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="bus_number" name="bus_number" 
                                   placeholder="e.g., WP ABC-1234" required>
                            <small class="text-muted">Enter the bus license plate number</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="route_id" class="form-label">Assigned Route</label>
                            <select class="form-select" id="route_id" name="route_id">
                                <option value="">-- Select Route (Optional) --</option>
                                <?php foreach ($routes as $route): ?>
                                    <option value="<?php echo $route['route_id']; ?>">
                                        <?php echo htmlspecialchars($route['route_name']); ?> 
                                        (<?php echo htmlspecialchars($route['origin'] . ' → ' . $route['destination']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="operator_name" class="form-label">Operator Name</label>
                            <input type="text" class="form-control" id="operator_name" name="operator_name" 
                                   placeholder="e.g., John Perera">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="operator_contact" class="form-label">Operator Contact</label>
                            <input type="text" class="form-control" id="operator_contact" name="operator_contact" 
                                   placeholder="e.g., 0771234567">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bus_capacity" class="form-label">Passenger Capacity</label>
                            <input type="number" class="form-control" id="bus_capacity" name="bus_capacity" 
                                   min="1" placeholder="e.g., 50">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="registration_number" class="form-label">Registration Number</label>
                            <input type="text" class="form-control text-uppercase" id="registration_number" 
                                   name="registration_number" placeholder="e.g., NB-1234">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active (Bus is operational)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Register Bus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Bus Modal -->
<div class="modal fade" id="editBusModal" tabindex="-1" aria-labelledby="editBusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="editBusModalLabel">
                    <i class="fas fa-edit"></i> Edit Bus Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editBusForm">
                <input type="hidden" id="edit_bus_id" name="bus_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_bus_number" class="form-label">License Plate Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="edit_bus_number" 
                                   name="bus_number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_route_id" class="form-label">Assigned Route</label>
                            <select class="form-select" id="edit_route_id" name="route_id">
                                <option value="">-- Select Route (Optional) --</option>
                                <?php foreach ($routes as $route): ?>
                                    <option value="<?php echo $route['route_id']; ?>">
                                        <?php echo htmlspecialchars($route['route_name']); ?>
                                        (<?php echo htmlspecialchars($route['origin'] . ' → ' . $route['destination']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_operator_name" class="form-label">Operator Name</label>
                            <input type="text" class="form-control" id="edit_operator_name" name="operator_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_operator_contact" class="form-label">Operator Contact</label>
                            <input type="text" class="form-control" id="edit_operator_contact" name="operator_contact">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_bus_capacity" class="form-label">Passenger Capacity</label>
                            <input type="number" class="form-control" id="edit_bus_capacity" name="bus_capacity" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_registration_number" class="form-label">Registration Number</label>
                            <input type="text" class="form-control text-uppercase" id="edit_registration_number" 
                                   name="registration_number">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">
                                Active (Bus is operational)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save"></i> Update Bus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).ready(function() {
    // Add Bus
    $('#addBusForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'add',
            bus_number: $('#bus_number').val().toUpperCase(),
            route_id: $('#route_id').val() || null,
            operator_name: $('#operator_name').val(),
            operator_contact: $('#operator_contact').val(),
            bus_capacity: $('#bus_capacity').val() || null,
            registration_number: $('#registration_number').val().toUpperCase(),
            is_active: $('#is_active').is(':checked') ? 1 : 0
        };
        
        $.ajax({
            url: '../../api/buses_api.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Bus registered successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Failed to register bus. Please try again.');
            }
        });
    });
    
    // Edit Bus - Load Data
    $(document).on('click', '.edit-bus', function() {
        const busId = $(this).data('bus-id');
        const busNumber = $(this).data('bus-number');
        const routeId = $(this).data('route-id');
        const operatorName = $(this).data('operator-name');
        const operatorContact = $(this).data('operator-contact');
        const capacity = $(this).data('capacity');
        const registration = $(this).data('registration');
        const isActive = $(this).data('active');
        
        $('#edit_bus_id').val(busId);
        $('#edit_bus_number').val(busNumber);
        $('#edit_route_id').val(routeId);
        $('#edit_operator_name').val(operatorName);
        $('#edit_operator_contact').val(operatorContact);
        $('#edit_bus_capacity').val(capacity);
        $('#edit_registration_number').val(registration);
        $('#edit_is_active').prop('checked', isActive == 1);
        
        $('#editBusModal').modal('show');
    });
    
    // Edit Bus - Submit
    $('#editBusForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'edit',
            bus_id: $('#edit_bus_id').val(),
            bus_number: $('#edit_bus_number').val().toUpperCase(),
            route_id: $('#edit_route_id').val() || null,
            operator_name: $('#edit_operator_name').val(),
            operator_contact: $('#edit_operator_contact').val(),
            bus_capacity: $('#edit_bus_capacity').val() || null,
            registration_number: $('#edit_registration_number').val().toUpperCase(),
            is_active: $('#edit_is_active').is(':checked') ? 1 : 0
        };
        
        $.ajax({
            url: '../../api/buses_api.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Bus updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Failed to update bus. Please try again.');
            }
        });
    });
    
    // Toggle Status
    $(document).on('click', '.toggle-status', function() {
        const busId = $(this).data('bus-id');
        const action = $(this).data('action');
        
        if (confirm('Are you sure you want to ' + action + ' this bus?')) {
            $.ajax({
                url: '../../api/buses_api.php',
                method: 'POST',
                data: {
                    action: 'toggle',
                    bus_id: busId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Bus status updated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Failed to update status. Please try again.');
                }
            });
        }
    });
    
    // Delete Bus
    $(document).on('click', '.delete-bus', function() {
        const busId = $(this).data('bus-id');
        const busNumber = $(this).data('bus-number');
        
        if (confirm('Are you sure you want to delete bus "' + busNumber + '"?\n\nWarning: This cannot be undone!')) {
            $.ajax({
                url: '../../api/buses_api.php',
                method: 'POST',
                data: {
                    action: 'delete',
                    bus_id: busId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Bus deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Delete error:', xhr.responseText);
                    try {
                        const response = JSON.parse(xhr.responseText);
                        alert('Error: ' + (response.message || 'Failed to delete bus. Please try again.'));
                    } catch (e) {
                        alert('Failed to delete bus. Server error. Please check browser console for details.');
                    }
                }
            });
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
