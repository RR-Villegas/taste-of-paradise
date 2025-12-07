<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // 1. We MUST still set the HTTP status code for proper compliance.
    http_response_code(401);
    if (ob_get_level() > 0) {
        ob_clean();
    }
    include 'error_401.php'; 
    
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


$allowedSections = ['dashboard','add-product','manage-products','users','manage-addons'];
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
<link rel="stylesheet" href="/taste-of-paradise-a/static/css/admin.css"/>
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
          <li><a href="/taste-of-paradise-a/php/admin.php?section=dashboard#dashboard">Dashboard</a></li>
          <li><a href="/taste-of-paradise-a/php/admin.php?section=add-product#add-product">Add Product</a></li>
          <li><a href="/taste-of-paradise-a/php/admin.php?section=manage-products#manage-products">Manage Products</a></li>
          <li><a href="/taste-of-paradise-a/php/admin.php?section=manage-addons#manage-addons">Manage Add-ons</a></li>
          <li><a href="/taste-of-paradise-a/php/admin.php?section=users#users">Users</a></li>
        </ul>
        <div class="logout-section">
<button onclick="if(confirm('Are you sure you want to logout?')) window.location.href='/taste-of-paradise-a/php/logout.php';" class="logout-btn">Logout</button>
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
          <form class="product-form" method="POST" action="/taste-of-paradise-a/php/add_product.php" enctype="multipart/form-data">
            <div class="field">
              <label for="prod-name">Name</label>
              <input id="prod-name" type="text" name="name" placeholder="e.g. Iced Matcha Latte" required>
            </div>
            <div class="field">
              <label for="prod-category">Category</label>
              <select id="prod-category" name="category">
                <option value="drink">Drink</option>
                <option value="food">Food</option>
              </select>
            </div>
            <div class="field">
              <label for="prod-size-type">Size Type</label>
              <select id="prod-size-type" name="size_type" onchange="toggleSizePrices()">
                <option value="none">No Size (fixed price)</option>
                <option value="s_m_l">S / M / L (toggle sizes)</option>
              </select>
            </div>
            <div id="base-price-section" class="field">
              <label for="prod-price">Base Price (PHP)</label>
              <input id="prod-price" type="number" name="price" placeholder="e.g. 120" step="0.01" min="0" required>
            </div>
            <div id="size-prices-section" style="display:none;">
              <p style="margin:10px 0; color:#5a4631; font-weight:bold;">Select sizes and enter prices:</p>
              <div style="display:grid; grid-template-columns: auto 1fr; gap:12px; align-items:center;">
                <label><input type="checkbox" name="size_s" value="1"> Small (S)</label>
                <input type="number" name="price_s" placeholder="e.g. 100" step="0.01" min="0" disabled>
                
                <label><input type="checkbox" name="size_m" value="1"> Medium (M)</label>
                <input type="number" name="price_m" placeholder="e.g. 120" step="0.01" min="0" disabled>
                
                <label><input type="checkbox" name="size_l" value="1"> Large (L)</label>
                <input type="number" name="price_l" placeholder="e.g. 150" step="0.01" min="0" disabled>
              </div>
            </div>
            <div id="addons-section" style="display:none;">
              <p style="margin:15px 0 10px 0; color:#5a4631; font-weight:bold;">Available Add-ons (select which apply, check "Included" for free add-ons):</p>
              <div id="addons-list" style="display:grid; gap:12px;">
                <?php
                  $addonsResult = $conn->query("SELECT addon_id, addon_name, addon_price FROM addons ORDER BY addon_name ASC");
                  if ($addonsResult && $addonsResult->num_rows > 0):
                    while ($addon = $addonsResult->fetch_assoc()):
                ?>
                  <div style="display:flex; align-items:center; gap:12px; padding:10px; border:1px solid #ddd; border-radius:4px; background:#fafafa;">
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer; flex:1;">
                      <input type="checkbox" name="addon_ids[]" value="<?php echo $addon['addon_id']; ?>" />
                      <span style="font-weight:500;"><?php echo htmlspecialchars($addon['addon_name']); ?></span>
                      <span style="color:#666; font-size:0.9rem;">(+₱<?php echo number_format((float)$addon['addon_price'], 2); ?>)</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer; white-space:nowrap;">
                      <input type="checkbox" name="addon_included_<?php echo $addon['addon_id']; ?>" value="1" />
                      <span style="font-size:0.9rem; color:#4b2e0b;">Included (FREE)</span>
                    </label>
                  </div>
                <?php
                    endwhile;
                  else:
                ?>
                  <p style="color:#999;">No add-ons available. Create them in the <strong>Manage Add-ons</strong> section first.</p>
                <?php endif; ?>
              </div>
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
                            <img src="/taste-of-paradise-a/<?php echo htmlspecialchars($p['image_path']); ?>" alt="thumb" />
                          </div>
                        <?php endif; ?>
                        <form class="product-row-form" method="POST" action="/taste-of-paradise-a/php/update_product.php" enctype="multipart/form-data">
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
                      <form method="POST" action="/taste-of-paradise-a/php/delete_product.php" onsubmit="return confirm('Delete this product?');">
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
            <form method="POST" action="/taste-of-paradise-a/php/add_user.php" class="user-add-form">
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

        <section id="manage-addons" class="dashboard-section" style="display: <?php echo $active==='manage-addons'?'block':'none'; ?>;">
          <h1>Manage Add-ons</h1>
          <p style="margin-bottom: 1rem; color:#5a4631; font-size:0.95rem;">
            Create and manage add-ons (e.g., Boba, Extra Sugar) that can be assigned to drinks.
          </p>
          
          <div class="user-add-card">
            <h2>Add New Add-on</h2>
            <form method="POST" action="/taste-of-paradise-a/php/manage_addons.php" class="user-add-form">
              <input type="hidden" name="action" value="add">
              <input type="text" name="addon_name" placeholder="Add-on name (e.g., Boba, Extra Shot)" required />
              <input type="number" name="addon_price" placeholder="Price" step="0.01" min="0" required />
              <button type="submit">Create Add-on</button>
            </form>
          </div>

          <div style="margin-top: 2rem;">
            <h3>Available Add-ons</h3>
            <div class="admin-table-wrapper">
              <table class="admin-table" border="0" cellpadding="0" cellspacing="0">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price (₱)</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $addons = $conn->query("SELECT addon_id, addon_name, addon_price FROM addons ORDER BY addon_name ASC");
                    if ($addons && $addons->num_rows > 0):
                      while ($addon = $addons->fetch_assoc()):
                  ?>
                    <tr>
                      <td>#<?php echo $addon['addon_id']; ?></td>
                      <td><?php echo htmlspecialchars($addon['addon_name']); ?></td>
                      <td>₱<?php echo number_format((float)$addon['addon_price'], 2); ?></td>
                      <td>
                        <form method="POST" action="/taste-of-paradise-a/php/manage_addons.php" style="display:inline;" onsubmit="return confirm('Delete this add-on?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="addon_id" value="<?php echo $addon['addon_id']; ?>" />
                          <button type="submit" class="danger-btn" style="padding:6px 10px; font-size:0.9rem;">Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php
                      endwhile;
                    else:
                  ?>
                    <tr><td colspan="4" style="padding:12px; text-align:center; color:#666;">No add-ons yet.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

      </main>
    </div>

    <script>
      function toggleSizePrices(){
        var sizeType = document.getElementById('prod-size-type').value;
        var basePriceSection = document.getElementById('base-price-section');
        var sizePricesSection = document.getElementById('size-prices-section');
        var basePriceInput = document.getElementById('prod-price');
        
        if (sizeType === 's_m_l') {
          basePriceSection.style.display = 'none';
          sizePricesSection.style.display = 'block';
          basePriceInput.removeAttribute('required');
          enableSizeCheckboxes();
        } else {
          basePriceSection.style.display = 'block';
          sizePricesSection.style.display = 'none';
          basePriceInput.setAttribute('required', 'required');
        }
        toggleAddonsSection();
      }
      
      function enableSizeCheckboxes(){
        var inputs = document.querySelectorAll('#size-prices-section input[type="checkbox"]');
        var priceInputs = document.querySelectorAll('#size-prices-section input[type="number"]');
        
        inputs.forEach((checkbox, index) => {
          checkbox.addEventListener('change', () => {
            priceInputs[index].disabled = !checkbox.checked;
            if (!checkbox.checked) priceInputs[index].value = '';
          });
        });
      }
      
      function toggleAddonsSection(){
        var category = document.getElementById('prod-category').value;
        var addonsSection = document.getElementById('addons-section');
        if (category === 'drink') {
          addonsSection.style.display = 'block';
          // Setup addon checkbox behavior
          document.querySelectorAll('#addons-list input[type="checkbox"][name="addon_ids[]"]').forEach(mainCheckbox => {
            const addonId = mainCheckbox.value;
            const includedCheckbox = document.querySelector(`input[name="addon_included_${addonId}"]`);
            
            function syncCheckboxes() {
              if (!mainCheckbox.checked) {
                includedCheckbox.checked = false;
                includedCheckbox.disabled = true;
              } else {
                includedCheckbox.disabled = false;
              }
            }
            
            mainCheckbox.addEventListener('change', syncCheckboxes);
            syncCheckboxes();
          });
        } else {
          addonsSection.style.display = 'none';
        }
      }
      
      document.addEventListener('DOMContentLoaded', function(){
        var categorySelect = document.getElementById('prod-category');
        if (categorySelect) {
          categorySelect.addEventListener('change', toggleAddonsSection);
          toggleAddonsSection(); // Initialize on page load
        }
      });
      
      (function(){
        var sectionIds = ['dashboard','add-product','manage-products','users','manage-addons'];
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
