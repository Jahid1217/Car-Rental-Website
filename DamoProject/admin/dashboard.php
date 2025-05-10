<?php
require_once '../includes/header.php';

if (!isAdmin()) {
    redirect('../index.php');
}

// Get counts for dashboard
$carsCount = $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
$availableCarsCount = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'available'")->fetchColumn();
$usersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'customer'")->fetchColumn();
$bookingsCount = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pendingBookingsCount = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
?>

<div class="container mt-4">
    <h2>Admin Dashboard</h2>
    
    <div class="row mt-4">
        <div class="col-md-4 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Cars</h5>
                    <h2><?php echo $carsCount; ?></h2>
                    <a href="cars.php" class="text-white">View All</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Available Cars</h5>
                    <h2><?php echo $availableCarsCount; ?></h2>
                    <a href="cars.php?status=available" class="text-white">View Available</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Customers</h5>
                    <h2><?php echo $usersCount; ?></h2>
                    <a href="users.php" class="text-white">View All</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Bookings</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("SELECT b.*, u.full_name, c.make, c.model 
                                        FROM bookings b
                                        JOIN users u ON b.user_id = u.user_id
                                        JOIN cars c ON b.car_id = c.car_id
                                        ORDER BY b.created_at DESC LIMIT 5");
                    if ($stmt->rowCount() > 0):
                    ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Car</th>
                                    <th>Dates</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $stmt->fetch()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['make'].' '.$booking['model']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['start_date'].' to '.$booking['end_date']); ?></td>
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
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="bookings.php" class="btn btn-sm btn-primary">View All Bookings</a>
                    <?php else: ?>
                    <p>No recent bookings found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Pending Bookings (<?php echo $pendingBookingsCount; ?>)</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("SELECT b.*, u.full_name, c.make, c.model 
                                        FROM bookings b
                                        JOIN users u ON b.user_id = u.user_id
                                        JOIN cars c ON b.car_id = c.car_id
                                        WHERE b.status = 'pending'
                                        ORDER BY b.created_at DESC LIMIT 5");
                    if ($stmt->rowCount() > 0):
                    ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Car</th>
                                    <th>Dates</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $stmt->fetch()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['make'].' '.$booking['model']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['start_date'].' to '.$booking['end_date']); ?></td>
                                    <td>
                                        <a href="process-booking.php?action=approve&id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                        <a href="process-booking.php?action=reject&id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="bookings.php?status=pending" class="btn btn-sm btn-primary">View All Pending</a>
                    <?php else: ?>
                    <p>No pending bookings.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>