<?php
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('bookings.php');
}

$booking_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get booking details
$sql = "SELECT b.*, u.username, u.full_name, u.email, u.phone, 
               c.make, c.model, c.year, c.color, c.license_plate, c.daily_rate, c.image_path
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN cars c ON b.car_id = c.car_id
        WHERE b.booking_id = ?";

if (!isAdmin()) {
    $sql .= " AND b.user_id = ?";
    $params = [$booking_id, $user_id];
} else {
    $params = [$booking_id];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error'] = "Booking not found or you don't have permission to view it";
    redirect(isAdmin() ? 'admin/bookings.php' : 'bookings.php');
}

// Calculate rental days
$start_date = new DateTime($booking['start_date']);
$end_date = new DateTime($booking['end_date']);
$days = $start_date->diff($end_date)->days + 1;
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Booking Details #<?php echo $booking['booking_id']; ?></h2>
        <a href="<?php echo isAdmin() ? 'admin/bookings.php' : 'bookings.php'; ?>" class="btn btn-secondary">Back to Bookings</a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Car Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <img src="<?php echo $booking['image_path'] ? 'assets/images/cars/'.$booking['image_path'] : 'assets/images/car-placeholder.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($booking['make'].' '.$booking['model']); ?>" 
                                 class="img-fluid rounded">
                        </div>
                        <div class="col-md-8">
                            <h4><?php echo htmlspecialchars($booking['make'].' '.$booking['model']); ?></h4>
                            <p><strong>Year:</strong> <?php echo htmlspecialchars($booking['year']); ?></p>
                            <p><strong>Color:</strong> <?php echo htmlspecialchars($booking['color']); ?></p>
                            <p><strong>License Plate:</strong> <?php echo htmlspecialchars($booking['license_plate']); ?></p>
                            <p><strong>Daily Rate:</strong> $<?php echo number_format($booking['daily_rate'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Rental Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Pickup Date:</strong> <?php echo date('M d, Y', strtotime($booking['start_date'])); ?></p>
                            <p><strong>Drop-off Date:</strong> <?php echo date('M d, Y', strtotime($booking['end_date'])); ?></p>
                            <p><strong>Total Days:</strong> <?php echo $days; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Amount:</strong> $<?php echo number_format($booking['total_amount'], 2); ?></p>
                            <p><strong>Booking Status:</strong> 
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
                            </p>
                            <p><strong>Payment Status:</strong> 
                                <span class="badge bg-<?php echo $booking['payment_status'] == 'paid' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($booking['payment_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Customer Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['full_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?></p>
                    <p><strong>Booking Date:</strong> <?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></p>
                </div>
            </div>
            
            <?php if (isAdmin() && $booking['status'] == 'pending'): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                    <h5>Admin Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="process-booking.php?action=approve&id=<?php echo $booking['booking_id']; ?>" class="btn btn-success">Approve Booking</a>
                        <a href="process-booking.php?action=reject&id=<?php echo $booking['booking_id']; ?>" class="btn btn-danger">Reject Booking</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!isAdmin() && $booking['status'] == 'pending'): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                    <h5>Customer Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="process-booking.php?cancel=<?php echo $booking['booking_id']; ?>" class="btn btn-warning" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel Booking</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!isAdmin() && $booking['status'] == 'approved' && $booking['payment_status'] != 'paid'): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Payment</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="payment.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-success">Proceed to Payment</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>