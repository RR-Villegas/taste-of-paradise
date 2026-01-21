<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../php/admin.php');
    exit();
}

$id = (int)($_POST['product_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$removeImage = isset($_POST['remove_image']);

if ($id <= 0 || $name === '') {
    header('Location: ../php/admin.php?section=manage-products');
    exit();
}

/* ================= FETCH OLD DATA ================= */

$stmt = $conn->prepare("
    SELECT price, size_type, size_prices, image_path, category
    FROM products
    WHERE product_id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current) {
    header('Location: ../php/admin.php?section=manage-products');
    exit();
}

$price      = (float)$current['price'];
$sizeType   = $current['size_type'];
$sizePrices = $current['size_prices'];
$category   = $current['category'];
$imagePath  = $current['image_path'];

/* ================= PRICE UPDATE (SAFE) ================= */

/* ================= PRICE UPDATE (CANONICAL) ================= */

// If S/M/L prices are submitted → S/M/L mode wins
if (
    isset($_POST['price_s'], $_POST['price_m'], $_POST['price_l']) &&
    $_POST['price_s'] !== '' &&
    $_POST['price_m'] !== '' &&
    $_POST['price_l'] !== ''
) {
    $s = (float)$_POST['price_s'];
    $m = (float)$_POST['price_m'];
    $l = (float)$_POST['price_l'];

    $sizePrices = json_encode([
        'S' => $s,
        'M' => $m,
        'L' => $l
    ]);

    $sizeType = 's_m_l';
    $price = $m; // DEFAULT TO MEDIUM

} else {
    // Single price mode
    if (isset($_POST['price']) && $_POST['price'] !== '') {
        $price = (float)$_POST['price'];
    }

    $sizeType = 'none';
    $sizePrices = null;
}


/* ================= IMAGE HANDLING ================= */

$finalImagePath = $imagePath;

// Remove image
if ($removeImage && $imagePath) {
    $fs = dirname(__DIR__) . '/' . $imagePath;
    if (file_exists($fs)) @unlink($fs);
    $finalImagePath = null;
}

// Upload new image
if (!$removeImage && isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    $mime = mime_content_type($_FILES['image']['tmp_name']);

    if (isset($allowed[$mime]) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
        $dir = dirname(__DIR__) . '/static/image/products/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ext = $allowed[$mime];
        $file = uniqid('prod_', true) . '.' . $ext;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $file)) {
            if ($imagePath && file_exists(dirname(__DIR__) . '/' . $imagePath)) {
                @unlink(dirname(__DIR__) . '/' . $imagePath);
            }
            $finalImagePath = 'static/image/products/' . $file;
        }
    }
}

/* ================= UPDATE ================= */

if ($finalImagePath === null) {
    $sql = "
        UPDATE products
        SET product_name=?, description=?, price=?, image_path=NULL,
            size_type=?, size_prices=?
        WHERE product_id=?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssdssi',
        $name, $description, $price,
        $sizeType, $sizePrices, $id
    );
} elseif ($finalImagePath === $imagePath) {
    $sql = "
        UPDATE products
        SET product_name=?, description=?, price=?,
            size_type=?, size_prices=?
        WHERE product_id=?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssdssi',
        $name, $description, $price,
        $sizeType, $sizePrices, $id
    );
} else {
    $sql = "
        UPDATE products
        SET product_name=?, description=?, price=?, image_path=?,
            size_type=?, size_prices=?
        WHERE product_id=?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssdsssi',
        $name, $description, $price, $finalImagePath,
        $sizeType, $sizePrices, $id
    );
}

$stmt->execute();
$stmt->close();

$conn->close();

header('Location: ../php/admin.php?section=manage-products#manage-products');
exit();
