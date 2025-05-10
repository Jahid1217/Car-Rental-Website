<?php
require_once '../includes/header.php';

if (!isAdmin()) {
    redirect('../index.php');
}

// Handle booking status updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $booking_id = $_GET['id'];
    
    if (!in_array($action, ['approve', 'reject', 'cancel'])) {
        $_SESSION['error'] = "Invalid action";
        redirect('bookings.php');
    }
    
    $status = $action == 'approve' ? 'approved' : ($action == 'reject' ? 'rejected' : 'cancelled');
    
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        $stmt->execute([$status, $booking_id]);
        
        $_SESSION['success'] = "Booking has been " . $status . " successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating booking: " . $e->getMessage();
    }
    
    redirect('bookings.php');
}

// Get all bookings with filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT b.*, u.username, u.full_name, u.email, c.make, c.model, c.image_path 
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN cars c ON b.car_id = c.car_id";

$params = [];
$conditions = [];

if (!empty($status_filter)) {
    $conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $search = '%' . $search_query . '%';
    $conditions[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR c.make LIKE ? OR c.model LIKE ? OR b.booking_id = ?)";
    $params = array_merge($params, [$search, $search, $search, $search, $search, $search_query]);
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<div class="container mt-4">
    <h2>Manage Bookings</h2>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Search by user, car, or booking ID" value="<?php echo htmlspecialchars($search_query); ?>">
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
                    <th>Booking ID</th>
                    <th>Customer</th>
                    <th>Car</th>
                    <th>Dates</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?php echo $booking['booking_id']; ?></td>
                    <td>
                        <?php echo htmlspecialchars($booking['full_name']); ?><br>
                        <small><?php echo htmlspecialchars($booking['email']); ?></small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($booking['make'] . ' ' . $booking['model']); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($booking['start_date'] . ' to ' . $booking['end_date']); ?><br>
                        <?php 
                            $start = new DateTime($booking['start_date']);
                            $end = new DateTime($booking['end_date']);
                            $days = $start->diff($end)->days + 1;
                            echo $days . ' day' . ($days != 1 ? 's' : '');
                        ?>
                    </td>
                    <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                    <td>
                        <span class="badge bg-<?php 
                            switch($booking['status']) {
                                case 'approved': echo 'success'; break;
                                case 'rejected': echo 'danger'; break;
                                case 'cancelled': echo 'warning'; break;
                                default: echo 'info';
                            }
                        ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($booking['payment_status'] == 'paid'): ?>
                            <span class="badge bg-success">Paid</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?php echo ucfirst($booking['payment_status']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <?php if ($booking['status'] == 'pending'): ?>
                                <a href="?action=approve&id=<?php echo $booking['booking_id']; ?>" class="btn btn-success">Approve</a>
                                <a href="?action=reject&id=<?php echo $booking['booking_id']; ?>" class="btn btn-danger">Reject</a>
                            <?php elseif ($booking['status'] == 'approved'): ?>
                                <a href="?action=cancel&id=<?php echo $booking['booking_id']; ?>" class="btn btn-warning">Cancel</a>
                            <?php endif; ?>
                            <a href="../booking-details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-info">Details</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($bookings)): ?>
        <div class="alert alert-info">No bookings found matching your criteria.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>