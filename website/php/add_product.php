<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];

    // Ensure image_path column exists (compatible with MySQL/MariaDB)
    $check = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='image_path'");
    if ($check) {
        $row = $check->fetch_assoc();
        if ((int)($row['c'] ?? 0) === 0) {
            @$conn->query("ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL");
        }
    }

    $imageRelPath = null;
    if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);
        $size = (int)$_FILES['image']['size'];
        if (isset($allowed[$mime]) && $size <= 5 * 1024 * 1024) {
            $ext = $allowed[$mime];
            $uploadDirFs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'products';
            if (!is_dir($uploadDirFs)) {
                @mkdir($uploadDirFs, 0777, true);
            }
            $base = preg_replace('/[^A-Za-z0-9_-]/','_', strtolower(pathinfo($name, PATHINFO_FILENAME)));
            if ($base === '') { $base = 'product'; }
            $filename = $base . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            $destFs = $uploadDirFs . DIRECTORY_SEPARATOR . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destFs)) {
                $imageRelPath = 'static/image/products/' . $filename;
            }
        } else {
            $_SESSION['flash'] = 'Invalid image file. Use PNG/JPG/GIF/WEBP up to 5MB.';
        }
    }

    $sql = $imageRelPath
        ? "INSERT INTO products (product_name, description, price, image_path) VALUES (?, ?, ?, ?)"
        : "INSERT INTO products (product_name, description, price) VALUES (?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        if ($imageRelPath) {
            $stmt->bind_param("ssds", $name, $description, $price, $imageRelPath);
        } else {
            $stmt->bind_param("ssd", $name, $description, $price);
        }
        if ($stmt->execute()) {
            $_SESSION['flash'] = $_SESSION['flash'] ?? "Product added successfully.";
        } else {
            $_SESSION['flash'] = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['flash'] = 'Failed to add product (prepare error).';
    }
}

$conn->close();
header("Location: /website/template/admin.php#add-product");
exit();
?>
