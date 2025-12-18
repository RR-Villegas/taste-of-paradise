<?php
require_once 'php/config.php';

echo "Testing API call to get_product_addons.php for product_id=1:\n\n";

// Simulate the API call
$_GET['product_id'] = 1;
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if (!$product_id) {
  echo "ERROR: Missing product_id\n";
  exit;
}

try {
  $stmt = $pdo->prepare('
    SELECT a.addon_id, a.addon_name, a.addon_price, pa.is_included
    FROM addons a
    JOIN product_addons pa ON a.addon_id = pa.addon_id
    WHERE pa.product_id = ?
    ORDER BY pa.is_included DESC, a.addon_name ASC
  ');
  $stmt->execute([$product_id]);
  $addons = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  echo "Response JSON:\n";
  echo json_encode($addons, JSON_PRETTY_PRINT);
  echo "\n\nNumber of add-ons: " . count($addons) . "\n";
} catch (Exception $e) {
  echo "Database error: " . $e->getMessage();
}
?>
