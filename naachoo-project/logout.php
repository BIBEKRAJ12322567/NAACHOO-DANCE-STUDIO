<?php
session_start();
header('Content-Type: application/json');

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
?> 