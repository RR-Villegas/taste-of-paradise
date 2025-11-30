<?php
require_once 'php/config.php';

echo "=== Checking Database ===\n\n";

// Check product_addons
echo "Product-Addons Links:\n";
$result = $conn->query("SELECT pa.product_id, pa.addon_id, pa.is_included, a.addon_name, p.product_name 
                        FROM product_addons pa 
                        JOIN addons a ON pa.addon_id = a.addon_id 
                        JOIN products p ON pa.product_id = p.product_id
                        ORDER BY pa.product_id");

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $included = $row['is_included'] ? 'FREE' : 'PAID';
    echo "  Product {$row['product_id']} ({$row['product_name']}): {$row['addon_name']} - {$included}\n";
  }
} else {
  echo "  ERROR: No product_addons found!\n";
}

echo "\nTesting API endpoint for product_id=1:\n";
$result = $conn->query("SELECT a.addon_id, a.addon_name, a.addon_price, pa.is_included
                        FROM addons a
                        JOIN product_addons pa ON a.addon_id = pa.addon_id
                        WHERE pa.product_id = 1
                        ORDER BY pa.is_included DESC, a.addon_name ASC");

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    echo json_encode($row) . "\n";
  }
} else {
  echo "  No add-ons for product 1\n";
}

$conn->close();
?>
