<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  http_response_code(403);
  exit('Unauthorized');
}

$title   = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

if ($title === '' || $content === '') {
  $_SESSION['flash'] = 'Title and content are required.';
  header('Location: ../php/admin.php?section=announcement');
  exit;
}

$stmt = $conn->prepare(
  "INSERT INTO announcement_blog (title, content) VALUES (?, ?)"
);
$stmt->bind_param("ss", $title, $content);
$stmt->execute();

$_SESSION['flash'] = 'Announcement published!';
header('Location: ../php/admin.php?section=announcement');
