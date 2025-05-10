<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login to book a car";
    redirect('login.php');
}

// Handle new booking
if (isset($_POST['car_id']) && isset($_POST['start_date']) && isset($_POST['end_date'])) {
    $car_id = $_POST['car_id'];
    $user_id = $_SESSION['user_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Calculate total amount
    $stmt = $pdo->prepare("SELECT daily_rate FROM cars WHERE car_id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();
    
    if (!$car) {
        $_SESSION['error'] = "Car not found";
        redirect('cars.php');
    }
    
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days = $start->diff($end)->days + 1;
    $total_amount = $days * $car['daily_rate'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, car_id, start_date, end_date, total_amount) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $car_id, $start_date, $end_date, $total_amount]);
        
        $_SESSION['success'] = "Booking request submitted successfully. Waiting for admin approval.";
        redirect('bookings.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error creating booking: " . $e->getMessage();
        redirect('cars.php');
    }
}

// Handle admin actions (approve/reject)
if (isAdmin() && isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $booking_id = $_GET['id'];
    
    if (!in_array($action, ['approve', 'reject'])) {
        $_SESSION['error'] = "Invalid action";
        redirect('admin/bookings.php');
    }
    
    $status = $action == 'approve' ? 'approved' : 'rejected';
    
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        $stmt->execute([$status, $booking_id]);
        
        $_SESSION['success'] = "Booking $status successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating booking: " . $e->getMessage();
    }
    
    redirect('admin/bookings.php');
}

// Handle customer cancellation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $booking_id = $_GET['cancel'];
    $user_id = $_SESSION['user_id'];
    
    // Verify the booking belongs to the user and is still pending
    $stmt = $pdo->prepare("SELECT status FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        $_SESSION['error'] = "Booking not found or you don't have permission to cancel it";
        redirect('bookings.php');
    }
    
    if ($booking['status'] != 'pending') {
        $_SESSION['error'] = "You can only cancel pending bookings";
        redirect('bookings.php');
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        
        $_SESSION['success'] = "Booking cancelled successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error cancelling booking: " . $e->getMessage();
    }
    
    redirect('bookings.php');
}

redirect('index.php');
?>