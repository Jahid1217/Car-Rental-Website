<?php
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <h2>Available Cars</h2>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <form action="search.php" method="get" class="d-flex">
                <input type="text" name="query" class="form-control me-2" placeholder="Search cars...">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
        <div class="col-md-6 text-end">
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                    Sort By
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?sort=price_low">Price: Low to High</a></li>
                    <li><a class="dropdown-item" href="?sort=price_high">Price: High to Low</a></li>
                    <li><a class="dropdown-item" href="?sort=year_new">Year: Newest First</a></li>
                    <li><a class="dropdown-item" href="?sort=year_old">Year: Oldest First</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="row">
        <?php
        $sql = "SELECT * FROM cars WHERE status = 'available'";
        
        if (isset($_GET['sort'])) {
            switch ($_GET['sort']) {
                case 'price_low':
                    $sql .= " ORDER BY daily_rate ASC";
                    break;
                case 'price_high':
                    $sql .= " ORDER BY daily_rate DESC";
                    break;
                case 'year_new':
                    $sql .= " ORDER BY year DESC";
                    break;
                case 'year_old':
                    $sql .= " ORDER BY year ASC";
                    break;
            }
        }
        
        $stmt = $pdo->query($sql);
        while ($car = $stmt->fetch()):
        ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <img src="<?php echo $car['image_path'] ? 'assets/images/cars/'.$car['image_path'] : 'assets/images/car-placeholder.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($car['make'].' '.$car['model']); ?>">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($car['make'].' '.$car['model']); ?></h5>
                    <p class="card-text">
                        <strong>Year:</strong> <?php echo htmlspecialchars($car['year']); ?><br>
                        <strong>Color:</strong> <?php echo htmlspecialchars($car['color']); ?><br>
                        <strong>License Plate:</strong> <?php echo htmlspecialchars($car['license_plate']); ?><br>
                        <strong>Daily Rate:</strong> $<?php echo htmlspecialchars($car['daily_rate']); ?>
                    </p>
                    <p class="card-text"><?php echo htmlspecialchars($car['description']); ?></p>
                </div>
                <div class="card-footer bg-white">
                    <?php if (isLoggedIn() && !isAdmin()): ?>
                    <button class="btn btn-primary book-btn" data-car-id="<?php echo $car['car_id']; ?>">Book Now</button>
                    <?php endif; ?>
                    <a href="car-details.php?id=<?php echo $car['car_id']; ?>" class="btn btn-outline-secondary">View Details</a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Book Car</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bookingForm" method="post" action="process-booking.php">
                <div class="modal-body">
                    <input type="hidden" name="car_id" id="modalCarId">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Amount: $<span id="totalAmount">0.00</span></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Booking modal setup
    const bookButtons = document.querySelectorAll('.book-btn');
    const modalCarId = document.getElementById('modalCarId');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const totalAmountSpan = document.getElementById('totalAmount');
    let dailyRate = 0;
    
    bookButtons.forEach(button => {
        button.addEventListener('click', function() {
            const carId = this.getAttribute('data-car-id');
            modalCarId.value = carId;
            
            // Fetch car details to get daily rate (in a real app, you might pass this via data attributes)
            fetch('get-car-details.php?id=' + carId)
                .then(response => response.json())
                .then(data => {
                    dailyRate = parseFloat(data.daily_rate);
                    updateTotalAmount();
                });
            
            const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
            modal.show();
        });
    });
    
    // Calculate total amount when dates change
    startDateInput.addEventListener('change', updateTotalAmount);
    endDateInput.addEventListener('change', updateTotalAmount);
    
    function updateTotalAmount() {
        if (startDateInput.value && endDateInput.value && dailyRate > 0) {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            
            if (endDate >= startDate) {
                const diffTime = Math.abs(endDate - startDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                const total = diffDays * dailyRate;
                totalAmountSpan.textContent = total.toFixed(2);
            } else {
                totalAmountSpan.textContent = '0.00 (invalid date range)';
            }
        } else {
            totalAmountSpan.textContent = '0.00';
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>