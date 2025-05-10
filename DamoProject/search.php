<?php
require_once 'includes/header.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query)) {
    redirect('cars.php');
}

$search = '%' . $query . '%';
$stmt = $pdo->prepare("SELECT * FROM cars 
                      WHERE (make LIKE ? OR model LIKE ? OR license_plate LIKE ? OR color LIKE ? OR year LIKE ?)
                      AND status = 'available'");
$stmt->execute([$search, $search, $search, $search, $search]);
$cars = $stmt->fetchAll();
?>

<div class="container mt-4">
    <h2>Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>
    
    <?php if (empty($cars)): ?>
    <div class="alert alert-info">No cars found matching your search. Try different keywords.</div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($cars as $car): ?>
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
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>