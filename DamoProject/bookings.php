<?php
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (isAdmin()) {
    redirect('admin/bookings.php');
}

$user_id = $_SESSION['user_id'];

// Get all bookings for the current user
$stmt = $pdo->prepare("SELECT b.*, c.make, c.model, c.image_path 
                       FROM bookings b
                       JOIN cars c ON b.car_id = c.car_id
                       WHERE b.user_id = ?
                       ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>

<div class="container mt-4">
    <h2>My Bookings</h2>
    
    <?php if (empty($bookings)): ?>
    <div class="alert alert-info">You have no bookings yet. <a href="cars.php">Browse cars</a> to make a booking.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
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
                    <td>
                        <img src="<?php echo $booking['image_path'] ? 'assets/images/cars/'.$booking['image_path'] : 'assets/images/car-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($booking['make'].' '.$booking['model']); ?>" style="width: 80px; height: auto;">
                        <?php echo htmlspecialchars($booking['make'].' '.$booking['model']); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($booking['start_date'].' to '.$booking['end_date']); ?><br>
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
                        <?php elseif ($booking['status'] == 'approved'): ?>
                            <button class="btn btn-sm btn-primary pay-btn" data-booking-id="<?php echo $booking['booking_id']; ?>">Pay Now</button>
                        <?php else: ?>
                            <span class="badge bg-secondary">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($booking['status'] == 'pending'): ?>
                            <a href="process-booking.php?cancel=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel</a>
                        <?php endif; ?>
                        <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-info">Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Make Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm" method="post" action="process-payment.php">
                <div class="modal-body">
                    <input type="hidden" name="booking_id" id="paymentBookingId">
                    <div class="mb-3">
                        <label class="form-label">Amount to Pay: $<span id="paymentAmount">0.00</span></label>
                    </div>
                    <div class="mb-3">
                        <label for="paymentMethod" class="form-label">Payment Method</label>
                        <select class="form-select" id="paymentMethod" name="payment_method" required>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="paypal">PayPal</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div id="creditCardFields">
                        <div class="mb-3">
                            <label for="cardNumber" class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="cardNumber" name="card_number" placeholder="1234 5678 9012 3456">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expiryDate" class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="expiryDate" name="expiry_date" placeholder="MM/YY">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cvv" name="cvv" placeholder="123">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="cardName" class="form-label">Name on Card</label>
                            <input type="text" class="form-control" id="cardName" name="card_name" placeholder="John Doe">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment modal setup
    const payButtons = document.querySelectorAll('.pay-btn');
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    const paymentBookingId = document.getElementById('paymentBookingId');
    const paymentAmount = document.getElementById('paymentAmount');
    
    payButtons.forEach(button => {
        button.addEventListener('click', function() {
            const bookingId = this.getAttribute('data-booking-id');
            
            // Fetch booking details to get amount
            fetch('get-booking-details.php?id=' + bookingId)
                .then(response => response.json())
                .then(booking => {
                    paymentBookingId.value = booking.booking_id;
                    paymentAmount.textContent = booking.total_amount.toFixed(2);
                    
                    paymentModal.show();
                });
        });
    });
    
    // Toggle credit card fields based on payment method
    const paymentMethod = document.getElementById('paymentMethod');
    const creditCardFields = document.getElementById('creditCardFields');
    
    paymentMethod.addEventListener('change', function() {
        if (this.value === 'credit_card' || this.value === 'debit_card') {
            creditCardFields.style.display = 'block';
        } else {
            creditCardFields.style.display = 'none';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>