<?php
require_once '../includes/header.php';

if (!isAdmin()) {
    redirect('../index.php');
}

// Add new car
if (isset($_POST['add_car'])) {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $color = $_POST['color'];
    $license_plate = $_POST['license_plate'];
    $daily_rate = $_POST['daily_rate'];
    $description = $_POST['description'];
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/cars/';
        $file_name = basename($_FILES['image']['name']);
        $file_path = $upload_dir . $file_name;
        
        // Generate unique filename
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_name = uniqid() . '.' . $file_ext;
        $file_path = $upload_dir . $unique_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $image_path = $unique_name;
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO cars (make, model, year, color, license_plate, daily_rate, description, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$make, $model, $year, $color, $license_plate, $daily_rate, $description, $image_path]);
        
        $_SESSION['success'] = "Car added successfully!";
        redirect('cars.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding car: " . $e->getMessage();
    }
}

// Update car
if (isset($_POST['update_car'])) {
    $car_id = $_POST['car_id'];
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $color = $_POST['color'];
    $license_plate = $_POST['license_plate'];
    $daily_rate = $_POST['daily_rate'];
    $status = $_POST['status'];
    $description = $_POST['description'];
    
    // Handle image upload if a new image is provided
    $image_path = $_POST['existing_image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/cars/';
        $file_name = basename($_FILES['image']['name']);
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_name = uniqid() . '.' . $file_ext;
        $file_path = $upload_dir . $unique_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            // Delete old image if it exists
            if ($image_path && file_exists($upload_dir . $image_path)) {
                unlink($upload_dir . $image_path);
            }
            $image_path = $unique_name;
        }
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE cars SET make = ?, model = ?, year = ?, color = ?, license_plate = ?, daily_rate = ?, status = ?, description = ?, image_path = ? WHERE car_id = ?");
        $stmt->execute([$make, $model, $year, $color, $license_plate, $daily_rate, $status, $description, $image_path, $car_id]);
        
        $_SESSION['success'] = "Car updated successfully!";
        redirect('cars.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating car: " . $e->getMessage();
    }
}

// Delete car
if (isset($_GET['delete'])) {
    $car_id = $_GET['delete'];
    
    try {
        // First, get the image path to delete the file
        $stmt = $pdo->prepare("SELECT image_path FROM cars WHERE car_id = ?");
        $stmt->execute([$car_id]);
        $car = $stmt->fetch();
        
        if ($car && $car['image_path']) {
            $file_path = '../assets/images/cars/' . $car['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Then delete the car record
        $stmt = $pdo->prepare("DELETE FROM cars WHERE car_id = ?");
        $stmt->execute([$car_id]);
        
        $_SESSION['success'] = "Car deleted successfully!";
        redirect('cars.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting car: " . $e->getMessage();
        redirect('cars.php');
    }
}
?>

<div class="container mt-4">
    <h2>Manage Cars</h2>
    
    <div class="d-flex justify-content-between mb-4">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCarModal">Add New Car</button>
        <form class="d-flex" method="get" action="">
            <input type="text" name="search" class="form-control me-2" placeholder="Search cars..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Make & Model</th>
                    <th>Year</th>
                    <th>Color</th>
                    <th>License Plate</th>
                    <th>Daily Rate</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM cars";
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $search = '%' . $_GET['search'] . '%';
                    $sql .= " WHERE make LIKE ? OR model LIKE ? OR license_plate LIKE ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$search, $search, $search]);
                } else {
                    $stmt = $pdo->query($sql);
                }
                
                while ($car = $stmt->fetch()):
                ?>
                <tr>
                    <td>
                        <img src="<?php echo $car['image_path'] ? '../assets/images/cars/'.$car['image_path'] : '../assets/images/car-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($car['make'].' '.$car['model']); ?>" style="width: 80px; height: auto;">
                    </td>
                    <td><?php echo htmlspecialchars($car['make'].' '.$car['model']); ?></td>
                    <td><?php echo htmlspecialchars($car['year']); ?></td>
                    <td><?php echo htmlspecialchars($car['color']); ?></td>
                    <td><?php echo htmlspecialchars($car['license_plate']); ?></td>
                    <td>$<?php echo htmlspecialchars($car['daily_rate']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $car['status'] == 'available' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($car['status']); ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-btn" data-car-id="<?php echo $car['car_id']; ?>">Edit</button>
                        <a href="?delete=<?php echo $car['car_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this car?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Car Modal -->
<div class="modal fade" id="addCarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Car</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="make" class="form-label">Make</label>
                        <input type="text" class="form-control" id="make" name="make" required>
                    </div>
                    <div class="mb-3">
                        <label for="model" class="form-label">Model</label>
                        <input type="text" class="form-control" id="model" name="model" required>
                    </div>
                    <div class="mb-3">
                        <label for="year" class="form-label">Year</label>
                        <input type="number" class="form-control" id="year" name="year" min="1900" max="<?php echo date('Y')+1; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="color" class="form-label">Color</label>
                        <input type="text" class="form-control" id="color" name="color" required>
                    </div>
                    <div class="mb-3">
                        <label for="license_plate" class="form-label">License Plate</label>
                        <input type="text" class="form-control" id="license_plate" name="license_plate" required>
                    </div>
                    <div class="mb-3">
                        <label for="daily_rate" class="form-label">Daily Rate ($)</label>
                        <input type="number" class="form-control" id="daily_rate" name="daily_rate" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Car Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_car" class="btn btn-primary">Add Car</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Car Modal -->
<div class="modal fade" id="editCarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Car</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="car_id" id="editCarId">
                    <input type="hidden" name="existing_image" id="existingImage">
                    <div class="mb-3">
                        <label for="editMake" class="form-label">Make</label>
                        <input type="text" class="form-control" id="editMake" name="make" required>
                    </div>
                    <div class="mb-3">
                        <label for="editModel" class="form-label">Model</label>
                        <input type="text" class="form-control" id="editModel" name="model" required>
                    </div>
                    <div class="mb-3">
                        <label for="editYear" class="form-label">Year</label>
                        <input type="number" class="form-control" id="editYear" name="year" min="1900" max="<?php echo date('Y')+1; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="editColor" class="form-label">Color</label>
                        <input type="text" class="form-control" id="editColor" name="color" required>
                    </div>
                    <div class="mb-3">
                        <label for="editLicensePlate" class="form-label">License Plate</label>
                        <input type="text" class="form-control" id="editLicensePlate" name="license_plate" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDailyRate" class="form-label">Daily Rate ($)</label>
                        <input type="number" class="form-control" id="editDailyRate" name="daily_rate" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="editStatus" class="form-label">Status</label>
                        <select class="form-select" id="editStatus" name="status" required>
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editImage" class="form-label">Car Image</label>
                        <input type="file" class="form-control" id="editImage" name="image" accept="image/*">
                        <div class="mt-2">
                            <img id="currentImagePreview" src="" alt="Current Image" style="max-width: 100px; max-height: 100px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_car" class="btn btn-primary">Update Car</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit car modal setup
    const editButtons = document.querySelectorAll('.edit-btn');
    const editModal = new bootstrap.Modal(document.getElementById('editCarModal'));
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const carId = this.getAttribute('data-car-id');
            
            // Fetch car details
            fetch('get-car-details.php?id=' + carId)
                .then(response => response.json())
                .then(car => {
                    document.getElementById('editCarId').value = car.car_id;
                    document.getElementById('editMake').value = car.make;
                    document.getElementById('editModel').value = car.model;
                    document.getElementById('editYear').value = car.year;
                    document.getElementById('editColor').value = car.color;
                    document.getElementById('editLicensePlate').value = car.license_plate;
                    document.getElementById('editDailyRate').value = car.daily_rate;
                    document.getElementById('editStatus').value = car.status;
                    document.getElementById('editDescription').value = car.description;
                    document.getElementById('existingImage').value = car.image_path;
                    
                    // Set image preview
                    const imagePreview = document.getElementById('currentImagePreview');
                    if (car.image_path) {
                        imagePreview.src = '../assets/images/cars/' + car.image_path;
                        imagePreview.style.display = 'block';
                    } else {
                        imagePreview.style.display = 'none';
                    }
                    
                    editModal.show();
                });
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>