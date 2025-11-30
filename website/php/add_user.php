<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'user';

    $errors = [];

    if ($firstName === '') { $errors[] = 'First name is required.'; }
    if ($lastName === '')  { $errors[] = 'Last name is required.'; }
    if ($username === '')  { $errors[] = 'Username is required.'; }

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    if ($password === '' || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    $allowedRoles = ['user', 'employee', 'admin'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'user';
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('ssssss', $firstName, $lastName, $username, $email, $hashed, $role);
            if ($stmt->execute()) {
                $_SESSION['flash'] = 'User created successfully.';
            } else {
                if ($stmt->errno === 1062) {
                    $_SESSION['flash'] = 'Username or email already exists.';
                } else {
                    $_SESSION['flash'] = 'Error creating user: ' . $stmt->error;
                }
            }
            $stmt->close();
        } else {
            $_SESSION['flash'] = 'Failed to prepare statement for creating user.';
        }
    } else {
        $_SESSION['flash'] = implode(' ', $errors);
    }
}

$conn->close();
header('Location: /website/php/admin.php?section=users#users');
exit();
