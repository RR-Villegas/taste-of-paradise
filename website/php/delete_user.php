<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    if ($id > 0) {
        // Do not allow deleting admins
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role <> 'admin'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();
header('Location: /website/template/admin.php#users');
exit();
