<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    if (ob_get_level() > 0) {
        ob_clean();
    }
    include 'error_401.php'; 
    exit(); 
}

// Robust config loader
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

// Load helper functions
$__helpers_loaded = false;
$__helper_paths = [
  __DIR__ . '/../php/helpers.php',
  __DIR__ . '/php/helpers.php',
  dirname(__DIR__) . '/php/helpers.php',
  __DIR__ . '/../helpers.php',
];

foreach ($__helper_paths as $__h) {
  if (file_exists($__h)) {
    require_once $__h;
    $__helpers_loaded = true;
    break;
  }
}

if (!$__helpers_loaded) {
  die('helpers.php not found.');
}

// REMOVED 'users' from allowed sections
$allowedSections = ['dashboard','add-product','manage-products','manage-addons', 'announcement'];
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
    <link rel="stylesheet" href="../static/css/admin.css"/>
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
          <li><a href="../php/admin.php?section=dashboard#dashboard">Dashboard</a></li>
          <li><a href="../php/admin.php?section=add-product#add-product">Add Product</a></li>
          <li><a href="../php/admin.php?section=manage-products#manage-products">Manage Products</a></li>
          <li><a href="../php/admin.php?section=manage-addons#manage-addons">Manage Add-ons</a></li>
          <li><a href="../php/admin.php?section=announcement#announcement">Announcements</a>
        </ul>
        <div class="logout-section">
          <button onclick="if(confirm('Are you sure you want to logout?')) window.location.href='../php/logout.php';" class="logout-btn">Logout</button>
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
          <div style="margin-top:2rem;">
            <h3 style="color:#fff">Published Announcements</h3>
            <?php
              $announcements = $conn->query(
                "SELECT * FROM announcement_blog ORDER BY created_at DESC LIMIT 3"
              );
            ?>
            <?php if ($announcements && $announcements->num_rows): ?>
              <?php while ($a = $announcements->fetch_assoc()): ?>
                <div class="published-card">
                  <h3><?= htmlspecialchars($a['title']) ?></h3>
                  <small><?= $a['created_at'] ?></small>
                  <div class="markdown-content" style="margin-top:8px;">
                    <?= renderMarkdown($a['content']) ?>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <p style="margin-top:1rem;">No announcements yet.</p>
            <?php endif; ?>
          </div>
        </section>

        <section id="add-product" class="dashboard-section" style="display: <?php echo $active==='add-product'?'block':'none'; ?>;">
          <h1>Add Product</h1>
          <p style="margin-bottom: 1rem; color:#fff; font-size:0.95rem;">
            Create a new drink or item for the menu. You can update or remove it later from the Manage Products tab.
          </p>
          <form class="product-form" method="POST" action="../php/add_product.php" enctype="multipart/form-data">
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
              <p style="margin:10px 0; color:#fbead2; font-weight:bold;">Select sizes and enter prices:</p>
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
              <p style="margin:15px 0 8px; color:#fbead2; font-weight:bold;">
                Add-ons
              </p>
              <select id="addon-selector" class="addon-select">
                <option value="">Select add-on</option>
                <?php
                  $addonsResult = $conn->query(
                    "SELECT addon_id, addon_name, addon_price FROM addons ORDER BY addon_name ASC"
                  );
                  while ($a = $addonsResult->fetch_assoc()):
                ?>
                  <option
                    value="<?= $a['addon_id'] ?>"
                    data-price="<?= number_format((float)$a['addon_price'], 2, '.', '') ?>">
                    <?= htmlspecialchars($a['addon_name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
              <div id="addon-config" style="display:none; margin-top:12px;">
                <div class="product-form" style="grid-template-columns: 1fr 1fr; margin-top:0; padding:0; box-shadow:none; border:none; background:transparent;">
                  <div class="field">
                    <label>Add?</label>
                    <select id="addon-add">
                      <option value="0">No</option>
                      <option value="1">Yes</option>
                    </select>
                  </div>
                  <div class="field">
                    <label>Included?</label>
                    <select id="addon-included" disabled>
                      <option value="0">No</option>
                      <option value="1">Yes (FREE)</option>
                    </select>
                  </div>
                  <div class="field field-full">
                    <label>Cost (₱)</label>
                    <input type="number" id="addon-price" step="0.01" min="0" disabled>
                  </div>
                </div>
              </div>
              <div id="addon-hidden-fields"></div>
            </div>
            <div class="field field-full">
              <label for="prod-desc">Description</label>
              <textarea id="prod-desc" name="description" placeholder="Short description, flavor notes, size, etc."></textarea>
            </div>
            <div class="field field-full">
              <label for="prod-image">Product Image</label>
              <input id="prod-image" type="file" name="image" accept="image/*">
              <small class="field-help" style="color:#b59678;">PNG/JPG/GIF/WebP, up to 5MB. Optional.</small>
            </div>
            <div class="product-form-actions">
              <button type="submit" class="primary-btn">Add Product</button>
            </div>
          </form>
        </section>

        <section id="manage-products" class="dashboard-section" style="display: <?php echo $active==='manage-products'?'block':'none'; ?>;">
          <h1>Manage Products</h1>
          <p style="margin-bottom: 1rem; color:#fff; font-size:0.95rem;">
            Edit product details, update photos, or remove items from the menu.
          </p>
          <div class="admin-table-wrapper">
            <table class="admin-table admin-products-table" border="0" cellpadding="0" cellspacing="0">
              <thead>
                <tr>
                  <th>Product Details</th>
                  <th style="width:120px;">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php
              $products = $conn->query("
                SELECT product_id, product_name, description, price, image_path, category, size_type, size_prices
                FROM products ORDER BY product_id DESC
              ");
              ?>
              <?php if ($products && $products->num_rows > 0): ?>
                <?php while ($p = $products->fetch_assoc()): ?>
                  <tr>
                    <td>
                      <form class="product-row-form" method="POST" action="../php/update_product.php" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                        <input type="hidden" name="size_type" value="<?= $p['size_type'] ?>">
                        <input type="hidden" name="category" value="<?= $p['category'] ?? 'drink' ?>">
                        
                        <div class="image-control-group field-full">
                            <?php if (!empty($p['image_path'])): ?>
                                <div class="product-thumb">
                                    <img src="../<?php echo htmlspecialchars($p['image_path']); ?>" alt="thumb">
                                </div>
                                <label class="field-inline remove-image-label" style="color:#fbead2;">
                                    <input type="checkbox" name="remove_image" value="1"> Remove current image
                                </label>
                            <?php endif; ?>
                        </div>

                        <div class="field field-full">
                          <label>Name</label>
                          <input type="text" name="name" value="<?= htmlspecialchars($p['product_name']) ?>" required>
                        </div>

                        <div class="field field-full">
                          <label>Description</label>
                          <textarea name="description"><?= htmlspecialchars($p['description']) ?></textarea>
                        </div>

                        <div class="price-fields-container field-full">
                          <label>Price</label>
                          <?php
                            $hasSizes = !empty($p['size_prices']);
                            $sizePrices = [];
                            if ($hasSizes) {
                                $decoded = json_decode($p['size_prices'], true);
                                if (is_array($decoded)) $sizePrices = $decoded;
                                else $hasSizes = false;
                            }
                          ?>
                          <?php if ($hasSizes): ?>
                              <div class="price-sizes" style="display:flex; gap:12px; flex-wrap:wrap;">
                                <label style="color:#fbead2; display:flex; gap:4px; align-items:center;">S ₱ <input type="number" step="0.01" min="0" name="price_s" value="<?= $sizePrices['S'] ?? '' ?>" required style="width:100px;"></label>
                                <label style="color:#fbead2; display:flex; gap:4px; align-items:center;">M ₱ <input type="number" step="0.01" min="0" name="price_m" value="<?= $sizePrices['M'] ?? '' ?>" required style="width:100px;"></label>
                                <label style="color:#fbead2; display:flex; gap:4px; align-items:center;">L ₱ <input type="number" step="0.01" min="0" name="price_l" value="<?= $sizePrices['L'] ?? '' ?>" required style="width:100px;"></label>
                              </div>
                              <input type="hidden" name="price" value="<?= $sizePrices['M'] ?? 0 ?>">
                          <?php else: ?>
                              <div class="price-static">
                                ₱ <input type="number" step="0.01" min="0" name="price" value="<?= number_format((float)$p['price'], 2, '.', '') ?>" required style="width:120px;">
                              </div>
                          <?php endif; ?>
                        </div>

                        <div class="field field-full">
                          <label>Add/Change Image</label>
                          <input type="file" name="image" accept="image/*">
                        </div>

                        <div class="product-row-actions">
                          <button type="submit" class="secondary-btn">Save</button>
                        </div>
                      </form>
                      </td>
                    
                    <td style="text-align:center;">
                      <form method="POST" action="../php/delete_product.php" onsubmit="return confirm('Delete this product?');">
                        <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                        <button type="submit" class="danger-btn">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="2" style="padding:12px; text-align:center; color:#ccc;">No products found.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section id="manage-addons" class="dashboard-section" style="display: <?php echo $active==='manage-addons'?'block':'none'; ?>;">
          <h1>Manage Add-ons</h1>
          <p style="margin-bottom: 1rem; color:#fff; font-size:0.95rem;">
            Create and manage add-ons (e.g., Boba, Extra Sugar) that can be assigned to drinks.
          </p>
          
          <div class="product-form" style="max-width: 100%; margin-bottom: 2rem;">
            <h2 style="grid-column: 1 / -1; color: #fbead2; font-size: 1.1rem; text-transform: uppercase; margin-bottom: 10px;">Add New Add-on</h2>
            <form method="POST" action="../php/manage_addons.php" style="display:contents;">
              <input type="hidden" name="action" value="add">
              <div class="field">
                <label>Add-on Name</label>
                <input type="text" name="addon_name" placeholder="e.g. Boba, Extra Shot" required />
              </div>
              <div class="field">
                <label>Price (₱)</label>
                <input type="number" name="addon_price" placeholder="Price" step="0.01" min="0" required />
              </div>
              <div class="product-form-actions">
                <button type="submit" class="primary-btn">Create Add-on</button>
              </div>
            </form>
          </div>

          <div style="margin-top: 2rem;">
            <h3 style="color:#fff;">Available Add-ons</h3>
            <div class="admin-table-wrapper">
              <table class="admin-table" border="0" cellpadding="0" cellspacing="0">
                <thead>
                  <tr>
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
                      <td style="color: #fff;"><?php echo htmlspecialchars($addon['addon_name']); ?></td>
                      <td>₱<?php echo number_format((float)$addon['addon_price'], 2); ?></td>
                      <td>
                        <form method="POST" action="../php/manage_addons.php" style="display:inline;" onsubmit="return confirm('Delete this add-on?');">
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
                    <tr><td colspan="3" style="padding:12px; text-align:center; color:#ccc;">No add-ons yet.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section id="announcement" class="dashboard-section" style="display: <?= $active==='announcement'?'block':'none'; ?>;">
          <h1>Announcements</h1>
          <p style="margin-bottom:1rem; color:#fff;">
            Create announcements using Markdown.  
            Example: <code>**Bold**</code>, <code>*Italic*</code>
          </p>

          <form method="POST" action="../php/add_announcement.php" class="product-form">
            <div class="field field-full">
              <label>Title</label>
              <input type="text" name="title" required>
            </div>

            <div class="field field-full">
              <label>Announcement Content (Markdown)</label>
              <div class="md-toolbar">
                <button type="button" data-md="bold" title="**Bold** Text"><b>B</b></button>
                <button type="button" data-md="italic" title="*Italic* Text"><i>I</i></button>
                <button type="button" data-md="underline" title="__Underline__ Text"><u>U</u></button>
                <button type="button" data-md="strike" title="~~Strikethrough~~ Text"><s>S</s></button>
                <button type="button" data-md="inline-code" title="`Inline Code`">C</button>
                <button type="button" data-md="code-block" title="```Fenced Code Block```">&lt;&gt;</button>
                <button type="button" data-md="quote" title="> Quote Block">Q</button>
                <button type="button" data-md="h1" title="# Bigger Text (H1)">#</button>
                <button type="button" data-md="h2" title="## Big Text (H2)">##</button>
              </div>
              <textarea
                id="announcement-content"
                name="content"
                rows="6"
                placeholder="This description uses Markdown. Select the text and press the desired button to apply the markdown."
                required></textarea>
            </div>

            <div class="product-form-actions">
              <button type="submit" class="primary-btn">Publish</button>
            </div>
          </form>

          <div style="margin-top:2rem;">
            <h3 style="color:#fff;">Published Announcements</h3>

            <?php
              $announcements = $conn->query(
                "SELECT * FROM announcement_blog ORDER BY created_at DESC"
              );
            ?>

            <?php if ($announcements && $announcements->num_rows): ?>
              <?php while ($a = $announcements->fetch_assoc()): ?>
                <div class="published-card">
                  <h3><?= htmlspecialchars($a['title']) ?></h3>
                  <small><?= $a['created_at'] ?></small>
                  <div class="markdown-content" style="margin-top:12px;">
                    <?= renderMarkdown($a['content']) ?>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <p style="margin-top:1rem; color:#ccc;">No announcements yet.</p>
            <?php endif; ?>
          </div>
        </section>

      </main>
    </div>

    <script>
      /* ================= SIZE LOGIC ================= */
      function toggleSizePrices(){
        const sizeType = document.getElementById('prod-size-type').value;
        const basePriceSection = document.getElementById('base-price-section');
        const sizePricesSection = document.getElementById('size-prices-section');
        const basePriceInput = document.getElementById('prod-price');

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
        const inputs = document.querySelectorAll('#size-prices-section input[type="checkbox"]');
        const priceInputs = document.querySelectorAll('#size-prices-section input[type="number"]');
        inputs.forEach((checkbox, index) => {
          checkbox.addEventListener('change', () => {
            priceInputs[index].disabled = !checkbox.checked;
            if (!checkbox.checked) priceInputs[index].value = '';
          });
        });
      }

      /* ================= ADD-ONS VISIBILITY ================= */
      function toggleAddonsSection(){
        const category = document.getElementById('prod-category');
        const addonsSection = document.getElementById('addons-section');
        if (!category || !addonsSection) return;
        addonsSection.style.display = category.value === 'drink' ? 'block' : 'none';
      }

      /* ================= ADD-ON DROPDOWN LOGIC ================= */
      const addonState = {};
      let currentAddonId = null;
      const addonSelector  = document.getElementById('addon-selector');
      const addonPanel     = document.getElementById('addon-config');
      const addSelect      = document.getElementById('addon-add');
      const includedSelect = document.getElementById('addon-included');
      const priceInput     = document.getElementById('addon-price');
      const hiddenFields   = document.getElementById('addon-hidden-fields');

      if(priceInput) {
        priceInput.readOnly = true;
        priceInput.disabled = true;
      }

      if(addonSelector) {
        addonSelector.addEventListener('change', () => {
          const addonId = addonSelector.value;
          if (!addonId) {
            addonPanel.style.display = 'none';
            currentAddonId = null;
            return;
          }
          currentAddonId = addonId;
          addonPanel.style.display = 'block';
          const basePrice = parseFloat(addonSelector.selectedOptions[0].dataset.price).toFixed(2);
          if (!addonState[addonId]) {
            addonState[addonId] = { add: '0', included: '0', basePrice: basePrice };
          }
          renderAddonUI();
        });
      }

      function renderAddonUI(){
        const state = addonState[currentAddonId];
        addSelect.value = state.add;
        includedSelect.value = state.included;
        includedSelect.disabled = (state.add !== '1');
        priceInput.value = (state.add === '1' && state.included === '1') ? '0.00' : state.basePrice;
        syncHiddenInputs();
      }

      if(addSelect) {
        addSelect.addEventListener('change', () => {
          const state = addonState[currentAddonId];
          state.add = addSelect.value;
          if (state.add !== '1') state.included = '0';
          renderAddonUI();
        });
      }
      if(includedSelect) {
        includedSelect.addEventListener('change', () => {
          addonState[currentAddonId].included = includedSelect.value;
          renderAddonUI();
        });
      }

      function syncHiddenInputs(){
        hiddenFields.innerHTML = '';
        Object.entries(addonState).forEach(([id, state]) => {
          if (state.add !== '1') return;
          const effectivePrice = state.included === '1' ? '0.00' : state.basePrice;
          [
            ['add', '1'],
            ['included', state.included],
            ['price', effectivePrice]
          ].forEach(([key, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `addons[${id}][${key}]`;
            input.value = value;
            hiddenFields.appendChild(input);
          });
        });
      }

      document.addEventListener('DOMContentLoaded', function(){
        const cat = document.getElementById('prod-category');
        if(cat) {
          cat.addEventListener('change', toggleAddonsSection);
          toggleAddonsSection();
        }
      });

      /* ================= ANNOUNCEMENT NAVBAR ================= */
      document.addEventListener('DOMContentLoaded', () => {
        const textarea = document.getElementById('announcement-content');
        if (!textarea) return;
        document.querySelectorAll('.md-toolbar button').forEach(btn => {
          btn.addEventListener('click', () => {
            applyMarkdown(btn.dataset.md, textarea);
          });
        });
        textarea.addEventListener('keydown', e => {
          if (e.key === 'Enter' && e.shiftKey) {
            e.preventDefault();
            textarea.setRangeText('\n\n', textarea.selectionStart, textarea.selectionEnd, 'end');
          }
        });
      });

      function applyMarkdown(type, textarea) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selected = textarea.value.substring(start, end) || 'text';
        let insert = selected;
        switch (type) {
          case 'bold': insert = `**${selected}**`; break;
          case 'italic': insert = `*${selected}*`; break;
          case 'underline': insert = `__${selected}__`; break;
          case 'strike': insert = `~~${selected}~~`; break;
          case 'inline-code': insert = `\`${selected}\``; break;
          case 'code-block': insert = `\`\`\`\n${selected}\n\`\`\``; break;
          case 'quote': insert = selected.split('\n').map(line => `> ${line}`).join('\n'); break;
          case 'h1': insert = `# ${selected}`; break;
          case 'h2': insert = `## ${selected}`; break;
        }
        textarea.setRangeText(insert, start, end, 'end');
        textarea.focus();
      }

      /* ================= SIDEBAR NAV ================= */
      (function(){
        // Removed 'users' from list
        const sectionIds = ['dashboard','add-product','manage-products','manage-addons', 'announcement'];
        const byId = id => document.getElementById(id);

        function show(id){
          sectionIds.forEach(s => {
            const el = byId(s);
            if (el) el.style.display = (s === id ? 'block' : 'none');
          });
        }
        function current(){
          return location.hash ? location.hash.slice(1) : 'dashboard';
        }
        document.addEventListener('DOMContentLoaded', function(){
          show(current());
          document.querySelectorAll('.sidebar a[href^="#"]').forEach(a => {
            a.addEventListener('click', () => show(a.getAttribute('href').slice(1)));
          });
        });
        window.addEventListener('hashchange', () => show(current()));
      })();
    </script>
  </body>
</html>
<?php $conn->close(); ?>