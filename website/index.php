<?php
session_start();
require_once 'php/config.php';

$isUser = isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['user','employee'], true);
$isAdmin = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'admin');
if ($isAdmin) {
  header('Location: /website/php/admin.php');
  exit();
}

$products = $conn->query("SELECT product_id, product_name, description, price, created_at FROM products ORDER BY created_at DESC, product_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Taste of Paradise | Homepage</title>
    <link rel="stylesheet" href="/website/static/css/homepage.css"/>
  </head>
  <body>
    <header>  
      <nav class="navbar">
        <div class="logo">
          <a href="/website/php/login_admin.php" title="Admin Login">
            <img src="/website/static/image/logo.png" alt="Taste of Paradise" style="height:42px; width:auto; display:block;" />
          </a>
        </div>
      </nav>
    </header>

    <main>
      <section class="welcome">
        <h1>Welcome Back!</h1>
        <p>This page shows the latest products</p>
      </section>

      <section id="menu" class="menu-preview">
        <h2 style="margin-bottom:16px">All Products</h2>
        <div class="menu-grid">
          <?php if ($products && $products->num_rows > 0): ?>
            <?php while ($p = $products->fetch_assoc()): ?>
              <?php
                // Default image (chocolate) so modal/cards are never blank
                $img = '/website/static/image/chocolate.png';
                if (!empty($p['image_path'])) {
                  // Use uploaded image if available
                  $img = '/website/' . $p['image_path'];
                } else {
                  // Fallback based on product name
                  $nameLc = strtolower(trim($p['product_name'] ?? ''));
                  if ($nameLc !== '') {
                    if (strpos($nameLc, 'matcha') !== false) {
                      $img = '/website/static/image/matcha.png';
                    } elseif (strpos($nameLc, 'okinawa') !== false) {
                      $img = '/website/static/image/okinawa.png';
                    } elseif (strpos($nameLc, 'choco') !== false) {
                      $img = '/website/static/image/chocolate.png';
                    }
                  }
                }
              ?>
              <div class="menu-item" role="button" tabindex="0"
                   data-name="<?php echo htmlspecialchars($p['product_name'], ENT_QUOTES); ?>"
                   data-desc="<?php echo htmlspecialchars($p['description'] ?: '', ENT_QUOTES); ?>"
                   data-price="<?php echo number_format((float)$p['price'], 2, '.', ''); ?>"
                   data-image="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>">
                <img class="menu-item-img" src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['product_name'] ?: 'Product'); ?>" />
                <div class="menu-item-body">
                  <h3><?php echo htmlspecialchars($p['product_name']); ?></h3>
                  <p><?php echo nl2br(htmlspecialchars($p['description'] ?: '')); ?></p>
                </div>
                <div class="menu-item-footer">
                  <span class="price">₱<?php echo number_format((float)$p['price'], 2); ?></span>
                  <button type="button" class="view-btn">View</button>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p>No products yet. Please check back later.</p>
          <?php endif; ?>
        </div>
      </section>

      <!-- Product Modal -->
      <div id="productModal" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#fff; width:min(520px, 92vw); border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
          <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #eee;">
            <h3 id="pmTitle" style="margin:0; font-size:1.25rem;">Product</h3>
            <button id="pmClose" type="button" aria-label="Close" style="background:#1a1a1a; color:#fff; border:none; width:28px; height:28px; border-radius:50%; cursor:pointer;">×</button>
          </div>
          <div style="padding:14px 16px;">
<div id="pmImageWrap" style="display: flex; justify-content: center; align-items: center; margin-bottom:10px;">
              <img id="pmImage" src="" alt="" style="width:200px; height:300px; object-fit:cover; border-radius:8px;" />
            </div>
            <p id="pmDesc" style="white-space:pre-wrap; margin:8px 0 12px;"></p>
            <div style="font-weight:700; font-size:1.1rem;">Price: <span id="pmPrice"></span></div>
          </div>
        </div>
      </div>

    </main>

    <!-- Admin Login Modal -->
    <div id="adminModal" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center;">
      <div style="background:#fff; width:min(480px, 92vw); border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #eee;">
          <h3 style="margin:0; font-size:1.15rem;">Admin Login</h3>
          <button id="adminClose" type="button" aria-label="Close" style="background:#1a1a1a; color:#fff; border:none; width:28px; height:28px; border-radius:50%; cursor:pointer;">×</button>
        </div>
        <div style="padding:16px;">
          <form method="POST" action="/website/php/login_admin.php" style="display:flex; flex-direction:column; gap:10px;">
            <input type="email" name="email" placeholder="Email" required style="padding:10px; border:1px solid #ddd; border-radius:6px;" />
            <input type="password" name="password" placeholder="Password" required style="padding:10px; border:1px solid #ddd; border-radius:6px;" />
            <button type="submit" name="login" style="background:#1a1a1a; color:#fff; padding:10px; border-radius:6px; border:none; cursor:pointer;">Sign in</button>
          </form>
        </div>
      </div>
    </div>

    <footer>
      <p>&copy; <?php echo date('Y'); ?> Taste of Paradise. All rights reserved.</p>
    </footer>
    <script>
      (function(){
        // Product modal existing logic
        const modal = document.getElementById('productModal');
        const title = document.getElementById('pmTitle');
        const desc = document.getElementById('pmDesc');
        const price = document.getElementById('pmPrice');
        const imgWrap = document.getElementById('pmImageWrap');
        const img = document.getElementById('pmImage');
        const closeBtn = document.getElementById('pmClose');

        function openModal(d){
          title.textContent = d.name || 'Product';
          desc.textContent = d.desc || '';
          price.textContent = '₱' + Number(d.price || 0).toFixed(2);
          if (d.image){
            img.src = d.image; img.alt = d.name || 'Product';
            imgWrap.style.display = 'block';
          } else {
            img.src = ''; img.alt = '';
            imgWrap.style.display = 'none';
          }
          modal.style.display = 'flex';
          document.body.style.overflow = 'hidden';
        }
        function closeModal(){
          modal.style.display = 'none';
          document.body.style.overflow = '';
        }
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e)=>{ if(e.target === modal) closeModal(); });

        document.querySelectorAll('.menu-item .view-btn, .menu-item').forEach(el => {
          el.addEventListener('click', (e) => {
            const card = e.currentTarget.closest('.menu-item');
            if (!card) return;
            const data = {
              name: card.getAttribute('data-name'),
              desc: card.getAttribute('data-desc'),
              price: card.getAttribute('data-price'),
              image: card.getAttribute('data-image')
            };
            openModal(data);
          });
        });

        // Admin modal open/close
        const adminModal = document.getElementById('adminModal');
        const adminBtn = document.getElementById('adminLoginBtn');
        const adminClose = document.getElementById('adminClose');
        if (adminBtn) {
          adminBtn.addEventListener('click', function(e){ e.preventDefault(); adminModal.style.display='flex'; document.body.style.overflow='hidden'; });
        }
        if (adminClose) {
          adminClose.addEventListener('click', function(){ adminModal.style.display='none'; document.body.style.overflow=''; });
        }
        adminModal.addEventListener('click', function(e){ if(e.target===adminModal){ adminModal.style.display='none'; document.body.style.overflow=''; }});
      })();
    </script>
  </body>
</html>
<?php $conn->close(); ?>
