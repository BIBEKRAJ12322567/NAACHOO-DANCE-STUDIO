<?php
session_start();

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'dance_school';
$conn = new mysqli($host, $user, $password, $dbname);


if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Get form data
$email = $_POST['email'];
$password = $_POST['password'];

// Fetch user from database
$sql = "SELECT * FROM users WHERE email='$email'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        // Login successful
        echo json_encode(['success' => true]);
    } else {
        // Incorrect password
        echo json_encode(['success' => false, 'message' => 'Incorrect password! Please try again.']);
    }
} else {
    // User not found
    echo json_encode(['success' => false, 'message' => 'User not found! Please check your email.']);
}

$conn->close();
?>