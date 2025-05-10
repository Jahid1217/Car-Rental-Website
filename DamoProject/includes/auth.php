<?php
require_once 'config.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['full_name'] = $user['full_name'];
        
        if (isAdmin()) {
            redirect('admin/dashboard.php');
        } else {
            redirect('index.php');
        }
    } else {
        $_SESSION['error'] = "Invalid username or password";
        redirect('login.php');
    }
}

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, phone, address, user_type) VALUES (?, ?, ?, ?, ?, ?, 'customer')");
        $stmt->execute([$username, $password, $email, $full_name, $phone, $address]);
        
        $_SESSION['success'] = "Registration successful. Please login.";
        redirect('login.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        redirect('register.php');
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}
?>