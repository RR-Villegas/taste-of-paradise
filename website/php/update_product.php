<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $remove = isset($_POST['remove_image']);

    if ($id > 0 && $name !== '' && $price !== '') {
        // Ensure image_path column exists (compatible)
        $chk = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='image_path'");
        if ($chk) {
            $r = $chk->fetch_assoc();
            if ((int)($r['c'] ?? 0) === 0) {
                @$conn->query("ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL");
            }
        }

        // Fetch current image
        $current = null;
        if ($stmt = $conn->prepare('SELECT image_path FROM products WHERE product_id = ?')) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $current = $res ? $res->fetch_assoc() : null;
            $stmt->close();
        }
        $oldPath = $current && !empty($current['image_path']) ? $current['image_path'] : null;
        $finalPath = $oldPath; // default keep

        // If remove requested
        if ($remove) {
            if ($oldPath && str_starts_with($oldPath, 'static/image/products/')) {
                $fsOld = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $oldPath);
                @unlink($fsOld);
            }
            $finalPath = null;
        }

        // If new image uploaded, validate and save (replaces old)
        if (!$remove && isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            $size = (int)$_FILES['image']['size'];
            if (isset($allowed[$mime]) && $size <= 5 * 1024 * 1024) {
                $ext = $allowed[$mime];
                $uploadDirFs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'products';
                if (!is_dir($uploadDirFs)) { @mkdir($uploadDirFs, 0777, true); }
                $base = preg_replace('/[^A-Za-z0-9_-]/','_', strtolower(pathinfo($name, PATHINFO_FILENAME)));
                if ($base === '') { $base = 'product'; }
                $filename = $base . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                $destFs = $uploadDirFs . DIRECTORY_SEPARATOR . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destFs)) {
                    // delete old
                    if ($oldPath && str_starts_with($oldPath, 'static/image/products/')) {
                        $fsOld = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $oldPath);
                        @unlink($fsOld);
                    }
                    $finalPath = 'static/image/products/' . $filename;
                }
            }
        }

        // Update product
        if ($finalPath === null) {
            $sql = 'UPDATE products SET product_name = ?, description = ?, price = ?, image_path = NULL WHERE product_id = ?';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssdi', $name, $description, $price, $id);
        } elseif ($finalPath === $oldPath) {
            $sql = 'UPDATE products SET product_name = ?, description = ?, price = ? WHERE product_id = ?';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssdi', $name, $description, $price, $id);
        } else {
            $sql = 'UPDATE products SET product_name = ?, description = ?, price = ?, image_path = ? WHERE product_id = ?';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssdsi', $name, $description, $price, $finalPath, $id);
        }
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();
header('Location: /website/template/admin.php#manage-products');
exit();
