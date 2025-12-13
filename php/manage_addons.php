<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_GET['redirect'] = '/taste-of-paradise-a/index.php';
    include 'error_401.php';
    exit();
}
require_once 'config.php';

// Add new add-on
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $addonName = trim($_POST['addon_name'] ?? '');
    $addonPrice = $_POST['addon_price'] ?? 0;
    
    if ($addonName !== '' && $addonPrice > 0) {
        $stmt = $conn->prepare("INSERT INTO addons (addon_name, addon_price) VALUES (?, ?)");
        if ($stmt) {
            $addonPrice = (float)$addonPrice;
            $stmt->bind_param('sd', $addonName, $addonPrice);
            if ($stmt->execute()) {
                $_SESSION['flash'] = 'Add-on created successfully.';
            } else {
                if ($stmt->errno === 1062) {
                    $_SESSION['flash'] = 'Add-on already exists.';
                } else {
                    $_SESSION['flash'] = 'Error creating add-on: ' . $stmt->error;
                }
            }
            $stmt->close();
        }
    } else {
        $_SESSION['flash'] = 'Please enter valid add-on name and price.';
    }
}

// Delete add-on
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $addonId = isset($_POST['addon_id']) ? (int)$_POST['addon_id'] : 0;
    if ($addonId > 0) {
        $stmt = $conn->prepare('DELETE FROM addons WHERE addon_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $addonId);
            $stmt->execute();
            $_SESSION['flash'] = 'Add-on deleted successfully.';
            $stmt->close();
        }
    }
}

$conn->close();
header('Location: /taste-of-paradise-a/php/admin.php?section=manage-addons#manage-addons');
exit();
?>
