<?php
require_once 'includes/config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $car_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE car_id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($car) {
        header('Content-Type: application/json');
        echo json_encode($car);
        exit;
    }
}

http_response_code(404);
echo json_encode(['error' => 'Car not found']);
?>