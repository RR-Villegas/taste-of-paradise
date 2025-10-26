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
    <link rel="stylesheet" href="../static/css/homepage.css"/>
    <style>
      .admin-container {
        display: flex;
        min-height: 100vh;
      }
      .sidebar {
        width: 250px;
        background-color: #4b2e0b;
        color: #fff8e1;
        padding: 2rem 1rem;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
      }
      .sidebar h2 {
        text-align: center;
        margin-bottom: 2rem;
        color: #ffcc80;
      }
      .sidebar ul {
        list-style: none;
      }
      .sidebar ul li {
        margin-bottom: 1rem;
      }
      .sidebar ul li a {
        color: #fff8e1;
        text-decoration: none;
        display: block;
        padding: 0.5rem;
        border-radius: 5px;
        transition: background-color 0.3s;
      }
      .sidebar ul li a:hover {
        background-color: #5a3d2a;
      }
      .main-content {
        flex: 1;
        padding: 2rem;
        background: linear-gradient(180deg, #f5e1c0, #d8b899, #8b5e3c);
      }
      .dashboard-section {
        background: #fffaf3;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 16px rgba(75, 46, 11, 0.2);
        margin-bottom: 2rem;
      }
      .dashboard-section h1 {
        color: #4b2e0b;
        margin-bottom: 1rem;
      }
      .stats-grid {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
      }
      .stat-card {
        background: #fff;
        border-radius: 10px;
        padding: 1rem;
        flex: 1;
        min-width: 200px;
        text-align: center;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      }
      .stat-card h3 {
        color: #4b2e0b;
        margin-bottom: 0.5rem;
      }
      .stat-card p {
        font-size: 2rem;
        color: #5a4631;
        font-weight: bold;
      }
    </style>
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
                // Assuming an orders table exists, for now placeholder
                echo "0"; // Replace with actual query
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

        <!-- Add more sections as needed -->
      </main>
    </div>

    <script>
      // Simple navigation script
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
