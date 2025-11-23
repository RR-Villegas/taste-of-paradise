<?php
// process_signup.php

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed.";
    exit;
}

// Get and sanitize input
$firstName        = trim($_POST['first_name'] ?? '');
$lastName         = trim($_POST['last_name'] ?? '');
$username         = trim($_POST['username'] ?? '');
$email            = trim($_POST['email'] ?? '');
$password         = $_POST['password'] ?? '';
$confirmPassword  = $_POST['confirm_password'] ?? '';

$errors = [];

// Validate first name
if ($firstName === '') {
    $errors[] = "First name is required.";
}

// Validate last name
if ($lastName === '') {
    $errors[] = "Last name is required.";
}

// Validate username
if ($username === '') {
    $errors[] = "Username is required.";
}

// Validate email
if ($email === '') {
    $errors[] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}

// Validate passwords
if ($password === '' || $confirmPassword === '') {
    $errors[] = "Password and confirmation are required.";
} elseif (strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters.";
} elseif ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
}

if (!empty($errors)) {
    // Show errors
    echo "<h1>Signup Error</h1>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
    echo '<p><a href="signup.html">Go back to signup</a></p>';
    exit;
}

// At this point, validation passed.
// Save the user to the database.

// 1) Hash the password (for secure storage)
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 2) Database connection settings - CHANGE these to match your setup
$host   = 'localhost';
$dbUser = 'root';          // XAMPP default user
$dbPass = '';              // XAMPP default has empty password
$dbName = 'food_paradise'; // TODO: change to your actual database name

// Create connection
$conn = new mysqli($host, $dbUser, $dbPass, $dbName);

// Check connection
if ($conn->connect_error) {
    die('Database connection failed: ' . htmlspecialchars($conn->connect_error));
}

// Prepare INSERT statement (uses prepared statements to avoid SQL injection)
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, password) VALUES (?, ?, ?, ?, ?)");

if (!$stmt) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param('sssss', $firstName, $lastName, $username, $email, $hashedPassword);

if (!$stmt->execute()) {
    // Handle duplicate entry (username/email already taken)
    if ($stmt->errno === 1062) { // 1062 = duplicate entry
        echo "<h1>Signup Error</h1>";
        echo "<p>That username or email is already taken. Please choose another.</p>";
        echo '<p><a href="signup.html">Go back to signup</a></p>';
        $stmt->close();
        $conn->close();
        exit;
    }

    die('Error saving user: ' . htmlspecialchars($stmt->error));
}

$stmt->close();
$conn->close();

// 3) Simple success message
echo "<h1>Signup Successful</h1>";
echo "<p>Welcome, " . htmlspecialchars($firstName . ' ' . $lastName) . "!</p>";
echo "<p>Your account has been created and stored in the database.</p>";
echo '<p><a href="signup.html">Back to signup</a></p>';
