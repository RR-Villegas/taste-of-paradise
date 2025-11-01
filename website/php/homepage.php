<?php
session_start();
// Allow only logged-in non-admins (user/employee)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['user', 'employee'])) {
  header('Location: ../index.php');
  exit();
}
require_once '../php/config.php';

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
        <div class="logo">Taste of Paradise</div>
        <ul class="nav-links">
          <li><a href="#menu">Menu</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#contact">Contact</a></li>
          <li><a href="/website/php/logout.php">Log out</a></li>
        </ul>
      </nav>
    </header>

    <main>
      <section class="welcome">
        <h1>Welcome Back!</h1>
        <p>This page shows the latest products added by Admin.</p>
      </section>

      <section id="menu" class="menu-preview">
        <h2 style="margin-bottom:16px">All Products</h2>
        <div class="menu-grid">
          <?php if ($products && $products->num_rows > 0): ?>
            <?php while ($p = $products->fetch_assoc()): ?>
              <?php $img = !empty($p['image_path']) ? '/website/' . $p['image_path'] : ''; ?>
              <div class="menu-item" role="button" tabindex="0"
                   data-name="<?php echo htmlspecialchars($p['product_name'], ENT_QUOTES); ?>"
                   data-desc="<?php echo htmlspecialchars($p['description'] ?: '', ENT_QUOTES); ?>"
                   data-price="<?php echo number_format((float)$p['price'], 2, '.', ''); ?>"
                   data-image="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>">
                <?php if (!empty($p['image_path'])): ?>
                  <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['product_name']); ?>" style="width:100%;height:auto;object-fit:cover;border-radius:8px;" />
                <?php endif; ?>
                <div class="menu-item-body">
                  <h3><?php echo htmlspecialchars($p['product_name']); ?></h3>
                  <p><?php echo nl2br(htmlspecialchars($p['description'] ?: '')); ?></p>
                </div>
                <div class="menu-item-footer" style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                  <span class="price">₱<?php echo number_format((float)$p['price'], 2); ?></span>
                  <button type="button" class="view-btn" style="background:#1a1a1a;color:#fff;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;">View</button>
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
            <div id="pmImageWrap" style="display:none; margin-bottom:10px;">
              <img id="pmImage" src="" alt="" style="width:100%; height:auto; object-fit:cover; border-radius:8px;" />
            </div>
            <p id="pmDesc" style="white-space:pre-wrap; margin:8px 0 12px;"></p>
            <div style="font-weight:700; font-size:1.1rem;">Price: <span id="pmPrice"></span></div>
          </div>
        </div>
      </div>

      <section id="about" class="about">
        <h2>About Taste of Paradise</h2>
        <p>
          Taste of Paradise is dedicated to serving high-quality milk tea made
          from the finest ingredients. This website helps our team stay
          connected and informed about our products and updates.
        </p>
      </section>

      <section id="contact" class="contact">
        <h2>Contact</h2>
        <p>Reach out to us for feedback and suggestions.</p>
      </section>
    </main>

    <footer>
      <p>&copy; <?php echo date('Y'); ?> Taste of Paradise. All rights reserved.</p>
    </footer>
    <script>
      (function(){
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
            // Only trigger on button or card root (avoid double open if nested)
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
      })();
    </script>
  </body>
</html>
<?php $conn->close(); ?>
