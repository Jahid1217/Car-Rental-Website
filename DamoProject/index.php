<?php
require_once 'includes/header.php';
?>

<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4">Find Your Perfect Rental Car</h1>
                <p class="lead">Choose from our wide selection of vehicles at competitive prices.</p>
                <a href="cars.php" class="btn btn-primary btn-lg">Browse Cars</a>
            </div>
            <div class="col-md-6">
                <img src="assets/images/car-hero.png" alt="Car" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <h2 class="text-center mb-4">Featured Cars</h2>
    <div class="row">
        <?php
        $stmt = $pdo->query("SELECT * FROM cars WHERE status = 'available' LIMIT 4");
        while ($car = $stmt->fetch()):
        ?>
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <img src="<?php echo $car['image_path'] ? 'assets/images/cars/'.$car['image_path'] : 'assets/images/car-placeholder.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($car['make'].' '.$car['model']); ?>">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($car['make'].' '.$car['model']); ?></h5>
                    <p class="card-text">
                        Year: <?php echo htmlspecialchars($car['year']); ?><br>
                        Color: <?php echo htmlspecialchars($car['color']); ?><br>
                        Rate: $<?php echo htmlspecialchars($car['daily_rate']); ?>/day
                    </p>
                </div>
                <div class="card-footer bg-white">
                    <a href="car-details.php?id=<?php echo $car['car_id']; ?>" class="btn btn-primary">View Details</a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <div class="text-center mt-3">
        <a href="cars.php" class="btn btn-outline-primary">View All Cars</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>