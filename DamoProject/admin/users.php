<?php
require_once '../includes/header.php';

if (!isAdmin()) {
    redirect('../index.php');
}

// Handle user status updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $user_id = $_GET['id'];
    
    if (!in_array($action, ['activate', 'deactivate', 'delete'])) {
        $_SESSION['error'] = "Invalid action";
        redirect('users.php');
    }
    
    try {
        if ($action == 'delete') {
            // Check if user has bookings before deleting
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $booking_count = $stmt->fetchColumn();
            
            if ($booking_count > 0) {
                $_SESSION['error'] = "Cannot delete user with existing bookings";
                redirect('users.php');
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success'] = "User deleted successfully";
        } else {
            $status = $action == 'activate' ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $stmt->execute([$status, $user_id]);
            $_SESSION['success'] = "User " . ($action == 'activate' ? 'activated' : 'deactivated') . " successfully";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error processing request: " . $e->getMessage();
    }
    
    redirect('users.php');
}

// Get all users with filters
$user_type = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
$conditions = [];

if (!empty($user_type)) {
    $conditions[] = "user_type = ?";
    $params[] = $user_type;
}

if ($status_filter !== '') {
    $conditions[] = "is_active = ?";
    $params[] = ($status_filter == 'active' ? 1 : 0);
}

if (!empty($search_query)) {
    $search = '%' . $search_query . '%';
    $conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $params = array_merge($params, [$search, $search, $search]);
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="container mt-4">
    <h2>Manage Users</h2>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">User Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="customer" <?php echo $user_type == 'customer' ? 'selected' : ''; ?>>Customer</option>
                        <option value="admin" <?php echo $user_type == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Search by name, username or email" value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $user['user_type'] == 'admin' ? 'danger' : 'primary'; ?>">
                            <?php echo ucfirst($user['user_type']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <?php if ($user['is_active']): ?>
                                <a href="?action=deactivate&id=<?php echo $user['user_id']; ?>" class="btn btn-warning">Deactivate</a>
                            <?php else: ?>
                                <a href="?action=activate&id=<?php echo $user['user_id']; ?>" class="btn btn-success">Activate</a>
                            <?php endif; ?>
                            <a href="?action=delete&id=<?php echo $user['user_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($users)): ?>
        <div class="alert alert-info">No users found matching your criteria.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>