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
              <h3>Total Products</h3>
              <p><?php
                $sql = "SELECT COUNT(*) as count FROM products";
                $result = $conn->query($sql);
                echo $result ? (int)$result->fetch_assoc()['count'] : 0;
              ?></p>
            </div>
            <div class="stat-card" style="grid-column: span 2;">
              <h3>Most Recent Product</h3>
              <p style="margin:4px 0 0 0; font-size:0.95rem;">
                <?php
                  $latestSql = "SELECT product_name, price, created_at FROM products ORDER BY created_at DESC, product_id DESC LIMIT 1";
                  $latestRes = $conn->query($latestSql);
                  if ($latestRes && $latestRes->num_rows > 0) {
                    $latest = $latestRes->fetch_assoc();
                    $name = htmlspecialchars($latest['product_name'] ?? '');
                    $price = number_format((float)($latest['price'] ?? 0), 2);
                    $created = htmlspecialchars($latest['created_at'] ?? '');
                    echo $name !== ''
                      ? $name . " — ₱" . $price . " (" . $created . ")"
                      : "No products yet.";
                  } else {
                    echo "No products yet.";
                  }
                ?>
              </p>
            </div>
          </div>
        </section>

        <section id="add-product" class="dashboard-section" style="display: <?php echo $active==='add-product'?'block':'none'; ?>;">
          <h1>Add Product</h1>
          <p style="margin-bottom: 1rem; color:#5a4631; font-size:0.95rem;">
            Create a new drink or item for the menu. You can update or remove it later from the Manage Products tab.
          </p>
          <form class="product-form" method="POST" action="/website/php/add_product.php" enctype="multipart/form-data">
            <div class="field">
              <label for="prod-name">Name</label>
              <input id="prod-name" type="text" name="name" placeholder="e.g. Iced Matcha Latte" required>
            </div>
            <div class="field">
              <label for="prod-price">Price (PHP)</label>
              <input id="prod-price" type="number" name="price" placeholder="e.g. 120" step="0.01" min="0" required>
            </div>
            <div class="field field-full">
              <label for="prod-desc">Description</label>
              <textarea id="prod-desc" name="description" placeholder="Short description, flavor notes, size, etc."></textarea>
            </div>
            <div class="field field-full">
              <label for="prod-image">Product Image</label>
              <input id="prod-image" type="file" name="image" accept="image/*">
              <small class="field-help">PNG/JPG/GIF/WebP, up to 5MB. Optional.</small>
            </div>
            <div class="product-form-actions">
              <button type="submit" class="primary-btn">Add Product</button>
            </div>
          </form>
        </section>

        <section id="manage-products" class="dashboard-section" style="display: <?php echo $active==='manage-products'?'block':'none'; ?>;">
          <h1>Manage Products</h1>
          <p style="margin-bottom: 1rem; color:#5a4631; font-size:0.95rem;">
            Edit product details, update photos, or remove items from the menu.
          </p>
          <div class="admin-table-wrapper">
            <table class="admin-table admin-products-table" border="0" cellpadding="0" cellspacing="0">
              <thead>
                <tr>
                  <th style="width:60px;">ID</th>
                  <th>Product</th>
                  <th style="width:120px;">Price (₱)</th>
                  <th style="width:120px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $products = $conn->query("SELECT product_id, product_name, description, price, image_path FROM products ORDER BY product_id DESC");
                  if ($products && $products->num_rows > 0):
                    while ($p = $products->fetch_assoc()):
                ?>
                  <tr>
                    <td>#<?php echo $p['product_id']; ?></td>
                    <td>
                      <div class="product-row">
                        <?php if (!empty($p['image_path'])): ?>
                          <div class="product-thumb">
                            <img src="/website/<?php echo htmlspecialchars($p['image_path']); ?>" alt="thumb" />
                          </div>
                        <?php endif; ?>
                        <form class="product-row-form" method="POST" action="/website/php/update_product.php" enctype="multipart/form-data">
                          <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>" />
                          <div class="field field-full">
                            <label>Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($p['product_name']); ?>" required />
                          </div>
                          <div class="field field-full">
                            <label>Description</label>
                            <textarea name="description"><?php echo htmlspecialchars($p['description']); ?></textarea>
                          </div>
                          <div class="field field-full">
                            <label>Image</label>
                            <input type="file" name="image" accept="image/*" />
                            <?php if (!empty($p['image_path'])): ?>
                              <label class="field-inline">
                                <input type="checkbox" name="remove_image" value="1" /> Remove current image
                              </label>
                            <?php endif; ?>
                          </div>
                          <div class="product-row-actions">
                            <button type="submit" class="secondary-btn">Save</button>
                          </div>
                        </form>
                      </div>
                    </td>
                    <td>
                      ₱<?php echo number_format((float)$p['price'], 2, '.', ''); ?>
                    </td>
                    <td>
                      <form method="POST" action="/website/php/delete_product.php" onsubmit="return confirm('Delete this product?');">
                        <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>" />
                        <button type="submit" class="danger-btn">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php
                    endwhile;
                  else:
                ?>
                  <tr><td colspan="4" style="padding:12px; text-align:center; color:#666;">No products found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section id="users" class="dashboard-section" style="display: <?php echo $active==='users'?'block':'none'; ?>;">
          <h1>Users</h1>

          <div class="user-add-card">
            <h2>Add User</h2>
            <form method="POST" action="/website/php/add_user.php" class="user-add-form">
              <input type="text" name="first_name" placeholder="First name" required />
              <input type="text" name="last_name" placeholder="Last name" required />
              <input type="text" name="username" placeholder="Username" required />
              <input type="email" name="email" placeholder="Email" required />
              <input type="password" name="password" placeholder="Password (min 6 chars)" required />
              <select name="role">
                <option value="user">User</option>
                <option value="employee">Employee</option>
                <option value="admin">Admin</option>
              </select>
              <button type="submit">Create</button>
            </form>
          </div>
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
