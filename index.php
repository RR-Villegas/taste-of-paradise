<?php
session_start();
require_once 'php/config.php';
require_once 'php/helpers.php';

$isUser = isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['user','employee','admin'], true);

// Fetch the latest announcement for the homepage preview
$latestAnnouncement = $conn->query("SELECT title, content, created_at FROM announcement_blog ORDER BY created_at DESC LIMIT 1");
$announcement = $latestAnnouncement->fetch_assoc();

// Fetch all products with category
$products = $conn->query("SELECT product_id, product_name, description, price, image_path, size_type, size_prices, category, created_at FROM products ORDER BY created_at DESC, product_id DESC");

// Separate products by category
$drinks = [];
$food = [];
if ($products && $products->num_rows > 0) {
  while ($p = $products->fetch_assoc()) {
    if ($p['category'] === 'drink') {
      $drinks[] = $p;
    } else {
      $food[] = $p;
    }
  }
}

/**
 * Resolve product image path with fallback logic
 */
function getProductImage($imagePath, $productName) {
  $default = '../static/image/chocolate.png';
  
  if (!empty($imagePath)) {
    return '/taste-of-paradise-a/' . $imagePath;
  }
  
  $nameLc = strtolower(trim($productName ?? ''));
  if ($nameLc !== '') {
    if (strpos($nameLc, 'matcha') !== false) {
      return '../static/image/matcha.png';
    } elseif (strpos($nameLc, 'okinawa') !== false) {
      return '../static/image/okinawa.png';
    } elseif (strpos($nameLc, 'choco') !== false) {
      return '../static/image/chocolate.png';
    }
  }
  
  return $default;
}

if ($announcement) {
    // Render full markdown once
    $fullRendered = renderMarkdown($announcement['content']);

    // Strip tags for preview truncation (keeps text only)
    $plainText = trim(preg_replace('/\s+/', ' ', strip_tags($fullRendered)));
    $truncateLimit = 120; // adjust as needed
    // Create truncated preview
    $truncated = mb_strlen($plainText) > $truncateLimit
        ? mb_substr($plainText, 0, $truncateLimit) . '…'
        : $plainText;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Taste of Paradise | Homepage</title>
    <link rel="stylesheet" href="static/css/homepagey.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
  </head>
  <body>
    <header>  
      <nav class="navbar">
        <div class="logo">
            <img src="static/image/logo.png" alt="Taste of Paradise"
                style="height:42px; width:auto; display:block;" />
        </div>

        <!-- Hamburger -->
        <div id="index-burger">
          <button class="hamburger" aria-label="Menu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
          </button>

          <ul class="hamburger-menu">
            <li><a href="index.php">Home</a></li>
            <li><a href="php/full_menu.php">Menu</a></li>
            <li><a href="php/announcements.php" class="active">Announcements</a></li>
          </ul>
        </div>
      </nav>
    </header>

    <main>
      <section class="welcome">
        <h1>Welcome Back!</h1>
        <p>This page shows the latest products</p>
      </section>

      <section id="announcements" class="menu-preview announcement-preview">
        <h2>Latest Announcement</h2>
        <?php if ($announcement): ?>
          <div class="latest-announcement-card">
            <h3><?= htmlspecialchars($announcement['title']) ?></h3>

            <p class="announcement-date">
              <?= date('F j, Y', strtotime($announcement['created_at'])) ?>
            </p>

            <div class="announcement-content">
              <p class="announcement-preview-text">
                <?= htmlspecialchars($truncated) ?>
              </p>

              <?php if (mb_strlen(strip_tags($fullRendered)) > $truncateLimit): ?>
                <div class="announcement-btn-wrap">
                  <button
                    type="button"
                    class="view-btn announcement-view-btn"
                    data-title="<?= htmlspecialchars($announcement['title'], ENT_QUOTES) ?>"
                    data-content="<?= htmlspecialchars($announcement['content'], ENT_QUOTES) ?>"
                  >
                    View
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <p>No announcements available at this time.</p>
        <?php endif; ?>
        
        <div style="margin-top: 1.5rem;">
          <a href="php/announcements.php" class="view-all-btn">View All Announcements</a>
        </div>
      </section>
      <section id="menu" class="menu-preview">
        <h2 style="margin-bottom:1.5rem">All Products</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; align-items:flex-start; max-width:700px; margin:0 auto;">
          
          <div>
            <h3 style="font-size:1.3rem; color:#4b2e0b; margin-bottom:1rem; text-align:center;">Drinks</h3>
            <div class="carousel-container" data-category="drinks" style="width:280px; margin:0 auto;">
              <div class="carousel-track" style="position:relative; width:100%; height:450px; cursor:grab;">
                <?php foreach ($drinks as $index => $p): 
                  $img = getProductImage($p['image_path'] ?? null, $p['product_name'] ?? '');
                  // Calculate display price (smallest size for S/M/L, base for none)
                  $displayPrice = (float)$p['price'];
                  if ($p['size_type'] === 's_m_l' && !empty($p['size_prices'])) {
                    $sizes = json_decode($p['size_prices'], true);
                    if (is_array($sizes) && isset($sizes['S'])) {
                      $displayPrice = (float)$sizes['S'];
                    }
                  }
                ?>
                  <div class="carousel-item menu-item" role="button" tabindex="0" 
                      style="position:absolute; inset:0; opacity:<?php echo $index === 0 ? '1' : '0'; ?>; transition:opacity 0.4s ease;"
                      data-product-id="<?php echo (int)$p['product_id']; ?>"
                      data-name="<?php echo htmlspecialchars($p['product_name'], ENT_QUOTES); ?>"
                      data-desc="<?php echo htmlspecialchars($p['description'] ?: '', ENT_QUOTES); ?>"
                      data-price="<?php echo number_format($displayPrice, 2, '.', ''); ?>"
                      data-size-type="<?php echo htmlspecialchars($p['size_type'] ?? 'none', ENT_QUOTES); ?>"
                      data-size-prices="<?php echo htmlspecialchars($p['size_prices'] ?? '', ENT_QUOTES); ?>"
                      data-image="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>">

                    <div class="menu-item-inner">
                      <img class="menu-item-img" src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['product_name'] ?: 'Product'); ?>" />
                      <div class="menu-item-body">
                        <h3><?php echo htmlspecialchars($p['product_name']); ?></h3>
                        <p><?php echo htmlspecialchars($p['description'] ?: ''); ?></p>
                      </div>
                      <div class="menu-item-footer">
                        <span class="price">₱<?php echo number_format($displayPrice, 2); ?></span>
                        <button type="button" class="view-btn">View</button>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
                <?php if (empty($drinks)): ?>
                  <p style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#999;">No drinks available.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div>
            <h3 style="font-size:1.3rem; color:#4b2e0b; margin-bottom:1rem; text-align:center;">Food</h3>
            <div class="carousel-container" data-category="food" style="width:280px; margin:0 auto;">
              <div class="carousel-track" style="position:relative; width:100%; height:450px; cursor:grab;">
                <?php foreach ($food as $index => $p): 
                  $img = getProductImage($p['image_path'] ?? null, $p['product_name'] ?? '');
                  // Calculate display price (smallest size for S/M/L, base for none)
                  $displayPrice = (float)$p['price'];
                  if ($p['size_type'] === 's_m_l' && !empty($p['size_prices'])) {
                    $sizes = json_decode($p['size_prices'], true);
                    if (is_array($sizes) && isset($sizes['S'])) {
                      $displayPrice = (float)$sizes['S'];
                    }
                  }
                ?>
                  <div class="carousel-item menu-item" role="button" tabindex="0" 
                    style="position:absolute; inset:0; opacity:<?php echo $index === 0 ? '1' : '0'; ?>; transition:opacity 0.4s ease;"
                    data-product-id="<?php echo (int)$p['product_id']; ?>"
                    data-name="<?php echo htmlspecialchars($p['product_name'], ENT_QUOTES); ?>"
                    data-desc="<?php echo htmlspecialchars($p['description'] ?: '', ENT_QUOTES); ?>"
                    data-price="<?php echo number_format($displayPrice, 2, '.', ''); ?>"
                    data-size-type="<?php echo htmlspecialchars($p['size_type'] ?? 'none', ENT_QUOTES); ?>"
                    data-size-prices="<?php echo htmlspecialchars($p['size_prices'] ?? '', ENT_QUOTES); ?>"
                    data-image="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>">

                  <div class="menu-item-inner">
                    <img class="menu-item-img" src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['product_name'] ?: 'Product'); ?>" />
                    <div class="menu-item-body">
                      <h3><?php echo htmlspecialchars($p['product_name']); ?></h3>
                      <p><?php echo htmlspecialchars($p['description'] ?: ''); ?></p>
                    </div>
                    <div class="menu-item-footer">
                      <span class="price">₱<?php echo number_format($displayPrice, 2); ?></span>
                      <button type="button" class="view-btn">View</button>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($food)): ?>
                  <p style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#999;">No food available.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </div>
      </section>

      <section id="location" class="location-section menu-preview" style="margin-top:2rem;">
        <h2 style="margin-bottom:1.5rem;">Our Location</h2>
        <div style="display:flex; justify-content:center; margin-bottom:1rem; gap:0.5rem;">
          <button id="mode-foot" class="transport-mode-btn" data-mode="foot" style="padding:8px 16px; background:#4b2e0b; color:#fff8e1; border:none; border-radius:4px; cursor:pointer; font-weight:500;">🚶 Foot</button>
          <button id="mode-bike" class="transport-mode-btn" data-mode="bike" style="padding:8px 16px; background:#8b5a2b; color:#fff8e1; border:none; border-radius:4px; cursor:pointer; font-weight:500;">🏍️ Motorcycle</button>
          <button id="mode-car" class="transport-mode-btn active" data-mode="car" style="padding:8px 16px; background:#4b2e0b; color:#fff8e1; border:none; border-radius:4px; cursor:pointer; font-weight:500;">🚗 Car</button>
        </div>
        <div style="display:flex; justify-content:center; margin-bottom:1.5rem;">
          <div id="map" style="width:70%; height:350px; border-radius:8px; overflow:hidden;"></div>
        </div>
        <div id="map-info" style="text-align:center; padding:1rem; background:rgba(75,46,11,0.05); border-radius:8px;">
          <p style="color:#5a4631; margin:0.5rem 0;">
            <strong>Taste of Paradise</strong><br>
            Lumban, Laguna, Philippines
          </p>
          <p id="distance-info" style="color:#4b2e0b; font-weight:700; margin:0.5rem 0; font-size:1.1rem;">
            Loading distance information...
          </p>
        </div>
      </section>

      <div id="productModal" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#fff; width:min(520px, 92vw); border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.3); max-height:85vh; overflow-y:auto;">
          <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #eee; position:sticky; top:0; background:#fff; z-index:1;">
            <h3 id="pmTitle" style="margin:0; font-size:1.25rem;">Product</h3>
            <button id="pmClose" type="button" aria-label="Close" style="background:#1a1a1a; color:#fff; border:none; width:28px; height:28px; border-radius:50%; cursor:pointer;">×</button>
          </div>
          <div style="padding:14px 16px;">
            <div id="pmImageWrap" style="display: flex; justify-content: center; align-items: center; margin-bottom:10px;">
              <img id="pmImage" src="" alt="" style="width:200px; height:300px; object-fit:cover; border-radius:8px;" />
            </div>
            <p id="pmDesc" style="white-space:pre-wrap; margin:8px 0 12px;"></p>
            <div style="font-weight:700; font-size:1.1rem;">Price: <span id="pmPrice"></span></div>
            <div id="pmSizeOptions" style="display:none; margin-top:12px; padding-top:12px; border-top:1px solid #eee;">
              <label style="font-weight:700; margin-bottom:8px; display:block;">Size:</label>
              <select id="pmSizeSelect" style="padding:8px; border:1px solid #ddd; border-radius:4px; width:100%;">
              </select>
              <div style="margin-top:8px; font-weight:700;">Selected Price: <span id="pmSelectedPrice"></span></div>
            </div>
            <div id="pmAddOnsContainer" style="display:none; margin-top:12px; padding-top:12px; border-top:1px solid #eee;">
              <label style="font-weight:700; margin-bottom:8px; display:block;">Add-ons:</label>
              <div id="pmAddOns" style="display:flex; flex-direction:column; gap:8px;">
              </div>
              <div style="margin-top:8px; font-weight:700;">Add-ons Total: <span id="pmAddOnsPrice">₱0.00</span></div>
            </div>
            <div style="margin-top:16px; padding-top:12px; border-top:1px solid #eee; font-weight:700; font-size:1.15rem;">Total: <span id="pmTotalPrice"></span></div>
          </div>
        </div>
      </div>
      <div id="announcementModal" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#fff; width:min(520px, 92vw); border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.3); max-height:85vh; overflow-y:auto;">
          <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #eee; position:sticky; top:0; background:#fff; z-index:1;">
            <h3 id="amTitle" style="margin:0; font-size:1.25rem;">Announcement</h3>
            <button id="amClose" type="button" aria-label="Close" style="background:#1a1a1a; color:#fff; border:none; width:28px; height:28px; border-radius:50%; cursor:pointer;">×</button>
          </div>
          <div style="padding:14px 16px;">
            <div id="amContent" style="white-space:pre-wrap;"></div>
          </div>
        </div>
      </div>
    </main>

    <div id="adminModal" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center;">
      <div style="background:#fff; width:min(480px, 92vw); border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #eee;">
          <h3 style="margin:0; font-size:1.15rem;">Admin Login</h3>
          <button id="adminClose" type="button" aria-label="Close" style="background:#1a1a1a; color:#fff; border:none; width:28px; height:28px; border-radius:50%; cursor:pointer;">×</button>
        </div>
        <div style="padding:16px;">
          <form method="POST" action="/taste-of-paradise-a/php/login_admin.php" style="display:flex; flex-direction:column; gap:10px;">
            <input type="email" name="email" placeholder="Email" required style="padding:10px; border:1px solid #ddd; border-radius:6px;" />
            <input type="password" name="password" placeholder="Password" required style="padding:10px; border:1px solid #ddd; border-radius:6px;" />
            <button type="submit" name="login" style="background:#1a1a1a; color:#fff; padding:10px; border-radius:6px; border:none; cursor:pointer;">Sign in</button>
          </form>
        </div>
      </div>
    </div>

    <footer style="position:relative; z-index:100;">
      <p>&copy; <?php echo date('Y'); ?> Taste of Paradise. All rights reserved.</p>
    </footer>
    <script>
      (function initAnnouncementModal() {
        const modal = document.getElementById('announcementModal');
        const titleEl = document.getElementById('amTitle');
        const contentEl = document.getElementById('amContent');
        const closeBtn = document.getElementById('amClose');

        document.querySelectorAll('.announcement-view-btn').forEach(btn => {
          btn.addEventListener('click', () => {
            titleEl.textContent = btn.dataset.title;
            contentEl.innerHTML = btn.dataset.content;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
          });
        });

        closeBtn.addEventListener('click', close);
        modal.addEventListener('click', e => {
          if (e.target === modal) close();
        });

        function close() {
          modal.style.display = 'none';
          document.body.style.overflow = '';
        }
      })();

      (function initIndexBurger() {
        const burgerContainer = document.getElementById('index-burger');
        const hamburger = burgerContainer.querySelector('.hamburger');
        const menu = burgerContainer.querySelector('.hamburger-menu');

        hamburger.addEventListener('click', (e) => {
          e.stopPropagation();
          hamburger.classList.toggle('active');
          menu.classList.toggle('show');
          hamburger.setAttribute('aria-expanded', hamburger.classList.contains('active'));
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
          if (!burgerContainer.contains(e.target)) {
            hamburger.classList.remove('active');
            menu.classList.remove('show');
            hamburger.setAttribute('aria-expanded', 'false');
          }
        });
      })();
      // ============================================================================
      // CAROUSEL DRAG/SWIPE NAVIGATION WITH AUTO-SCROLL (PATCHED)
      // ============================================================================
      (function initCarousel() {
        document.querySelectorAll('.carousel-container').forEach(carousel => {
          const track = carousel.querySelector('.carousel-track');
          const items = Array.from(carousel.querySelectorAll('.carousel-item'));
          
          if (items.length === 0) return;
          
          let currentIndex = 0;
          let startX = 0;
          let isDragging = false;
          let autoScrollTimer = null;

          function updateCarousel() {
            items.forEach((item, idx) => {
              item.style.opacity = idx === currentIndex ? '1' : '0';
              item.style.pointerEvents = idx === currentIndex ? 'auto' : 'none';
            });
          }

          function goToNext() {
            currentIndex = (currentIndex + 1) % items.length;
            updateCarousel();
            resetAutoScroll();
          }

          function goToPrev() {
            currentIndex = (currentIndex - 1 + items.length) % items.length;
            updateCarousel();
            resetAutoScroll();
          }

          function startAutoScroll() {
            autoScrollTimer = setInterval(goToNext, 5000);
          }

          function resetAutoScroll() {
            clearInterval(autoScrollTimer);
            startAutoScroll();
          }

          // Mouse events
          track.addEventListener('mousedown', (e) => {
            // Ignore if starting on a button inside a carousel-item
            if (e.target.closest('button')) return;

            isDragging = true;
            startX = e.clientX;
            track.style.cursor = 'grabbing';
          });

          document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            const diff = e.clientX - startX;
            if (Math.abs(diff) > 50) {
              isDragging = false;
              if (diff > 0) goToPrev();
              else goToNext();
            }
          });

          document.addEventListener('mouseup', () => {
            isDragging = false;
            track.style.cursor = 'grab';
          });

          // Touch events (mobile)
          track.addEventListener('touchstart', (e) => {
            // Ignore if starting on a button
            if (e.target.closest('button')) return;

            isDragging = true;
            startX = e.touches[0].clientX;
          });

          track.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            const diff = e.touches[0].clientX - startX;
            if (Math.abs(diff) > 50) {
              isDragging = false;
              if (diff > 0) goToPrev();
              else goToNext();
            }
          });

          track.addEventListener('touchend', () => {
            isDragging = false;
          });

          updateCarousel();
          startAutoScroll();
        });
      })();

      // ============================================================================
      // MAP INITIALIZATION WITH GEOLOCATION AND ROUTING
      // ============================================================================
      (function initMap() {
        const shopLat = 14.298330451824725;
        const shopLng = 121.46218760450121;
        const distanceInfoEl = document.getElementById('distance-info');
        
        let currentMode = 'car';
        let routingControl = null;
        let userLat = null;
        let userLng = null;
        
        const speedProfiles = {
          car: 30,      // km/h
          foot: 2.5,    // km/h
          bike: 45      // km/h
        };
        
        const map = L.map('map').setView([shopLat, shopLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Shop marker
        const shopMarker = L.marker([shopLat, shopLng]).addTo(map)
          .bindPopup('<strong>Taste of Paradise</strong><br>Lumban, Laguna, Philippines')
          .openPopup();
        
        let userMarker = null;
        
        function getRouter(profile) {
          if (profile === 'foot') return L.Routing.osrmv1({profile: 'foot'});
          if (profile === 'bike') return L.Routing.osrmv1({profile: 'bike'});
          return L.Routing.osrmv1({profile: 'car'});
        }
        
        function updateRoute() {
          if (!userLat || !userLng) return;
          
          if (routingControl) {
            map.removeControl(routingControl);
          }
          
          routingControl = L.Routing.control({
            waypoints: [
              L.latLng(userLat, userLng),
              L.latLng(shopLat, shopLng)
            ],
            routeWhileDragging: false,
            show: false,
            addWaypoints: false,
            lineOptions: {
              styles: [{color: '#4b2e0b', opacity: 0.7, weight: 3}]
            },
            createMarker: function() { return null; },
            router: getRouter(currentMode)
          }).on('routesfound', function(e) {
            const routes = e.routes;
            const distance = routes[0].summary.totalDistance / 1000; // km
            const speed = speedProfiles[currentMode];
            const speedMph = (speed * 0.621371).toFixed(1);
            const duration = Math.round((distance / speed) * 60); // minutes
            
            let modeText = 'Car';
            if (currentMode === 'foot') modeText = 'Walking';
            if (currentMode === 'bike') modeText = 'Motorcycle';
            
            distanceInfoEl.innerHTML = `
              <strong>${modeText}: ${duration} minutes</strong><br>
              <small>${speed} km/h (${speedMph} mph)</small>
            `;
          }).addTo(map);
        }
        
        // Mode button listeners
        document.querySelectorAll('.transport-mode-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            document.querySelectorAll('.transport-mode-btn').forEach(b => {
              b.style.background = '#8b5a2b';
            });
            this.style.background = '#4b2e0b';
            currentMode = this.getAttribute('data-mode');
            updateRoute();
          });
        });
        
        // Request user location
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(
            function(position) {
              userLat = position.coords.latitude;
              userLng = position.coords.longitude;
              
              // Add user marker
              userMarker = L.marker([userLat, userLng], {
                icon: L.icon({
                  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                  iconSize: [25, 41],
                  iconAnchor: [12, 41],
                  popupAnchor: [1, -34],
                  shadowSize: [41, 41]
                })
              }).addTo(map);
              
              userMarker.bindPopup('<strong>Your Location</strong>').openPopup();
              
              // Update route with initial mode
              updateRoute();
              
              // Fit both markers in view
              const group = new L.featureGroup([shopMarker, userMarker]);
              map.fitBounds(group.getBounds().pad(0.1));
            },
            function(error) {
              distanceInfoEl.innerHTML = '<small style="color:#999;">Enable location services to see distance information</small>';
              console.log('Geolocation error: ' + error.message);
            }
          );
        } else {
          distanceInfoEl.innerHTML = '<small style="color:#999;">Geolocation not supported</small>';
        }
        
        // Ensure map controls stay below other elements
        const controlContainer = document.querySelector('.leaflet-control-container');
        if (controlContainer) {
          controlContainer.style.position = 'relative';
          controlContainer.style.zIndex = '5';
        }
      })();

      // ============================================================================
      // PRODUCT MODAL
      // ============================================================================
      (function initProductModal() {
        const modal = document.getElementById('productModal');
        const title = document.getElementById('pmTitle');
        const desc = document.getElementById('pmDesc');
        const price = document.getElementById('pmPrice');
        const imgWrap = document.getElementById('pmImageWrap');
        const img = document.getElementById('pmImage');
        const closeBtn = document.getElementById('pmClose');
        const sizeOptions = document.getElementById('pmSizeOptions');
        const sizeSelect = document.getElementById('pmSizeSelect');
        const selectedPrice = document.getElementById('pmSelectedPrice');
        const addOnsContainer = document.getElementById('pmAddOnsContainer');
        const addOnsDiv = document.getElementById('pmAddOns');
        const addOnsPrice = document.getElementById('pmAddOnsPrice');
        const totalPrice = document.getElementById('pmTotalPrice');

        let currentSizePrice = 0;
        let currentAddOnsPrice = 0;
        let allAddOns = [];

        function updateTotalPrice() {
          const addOnsTotal = allAddOns
            .filter(addon => {
              const checkbox = document.getElementById('addon-' + addon.addon_id);
              return checkbox && checkbox.checked;
            })
            .reduce((sum, addon) => sum + Number(addon.addon_price), 0);
          
          currentAddOnsPrice = addOnsTotal;
          addOnsPrice.textContent = '₱' + addOnsTotal.toFixed(2);
          
          const finalTotal = currentSizePrice + addOnsTotal;
          totalPrice.textContent = '₱' + finalTotal.toFixed(2);
        }

        function renderAddOns(addons) {
          allAddOns = addons;
          addOnsDiv.innerHTML = '';
          
          if (!Array.isArray(addons) || addons.length === 0) {
            addOnsContainer.style.display = 'none';
            currentAddOnsPrice = 0;
            addOnsPrice.textContent = '₱0.00';
            return;
          }
          
          addOnsContainer.style.display = 'block';
          
          addons.forEach(addon => {
            const label = document.createElement('label');
            label.style.display = 'flex';
            label.style.alignItems = 'center';
            label.style.gap = '8px';
            label.style.cursor = 'pointer';
            label.style.padding = '6px 8px';
            label.style.borderRadius = '4px';
            
            if (addon.is_included) {
              label.style.background = 'rgba(75, 46, 11, 0.08)';
              label.style.borderLeft = '3px solid #4b2e0b';
            }
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = 'addon-' + addon.addon_id;
            checkbox.style.cursor = 'pointer';
            checkbox.checked = addon.is_included ? true : false;
            checkbox.disabled = addon.is_included ? true : false;
            checkbox.addEventListener('change', updateTotalPrice);
            
            const text = document.createElement('span');
            if (addon.is_included) {
              text.textContent = addon.addon_name + ' (Included FREE)';
              text.style.fontWeight = '600';
              text.style.color = '#4b2e0b';
            } else {
              text.textContent = addon.addon_name + ' (+₱' + Number(addon.addon_price).toFixed(2) + ')';
            }
            
            label.appendChild(checkbox);
            label.appendChild(text);
            addOnsDiv.appendChild(label);
          });
          
          updateTotalPrice();
        }

        function openModal(data) {
          title.textContent = data.name || 'Product';
          desc.textContent = data.desc || '';
          price.textContent = '₱' + Number(data.price || 0).toFixed(2);
          currentSizePrice = Number(data.price || 0);
          
          if (data.image) {
            img.src = data.image;
            img.alt = data.name || 'Product';
            imgWrap.style.display = 'block';
          } else {
            img.src = '';
            img.alt = '';
            imgWrap.style.display = 'none';
          }
          
          const sizeType = data.sizeType || 'none';
          if (sizeType === 's_m_l' && data.sizePrices) {
            try {
              const sizes = JSON.parse(data.sizePrices);
              sizeSelect.innerHTML = '';
              const sizeOrder = ['S', 'M', 'L'];
              sizeOrder.forEach(size => {
                if (sizes[size] !== undefined) {
                  const option = document.createElement('option');
                  option.value = size;
                  option.textContent = size + ' - ₱' + Number(sizes[size]).toFixed(2);
                  sizeSelect.appendChild(option);
                }
              });
              sizeSelect.onchange = function() {
                currentSizePrice = Number(sizes[this.value]);
                selectedPrice.textContent = '₱' + currentSizePrice.toFixed(2);
                updateTotalPrice();
              };
              const firstAvailableSize = sizeOrder.find(size => sizes[size] !== undefined);
              if (firstAvailableSize) {
                sizeSelect.value = firstAvailableSize;
                currentSizePrice = Number(sizes[firstAvailableSize]);
                selectedPrice.textContent = '₱' + currentSizePrice.toFixed(2);
              }
              sizeOptions.style.display = 'block';
            } catch (e) {
              sizeOptions.style.display = 'none';
            }
          } else {
            sizeOptions.style.display = 'none';
          }
          
          const productId = data.productId;
          if (productId) {
            fetch('/taste-of-paradise-a/php/get_product_addons.php?product_id=' + productId)
              .then(res => res.json())
              .then(addons => renderAddOns(addons))
              .catch(err => {
                console.error('Error fetching add-ons:', err);
                renderAddOns([]);
              });
          } else {
            renderAddOns([]);
          }
          
          updateTotalPrice();
          modal.style.display = 'flex';
          document.body.style.overflow = 'hidden';
        }

        function closeModal() {
          modal.style.display = 'none';
          document.body.style.overflow = '';
        }

        // Event listeners
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
          if (e.target === modal) closeModal();
        });

        // ===========================
        // PATCHED: Only target carousel items
        // ===========================
        document.querySelectorAll('.carousel-item').forEach(card => {
          const viewBtn = card.querySelector('.view-btn');
          if (viewBtn) {
            viewBtn.addEventListener('click', (e) => {
              e.stopPropagation();
              openModal({
                productId: card.getAttribute('data-product-id'),
                name: card.getAttribute('data-name'),
                desc: card.getAttribute('data-desc'),
                price: card.getAttribute('data-price'),
                sizeType: card.getAttribute('data-size-type'),
                sizePrices: card.getAttribute('data-size-prices'),
                image: card.getAttribute('data-image')
              });
            });
          }
          // Also allow clicking the card itself
          card.addEventListener('click', () => {
            openModal({
              productId: card.getAttribute('data-product-id'),
              name: card.getAttribute('data-name'),
              desc: card.getAttribute('data-desc'),
              price: card.getAttribute('data-price'),
              sizeType: card.getAttribute('data-size-type'),
              sizePrices: card.getAttribute('data-size-prices'),
              image: card.getAttribute('data-image')
            });
          });
        });
      })();

      // ============================================================================
      // ADMIN LOGIN MODAL
      // ============================================================================
      (function initAdminModal() {
        const modal = document.getElementById('adminModal');
        const closeBtn = document.getElementById('adminClose');
        const openBtn = document.getElementById('adminLoginBtn');

        function openModal() {
          modal.style.display = 'flex';
          document.body.style.overflow = 'hidden';
        }

        function closeModal() {
          modal.style.display = 'none';
          document.body.style.overflow = '';
        }

        // Event listeners
        if (openBtn) {
          openBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal();
          });
        }
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
          if (e.target === modal) closeModal();
        });
      })();
    </script>
  </body>
</html>
<?php $conn->close(); ?>