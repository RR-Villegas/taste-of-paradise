<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_GET['redirect'] = '/taste-of-paradise-a/index.php';
    include 'error_401.php';
    exit();
}
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'] ?? 0;
    $category = $_POST['category'] ?? 'drink';
    $sizeType = $_POST['size_type'] ?? 'none';
    $sizePrices = null;

    // Parse size prices based on size type
    if ($sizeType === 's_m_l') {
        $sizes = [];
        if (isset($_POST['size_s']) && isset($_POST['price_s'])) {
            $sizes['S'] = (float)$_POST['price_s'];
        }
        if (isset($_POST['size_m']) && isset($_POST['price_m'])) {
            $sizes['M'] = (float)$_POST['price_m'];
        }
        if (isset($_POST['size_l']) && isset($_POST['price_l'])) {
            $sizes['L'] = (float)$_POST['price_l'];
        }
        if (!empty($sizes)) {
            $sizePrices = json_encode($sizes);
        }
    }

    // Ensure columns exist (compatible with MySQL/MariaDB)
    $check = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='image_path'");
    if ($check) {
        $row = $check->fetch_assoc();
        if ((int)($row['c'] ?? 0) === 0) {
            @$conn->query("ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL");
        }
    }
    $checkSize = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='size_type'");
    if ($checkSize) {
        $rowSize = $checkSize->fetch_assoc();
        if ((int)($rowSize['c'] ?? 0) === 0) {
            @$conn->query("ALTER TABLE products ADD COLUMN size_type VARCHAR(50) DEFAULT 'none'");
            @$conn->query("ALTER TABLE products ADD COLUMN size_prices JSON NULL");
        }
    }
    $checkCategory = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='category'");
    if ($checkCategory) {
        $rowCategory = $checkCategory->fetch_assoc();
        if ((int)($rowCategory['c'] ?? 0) === 0) {
            @$conn->query("ALTER TABLE products ADD COLUMN category VARCHAR(50) DEFAULT 'drink'");
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
        ? "INSERT INTO products (product_name, description, price, image_path, category, size_type, size_prices) VALUES (?, ?, ?, ?, ?, ?, ?)"
        : "INSERT INTO products (product_name, description, price, category, size_type, size_prices) VALUES (?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        if ($imageRelPath) {
            $stmt->bind_param("ssdssss", $name, $description, $price, $imageRelPath, $category, $sizeType, $sizePrices);
        } else {
            $stmt->bind_param("ssdsss", $name, $description, $price, $category, $sizeType, $sizePrices);
        }
        if ($stmt->execute()) {
            $productId = $stmt->insert_id;
            $_SESSION['flash'] = $_SESSION['flash'] ?? "Product added successfully.";
            
            // Link selected add-ons via product_addons junction table
            if (isset($_POST['addon_ids']) && is_array($_POST['addon_ids'])) {
                $linkStmt = $conn->prepare("INSERT INTO product_addons (product_id, addon_id, is_included) VALUES (?, ?, ?)");
                if ($linkStmt) {
                    foreach ($_POST['addon_ids'] as $addonId) {
                        $addonId = (int)$addonId;
                        if ($addonId > 0) {
                            // Check if this addon is marked as included
                            $isIncluded = isset($_POST['addon_included_' . $addonId]) ? 1 : 0;
                            $linkStmt->bind_param("iii", $productId, $addonId, $isIncluded);
                            @$linkStmt->execute();
                        }
                    }
                    $linkStmt->close();
                }
            }
        } else {
            $_SESSION['flash'] = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['flash'] = 'Failed to add product (prepare error).';
    }
}

$conn->close();
header("Location: /taste-of-paradise-a/php/admin.php?section=add-product#add-product");
exit();
?>
