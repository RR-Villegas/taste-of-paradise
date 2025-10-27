<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
include '../php/config.php';
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Taste of Paradise | Admin Panel</title>
    <link rel="stylesheet" href="../static/css/admin.css"/>
  </head>
  <body>
    <div class="admin-container">
      <aside class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
          <li><a href="#dashboard">Dashboard</a></li>
          <li><a href="#add-product">Add Product</a></li>
          <li><a href="#manage-products">Manage Products</a></li>
          <li><a href="#users">Users</a></li>
          <li><a href="#orders">Orders</a></li>
          <li><a href="#settings">Settings</a></li>
        </ul>
        <div class="logout-section">
          <button onclick="if(confirm('Are you sure you want to logout?')) window.location.href='../php/logout.php';" class="logout-btn">Logout</button>
        </div>
      </aside>

      <main class="main-content">
        <section id="dashboard" class="dashboard-section">
          <h1>Dashboard</h1>
          <div class="stats-grid">
            <div class="stat-card">
              <h3>Total Users</h3>
              <p><?php
                $sql = "SELECT COUNT(*) as count FROM users";
                $result = $conn->query($sql);
                echo $result->fetch_assoc()['count'];
              ?></p>
            </div>
            <div class="stat-card">
              <h3>Total Products</h3>
              <p><?php
                $sql = "SELECT COUNT(*) as count FROM products";
                $result = $conn->query($sql);
                echo $result->fetch_assoc()['count'];
              ?></p>
            </div>
            <div class="stat-card">
              <h3>Total Orders</h3>
              <p><?php
                echo "0";
              ?></p>
            </div>
          </div>
        </section>

        <section id="add-product" class="dashboard-section" style="display: none;">
          <h1>Add Product</h1>
          <form method="POST" action="../php/add_product.php">
            <input type="text" name="name" placeholder="Product Name" required>
            <textarea name="description" placeholder="Description"></textarea>
            <input type="number" name="price" placeholder="Price" step="0.01" required>
            <button type="submit">Add Product</button>
          </form>
        </section>

        <section id="manage-products" class="dashboard-section" style="display: none;">
          <h1>Manage Products</h1>
          <div class="product-list">
            <h2>Existing Products</h2>
            <?php
              $sql = "SELECT * FROM products ORDER BY created_at DESC";
              $result = $conn->query($sql);
              if ($result->num_rows > 0) {
                echo "<table class='product-table'>";
                echo "<thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Price</th><th>Actions</th></tr></thead><tbody>";
                while ($row = $result->fetch_assoc()) {
                  echo "<tr>";
                  echo "<td>" . $row['product_id'] . "</td>";
                  echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                  echo "<td>$" . number_format($row['price'], 2) . "</td>";
                  echo "<td><button class='edit-btn' onclick='showEditForm(" . $row['product_id'] . ", \"" . addslashes($row['product_name']) . "\", \"" . addslashes($row['description']) . "\", " . $row['price'] . ")'>Edit</button> <button class='delete-btn' onclick='if(confirm(\"Are you sure you want to delete this product?\")) window.location.href=\"../php/delete_product.php?id=" . $row['product_id'] . "\"'>Delete</button></td>";
                  echo "</tr>";
                }
                echo "</tbody></table>";
              } else {
                echo "<p>No products found.</p>";
              }
            ?>
          </div>
          <div id="edit-product-form" class="edit-product-form" style="display: none;">
            <h2>Edit Product</h2>
            <form method="POST" action="../php/edit_product.php">
              <input type="hidden" id="edit-product-id" name="product_id">
              <input type="text" id="edit-name" name="name" placeholder="Product Name" required>
              <textarea id="edit-description" name="description" placeholder="Description"></textarea>
              <input type="number" id="edit-price" name="price" placeholder="Price" step="0.01" required>
              <button type="submit">Update Product</button>
              <button type="button" onclick="hideEditForm()">Cancel</button>
            </form>
          </div>
        </section>

      </main>
    </div>

    <script>
      const sections = document.querySelectorAll('.dashboard-section');
      const links = document.querySelectorAll('.sidebar a');

      links.forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          const targetId = link.getAttribute('href').substring(1);
          sections.forEach(section => {
            section.style.display = section.id === targetId ? 'block' : 'none';
          });
        });
      });

      function showEditForm(id, name, description, price) {
        document.getElementById('edit-product-id').value = id;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-description').value = description;
        document.getElementById('edit-price').value = price;
        document.getElementById('edit-product-form').style.display = 'block';
      }

      function hideEditForm() {
        document.getElementById('edit-product-form').style.display = 'none';
      }
    </script>
  </body>
</html>

<?php $conn->close(); ?>
