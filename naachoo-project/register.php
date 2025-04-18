<?php
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
$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash password

// Check if email already exists
$sql = "SELECT * FROM users WHERE email='$email'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Email already exists
    echo json_encode(['success' => false, 'message' => 'User already registered with this email!']);
} else {
    // Insert into database
    $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";

    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Registration successful! Please login.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
    }
}

$conn->close();
?>