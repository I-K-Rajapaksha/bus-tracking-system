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

$page_title = 'Route Management';
$db = new Database();

// Fetch all routes
try {
    $stmt = $db->query("
        SELECT 
            route_id,
            route_name,
            origin,
            destination,
            COALESCE(distance_km, 0) as distance_km,
            COALESCE(estimated_duration_minutes, 0) as estimated_duration_minutes,
            is_active,
            created_at
        FROM routes
        ORDER BY route_name ASC
    ");
    $routes = $stmt ? $stmt->fetchAll() : [];
} catch (Exception $e) {
    error_log("Routes fetch error: " . $e->getMessage());
    $routes = [];
}

include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-route"></i> Route Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal">
                    <i class="fas fa-plus"></i> Add New Route
                </button>
            </div>

            <!-- Routes Table -->
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> All Routes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="routesTable">
                            <thead>
                                <tr>
                                    <th>Route Name</th>
                                    <th>Origin</th>
                                    <th>Destination</th>
                                    <th>Distance (km)</th>
                                    <th>Duration (min)</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($routes)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No routes found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($routes as $route): ?>
                                        <tr data-route-id="<?php echo $route['route_id']; ?>">
                                            <td><strong><?php echo htmlspecialchars($route['route_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($route['origin']); ?></td>
                                            <td><?php echo htmlspecialchars($route['destination']); ?></td>
                                            <td><?php echo number_format($route['distance_km'], 1); ?></td>
                                            <td><?php echo $route['estimated_duration_minutes']; ?></td>
                                            <td>
                                                <?php if ($route['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($route['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info edit-route" 
                                                        data-route-id="<?php echo $route['route_id']; ?>"
                                                        data-route-name="<?php echo htmlspecialchars($route['route_name']); ?>"
                                                        data-origin="<?php echo htmlspecialchars($route['origin']); ?>"
                                                        data-destination="<?php echo htmlspecialchars($route['destination']); ?>"
                                                        data-distance="<?php echo $route['distance_km']; ?>"
                                                        data-duration="<?php echo $route['estimated_duration_minutes']; ?>"
                                                        data-active="<?php echo $route['is_active']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($route['is_active']): ?>
                                                    <button class="btn btn-sm btn-warning toggle-status" 
                                                            data-route-id="<?php echo $route['route_id']; ?>"
                                                            data-action="deactivate">
                                                        <i class="fas fa-toggle-off"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-success toggle-status" 
                                                            data-route-id="<?php echo $route['route_id']; ?>"
                                                            data-action="activate">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-danger delete-route" 
                                                        data-route-id="<?php echo $route['route_id']; ?>"
                                                        data-route-name="<?php echo htmlspecialchars($route['route_name']); ?>">
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

<!-- Add Route Modal -->
<div class="modal fade" id="addRouteModal" tabindex="-1" aria-labelledby="addRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addRouteModalLabel">
                    <i class="fas fa-plus-circle"></i> Add New Route
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addRouteForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="route_name" class="form-label">Route Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="route_name" name="route_name" 
                               placeholder="e.g., Colombo - Kandy" required>
                    </div>
                    <div class="mb-3">
                        <label for="origin" class="form-label">Origin <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="origin" name="origin" 
                               placeholder="e.g., Colombo" required>
                    </div>
                    <div class="mb-3">
                        <label for="destination" class="form-label">Destination <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="destination" name="destination" 
                               placeholder="e.g., Kandy" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="distance_km" class="form-label">Distance (km) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="distance_km" name="distance_km" 
                                   min="0" step="0.1" placeholder="e.g., 115.5" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="estimated_duration_minutes" class="form-label">Duration (min) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="estimated_duration_minutes" 
                                   name="estimated_duration_minutes" min="0" placeholder="e.g., 180" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active Route
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Route
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Route Modal -->
<div class="modal fade" id="editRouteModal" tabindex="-1" aria-labelledby="editRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="editRouteModalLabel">
                    <i class="fas fa-edit"></i> Edit Route
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editRouteForm">
                <input type="hidden" id="edit_route_id" name="route_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_route_name" class="form-label">Route Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_route_name" name="route_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_origin" class="form-label">Origin <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_origin" name="origin" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_destination" class="form-label">Destination <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_destination" name="destination" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_distance_km" class="form-label">Distance (km) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_distance_km" name="distance_km" 
                                   min="0" step="0.1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_estimated_duration_minutes" class="form-label">Duration (min) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_estimated_duration_minutes" 
                                   name="estimated_duration_minutes" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">
                                Active Route
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save"></i> Update Route
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).ready(function() {
    // Add Route
    $('#addRouteForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'add',
            route_name: $('#route_name').val(),
            origin: $('#origin').val(),
            destination: $('#destination').val(),
            distance_km: $('#distance_km').val(),
            estimated_duration_minutes: $('#estimated_duration_minutes').val(),
            is_active: $('#is_active').is(':checked') ? 1 : 0
        };
        
        $.ajax({
            url: '../../api/routes_api.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Route added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Failed to add route. Please try again.');
            }
        });
    });
    
    // Edit Route - Load Data
    $(document).on('click', '.edit-route', function() {
        const routeId = $(this).data('route-id');
        const routeName = $(this).data('route-name');
        const origin = $(this).data('origin');
        const destination = $(this).data('destination');
        const distance = $(this).data('distance');
        const duration = $(this).data('duration');
        const isActive = $(this).data('active');
        
        $('#edit_route_id').val(routeId);
        $('#edit_route_name').val(routeName);
        $('#edit_origin').val(origin);
        $('#edit_destination').val(destination);
        $('#edit_distance_km').val(distance);
        $('#edit_estimated_duration_minutes').val(duration);
        $('#edit_is_active').prop('checked', isActive == 1);
        
        $('#editRouteModal').modal('show');
    });
    
    // Edit Route - Submit
    $('#editRouteForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'edit',
            route_id: $('#edit_route_id').val(),
            route_name: $('#edit_route_name').val(),
            origin: $('#edit_origin').val(),
            destination: $('#edit_destination').val(),
            distance_km: $('#edit_distance_km').val(),
            estimated_duration_minutes: $('#edit_estimated_duration_minutes').val(),
            is_active: $('#edit_is_active').is(':checked') ? 1 : 0
        };
        
        $.ajax({
            url: '../../api/routes_api.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Route updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Failed to update route. Please try again.');
            }
        });
    });
    
    // Toggle Status
    $(document).on('click', '.toggle-status', function() {
        const routeId = $(this).data('route-id');
        const action = $(this).data('action');
        
        if (confirm('Are you sure you want to ' + action + ' this route?')) {
            $.ajax({
                url: '../../api/routes_api.php',
                method: 'POST',
                data: {
                    action: 'toggle',
                    route_id: routeId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Route status updated successfully!');
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
    
    // Delete Route
    $(document).on('click', '.delete-route', function() {
        const routeId = $(this).data('route-id');
        const routeName = $(this).data('route-name');
        
        if (confirm('Are you sure you want to delete route "' + routeName + '"?\n\nWarning: This cannot be undone!')) {
            $.ajax({
                url: '../../api/routes_api.php',
                method: 'POST',
                data: {
                    action: 'delete',
                    route_id: routeId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Route deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Delete error:', xhr.responseText);
                    try {
                        const response = JSON.parse(xhr.responseText);
                        alert('Error: ' + (response.message || 'Failed to delete route. Please try again.'));
                    } catch (e) {
                        alert('Failed to delete route. Server error. Please check browser console for details.');
                    }
                }
            });
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
