<?php
header('Content-Type: application/json');

require_once 'config.php';

// Get product_id from query string
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if (!$product_id) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing product_id']);
  exit;
}

try {
  // Query to fetch add-ons for this product with inclusion status
  $sql = "
    SELECT a.addon_id, a.addon_name, a.addon_price, pa.is_included
    FROM addons a
    JOIN product_addons pa ON a.addon_id = pa.addon_id
    WHERE pa.product_id = ?
    ORDER BY pa.is_included DESC, a.addon_name ASC
  ";
  
  if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $addons = [];
    
    while ($row = $result->fetch_assoc()) {
      $addons[] = $row;
    }
    
    echo json_encode($addons);
    $stmt->close();
  } else {
    http_response_code(500);
    echo json_encode(['error' => 'Database prepare error']);
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
