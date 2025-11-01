<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /website/php/login_admin.php");
    exit();
}
// Robust config loader in case file moved
$__cfg_loaded = false;
$__try_paths = [
  __DIR__ . '/../php/config.php',
  __DIR__ . '/php/config.php',
  dirname(__DIR__) . '/php/config.php',
  __DIR__ . '/../config.php',
];
foreach ($__try_paths as $__p) {
  if (file_exists($__p)) { require_once $__p; $__cfg_loaded = true; break; }
}
if (!$__cfg_loaded) { die('Configuration file not found.'); }


$allowedSections = ['dashboard','add-product','manage-products','users'];
$active = isset($_GET['section']) && in_array($_GET['section'], $allowedSections, true)
  ? $_GET['section']
  : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Taste of Paradise | Admin Panel</title>
<link rel="stylesheet" href="/website/static/css/admin.css"/>
  </head>
  <body>
    <?php if (!empty($_SESSION['flash'])): ?>
      <div style="margin:12px; padding:10px; background:#e8f5e9; border:1px solid #c8e6c9; color:#256029; border-radius:6px;">
        <?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?>
      </div>
    <?php endif; ?>
    <div class="admin-container">
      <aside class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
          <li><a href="/website/php/admin.php?section=dashboard#dashboard">Dashboard</a></li>
          <li><a href="/website/php/admin.php?section=add-product#add-product">Add Product</a></li>
          <li><a href="/website/php/admin.php?section=manage-products#manage-products">Manage Products</a></li>
          <li><a href="/website/php/admin.php?section=users#users">Users</a></li>
        </ul>
        <div class="logout-section">
<button onclick="if(confirm('Are you sure you want to logout?')) window.location.href='/website/php/logout.php';" class="logout-btn">Logout</button>
        </div>
      </aside>

      <main class="main-content">
        <section id="dashboard" class="dashboard-section" style="display: <?php echo $active==='dashboard'?'block':'none'; ?>;">
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

        <section id="add-product" class="dashboard-section" style="display: <?php echo $active==='add-product'?'block':'none'; ?>;">
          <h1>Add Product</h1>
<form method="POST" action="/website/php/add_product.php" enctype="multipart/form-data">
            <input type="text" name="name" placeholder="Product Name" required>
            <textarea name="description" placeholder="Description"></textarea>
            <input type="number" name="price" placeholder="Price (PHP)" step="0.01" required>
            <div style="margin:8px 0">
              <label style="display:block; margin-bottom:6px; font-weight:600;">Product Image</label>
              <input type="file" name="image" accept="image/*">
              <small style="color:#666;">PNG/JPG/GIF, up to 5MB.</small>
            </div>
            <button type="submit">Add Product</button>
          </form>
        </section>

        <section id="manage-products" class="dashboard-section" style="display: <?php echo $active==='manage-products'?'block':'none'; ?>;">
          <h1>Manage Products</h1>
          <table border="0" cellpadding="8" cellspacing="0" style="width:100%; background:#fff; border-radius:8px; overflow:hidden">
            <thead style="background:#f5f5f5; text-align:left;">
              <tr>
                <th style="padding:10px">ID</th>
                <th style="padding:10px">Name</th>
                <th style="padding:10px">Description</th>
                <th style="padding:10px">Price (₱)</th>
                <th style="padding:10px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $products = $conn->query("SELECT product_id, product_name, description, price FROM products ORDER BY product_id DESC");
                if ($products && $products->num_rows > 0):
                  while ($p = $products->fetch_assoc()):
              ?>
                <tr style="border-top:1px solid #eee">
                  <td style="padding:8px; vertical-align: top; width:60px;"><?php echo $p['product_id']; ?></td>
                  <td style="padding:8px; vertical-align: top;">
                    <?php if (!empty($p['image_path'])): ?>
                      <div style="margin-bottom:6px">
<img src="/website/<?php echo htmlspecialchars($p['image_path']); ?>" alt="thumb" style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #eee" />
                      </div>
                    <?php endif; ?>
<form method="POST" action="/website/php/update_product.php" enctype="multipart/form-data" style="display:flex; flex-wrap:wrap; gap:6px; align-items:flex-start;">
                      <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>" />
                      <input type="text" name="name" value="<?php echo htmlspecialchars($p['product_name']); ?>" required style="width:180px" />
                      <textarea name="description" style="width:260px;height:60px"><?php echo htmlspecialchars($p['description']); ?></textarea>
                      <input type="number" name="price" step="0.01" value="<?php echo number_format((float)$p['price'], 2, '.', ''); ?>" required style="width:120px" />
                      <input type="file" name="image" accept="image/*" style="width:240px" />
                      <?php if (!empty($p['image_path'])): ?>
                        <label style="display:flex; align-items:center; gap:6px; font-size:12px; color:#555;">
                          <input type="checkbox" name="remove_image" value="1" /> Remove image
                        </label>
                      <?php endif; ?>
                      <button type="submit" style="background:#1a1a1a;color:#fff;padding:6px 10px;border-radius:6px">Save</button>
                    </form>
                  </td>
                  <td style="display:none"></td>
                  <td style="display:none"></td>
                  <td style="padding:8px; vertical-align: top;">
<form method="POST" action="/website/php/delete_product.php" onsubmit="return confirm('Delete this product?');">
                      <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>" />
                      <button type="submit" style="background:#c0392b;color:#fff;padding:6px 10px;border-radius:6px">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php
                  endwhile;
                else:
              ?>
                <tr><td colspan="5" style="padding:12px; text-align:center; color:#666;">No products found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </section>

        <section id="users" class="dashboard-section" style="display: <?php echo $active==='users'?'block':'none'; ?>;">
          <h1>Users</h1>
          <table border="0" cellpadding="8" cellspacing="0" style="width:100%; background:#fff; border-radius:8px; overflow:hidden">
            <thead style="background:#f5f5f5; text-align:left;">
              <tr>
                <th style="padding:10px">ID</th>
                <th style="padding:10px">Name</th>
                <th style="padding:10px">Email</th>
                <th style="padding:10px">Role</th>
                <th style="padding:10px">Created</th>
                <th style="padding:10px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $users = $conn->query("SELECT user_id, first_name, last_name, email, role, created_at FROM users ORDER BY user_id DESC");
                if ($users && $users->num_rows > 0):
                  while ($u = $users->fetch_assoc()):
                    $fullName = trim($u['first_name'] . ' ' . $u['last_name']);
              ?>
                <tr style="border-top:1px solid #eee">
                  <td style="padding:8px;">&num;<?php echo $u['user_id']; ?></td>
                  <td style="padding:8px;">&nbsp;<?php echo htmlspecialchars($fullName); ?></td>
                  <td style="padding:8px;">&nbsp;<?php echo htmlspecialchars($u['email']); ?></td>
                  <td style="padding:8px; text-transform:capitalize;">&nbsp;<?php echo htmlspecialchars($u['role']); ?></td>
                  <td style="padding:8px;">&nbsp;<?php echo htmlspecialchars($u['created_at']); ?></td>
                  <td style="padding:8px;">
                    <?php if ($u['role'] !== 'admin'): ?>
<form method="POST" action="/website/php/delete_user.php" onsubmit="return confirm('Delete this user?');" style="display:inline-block">
                      <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>" />
                      <button type="submit" style="background:#c0392b;color:#fff;padding:6px 10px;border-radius:6px">Delete</button>
                    </form>
                    <?php else: ?>
                      <span style="color:#999;">Protected</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php
                  endwhile;
                else:
              ?>
                <tr><td colspan="6" style="padding:12px; text-align:center; color:#666;">No users found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </section>

      </main>
    </div>

    <script>
      (function(){
        var sectionIds = ['dashboard','add-product','manage-products','users'];
        function byId(id){ return document.getElementById(id); }
        function show(id){
          sectionIds.forEach(function(s){
            var el = byId(s);
            if (el) el.style.display = (s === id ? 'block' : 'none');
          });
        }
        function current(){ return location.hash ? location.hash.slice(1) : 'dashboard'; }

        document.addEventListener('DOMContentLoaded', function(){
          show(current());
          var links = document.querySelectorAll('.sidebar a[href^="#"]');
          links.forEach(function(a){
            a.addEventListener('click', function(){
              var id = a.getAttribute('href').slice(1);
              show(id);
            });
          });
        });
        window.addEventListener('hashchange', function(){ show(current()); });
      })();
    </script>
  </body>
</html>

<?php $conn->close(); ?>
      });
    </script>
  </body>
</html>

<?php $conn->close(); ?>
