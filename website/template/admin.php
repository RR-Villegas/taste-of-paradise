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
          <li><a href="../php/logout.php">Logout</a></li>
        </ul>
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
    </script>
  </body>
</html>

<?php $conn->close(); ?>
