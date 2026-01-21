<?php
session_start();
require_once 'config.php'; // Assuming config.php contains the database connection ($conn)

$isUser = isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['user', 'employee', 'admin'], true);

// Fetch all products with category, ordered by category for grouping
$products = $conn->query("SELECT product_id, product_name, description, price, image_path, size_type, size_prices, category, created_at FROM products ORDER BY category DESC, product_name ASC");

// Separate products by category
$categories = [];
if ($products && $products->num_rows > 0) {
    while ($p = $products->fetch_assoc()) {
        $category = strtolower($p['category'] ?? 'uncategorized');
        if (!isset($categories[$category])) {
            $categories[$category] = [];
        }
        $categories[$category][] = $p;
    }
}

/**
 * Resolve product image path with fallback logic
 */
function getProductImage($imagePath, $productName) {
    $default = '../static/image/chocolate.png'; // relative from php/

    if (!empty($imagePath)) {
        return '../' . ltrim($imagePath, '/'); // prepend ../ to reach static/
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



/**
 * Get the price to display (smallest size if s_m_l, otherwise base price)
 */
function getDisplayPrice($price, $sizeType, $sizePricesJson) {
    $displayPrice = (float)$price;
    if ($sizeType === 's_m_l' && !empty($sizePricesJson)) {
        $sizes = json_decode($sizePricesJson, true);
        if (is_array($sizes) && isset($sizes['S'])) {
            $displayPrice = (float)$sizes['S'];
        }
    }
    return $displayPrice;
}

// Function to format category title nicely
function formatCategoryTitle($category) {
    return ucwords(str_replace('_', ' ', $category));
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Taste of Paradise | Full Menu</title>
        <link rel="stylesheet" href="../static/css/menu.css"/>

        <style>
            /* Additional specific style for the full menu page if needed */
            .full-menu-section {
                padding-top: 2rem;
            }
            .category-heading {
                font-size: 2rem;
                color: #4b2e0b;
                text-align: center;
                margin-bottom: 2rem;
                border-bottom: 3px solid #e8c7a6;
                padding-bottom: 0.5rem;
                max-width: 90%;
                margin-left: auto;
                margin-right: auto;
            }
            .menu-grid-full {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 2rem;
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 1rem;
            }
            @media (max-width: 600px) {
                .menu-grid-full {
                    grid-template-columns: 1fr;
                    gap: 1.5rem;
                }
            }
            
            /* Modal-related styles imported from homepage.css */
            #productModal {
                /* Display: none should be the default */
                display: none; 
            }
        </style>
    </head>
    <body>
        <header>  
            <nav class="navbar">
                <div class="logo">
                    <img src="../static/image/logo.png" alt="Taste of Paradise" style="height:42px; width:auto; display:block;" />
                </div>

                <!-- Hamburger -->
                <button class="hamburger" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <!-- Nav links -->
                <ul class="nav-links">

                <!-- Hamburger Dropdown -->
                <div class="hamburger-menu">
                    <a href="../index.php">Home</a>
                    <a href="full_menu.php">Menu</a>
                    <?php if ($isUser): ?>
                    <?php else: ?>
                        <a href="announcements.php">Announcements</a>
                    <?php endif; ?>
                </div>
            </nav>
        </header>

        <main>
            <section class="welcome">
                <h1>Our Full Menu</h1>
                <p>Explore all the delicious items we offer.</p>
            </section>

            <section id="full-menu" class="full-menu-section">
                <?php if (empty($categories)): ?>
                    <p style="text-align:center; font-size:1.2rem; color:#8b5a2b; margin-top:50px;">
                        No products are currently available in the menu.
                    </p>
                <?php else: ?>
                    <?php 
                    // Define desired order: Drinks first, then Food, then others
                    $categoryOrder = ['drink', 'food'];
                    $orderedCategories = [];
                    foreach ($categoryOrder as $cat) {
                        if (isset($categories[$cat])) {
                            $orderedCategories[$cat] = $categories[$cat];
                            unset($categories[$cat]);
                        }
                    }
                    // Add any remaining categories
                    $orderedCategories = array_merge($orderedCategories, $categories);
                    ?>

                    <?php foreach ($orderedCategories as $category => $products): ?>
                        <h2 class="category-heading"><?php echo formatCategoryTitle($category); ?></h2>
                        <div class="menu-grid-full menu-grid">
                            <?php foreach ($products as $p): 
                                $img = getProductImage($p['image_path'] ?? null, $p['product_name'] ?? '');
                                $displayPrice = getDisplayPrice($p['price'], $p['size_type'] ?? 'none', $p['size_prices'] ?? '');
                            ?>
                                <div class="menu-item" role="button" tabindex="0"
                                    data-product-id="<?php echo (int)$p['product_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($p['product_name'], ENT_QUOTES); ?>"
                                    data-desc="<?php echo htmlspecialchars($p['description'] ?: '', ENT_QUOTES); ?>"
                                    data-price="<?php echo number_format((float)$p['price'], 2, '.', ''); ?>"
                                    data-size-type="<?php echo htmlspecialchars($p['size_type'] ?? 'none', ENT_QUOTES); ?>"
                                    data-size-prices="<?php echo htmlspecialchars($p['size_prices'] ?? '', ENT_QUOTES); ?>"
                                    data-image="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>"
                                >
                                    <img class="menu-item-img" src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['product_name'] ?: 'Product'); ?>" />
                                    <div class="menu-item-body">
                                        <h3><?php echo htmlspecialchars($p['product_name']); ?></h3>
                                        <p><?php echo htmlspecialchars(substr($p['description'] ?: '', 0, 80)) . (strlen($p['description'] ?? '') > 80 ? '...' : ''); ?></p>
                                    </div>
                                    <div class="menu-item-footer">
                                        <span class="price">
                                            ₱<?php echo number_format($displayPrice, 2); ?>
                                            <?php if ($p['size_type'] === 's_m_l'): ?>
                                                <small>(Starts)</small>
                                            <?php endif; ?>
                                        </span>
                                        <button type="button" class="view-btn">View Details</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <div id="productModal" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center;">
                <div style="background:#fff; width:min(520px, 92vw); border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.3); max-height:85vh; overflow-y:auto;">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #eee; position:sticky; top:0; background:#fff; z-index:1;">
                        <h3 id="pmTitle" style="margin:0; font-size:1.25rem;">Product</h3>
                        <button id="pmClose" type="button" aria-label="Close" style="background:#4b2e0b; color:#fff; border:none; width:28px; height:28px; border-radius:50%; cursor:pointer;">×</button>
                    </div>
                    <div style="padding:14px 16px;">
                        <div id="pmImageWrap" style="display: flex; justify-content: center; align-items: center; margin-bottom:10px;">
                            <img id="pmImage" src="" alt="" style="width:200px; height:300px; object-fit:cover; border-radius:8px;" />
                        </div>
                        <p id="pmDesc" style="white-space:pre-wrap; margin:8px 0 12px;"></p>
                        <div style="font-weight:700; font-size:1.1rem;">Base Price: <span id="pmPrice"></span></div>
                        <div id="pmSizeOptions" style="display:none; margin-top:12px; padding-top:12px; border-top:1px solid #eee;">
                            <label style="font-weight:700; margin-bottom:8px; display:block;">Size:</label>
                            <select id="pmSizeSelect" style="padding:8px; border:1px solid #ddd; border-radius:4px; width:100%;">
                            </select>
                            <div style="margin-top:8px; font-weight:700;">Selected Size Price: <span id="pmSelectedPrice"></span></div>
                        </div>
                        <div id="pmAddOnsContainer" style="display:none; margin-top:12px; padding-top:12px; border-top:1px solid #eee;">
                            <label style="font-weight:700; margin-bottom:8px; display:block;">Add-ons:</label>
                            <div id="pmAddOns" style="display:flex; flex-direction:column; gap:8px;">
                            </div>
                            <div style="margin-top:8px; font-weight:700;">Add-ons Total: <span id="pmAddOnsPrice">₱0.00</span></div>
                        </div>
                        <div style="margin-top:16px; padding-top:12px; border-top:1px solid #eee; font-weight:700; font-size:1.15rem;">Final Total: <span id="pmTotalPrice"></span></div>
                        
                        <?php if ($isUser): ?>
                        <div style="text-align:center; margin-top:15px;">
                            <button type="button" class="full-menu-btn" id="pmAddToCart" style="width:100%;">Add to Cart</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="adminModal" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center;">
                </div>
        </main>

        <footer style="position:relative; z-index:100;">
            <p>&copy; <?php echo date('Y'); ?> Taste of Paradise. All rights reserved.</p>
        </footer>

        <script>
            (() => {
                const navbar = document.querySelector('.navbar');
                const hamburger = navbar.querySelector('.hamburger');
                const menu = navbar.querySelector('.hamburger-menu');

                hamburger.addEventListener('click', e => {
                    e.stopPropagation();
                    hamburger.classList.toggle('active');
                    menu.classList.toggle('show');
                });

                document.addEventListener('click', e => {
                    if (!navbar.contains(e.target)) {
                        hamburger.classList.remove('active');
                        menu.classList.remove('show');
                    }
                });
            })();
            // ============================================================================
            // PRODUCT MODAL (Simplified copy from homepage for full_menu consistency)
            // Note: This needs to fetch add-ons via AJAX as it's not pre-loaded here.
            // ============================================================================
            (function initProductModal() {
                const modal = document.getElementById('productModal');
                const title = document.getElementById('pmTitle');
                const desc = document.getElementById('pmDesc');
                const basePriceEl = document.getElementById('pmPrice');
                const imgWrap = document.getElementById('pmImageWrap');
                const img = document.getElementById('pmImage');
                const closeBtn = document.getElementById('pmClose');
                const sizeOptions = document.getElementById('pmSizeOptions');
                const sizeSelect = document.getElementById('pmSizeSelect');
                const selectedPriceEl = document.getElementById('pmSelectedPrice');
                const addOnsContainer = document.getElementById('pmAddOnsContainer');
                const addOnsDiv = document.getElementById('pmAddOns');
                const addOnsPriceEl = document.getElementById('pmAddOnsPrice');
                const totalPriceEl = document.getElementById('pmTotalPrice');
                const allItems = document.querySelectorAll('.menu-item');

                let currentBasePrice = 0;
                let currentSizePrice = 0;
                let currentAddOnsPrice = 0;
                let allAddOns = [];
                let currentProductId = null;

                function updateTotalPrice() {
                    const addOnsTotal = allAddOns
                        .filter(addon => {
                            const checkbox = document.getElementById('addon-' + addon.addon_id);
                            return checkbox && checkbox.checked;
                        })
                        .reduce((sum, addon) => sum + Number(addon.addon_price), 0);
                    
                    currentAddOnsPrice = addOnsTotal;
                    addOnsPriceEl.textContent = '₱' + addOnsTotal.toFixed(2);
                    
                    const finalTotal = currentSizePrice + addOnsTotal;
                    totalPriceEl.textContent = '₱' + finalTotal.toFixed(2);
                }

                function renderAddOns(addons) {
                    allAddOns = addons;
                    addOnsDiv.innerHTML = '';
                    currentAddOnsPrice = 0; // Reset
                    
                    if (!Array.isArray(addons) || addons.length === 0) {
                        addOnsContainer.style.display = 'none';
                        addOnsPriceEl.textContent = '₱0.00';
                        updateTotalPrice();
                        return;
                    }
                    
                    addOnsContainer.style.display = 'block';
                    
                    addons.forEach(addon => {
                        const isIncluded = !!addon.is_included;
                        const label = document.createElement('label');
                        label.style.display = 'flex';
                        label.style.alignItems = 'center';
                        label.style.gap = '8px';
                        label.style.cursor = 'pointer';
                        label.style.padding = '6px 8px';
                        label.style.borderRadius = '4px';
                        
                        if (isIncluded) {
                            label.style.background = 'rgba(75, 46, 11, 0.08)';
                            label.style.borderLeft = '3px solid #4b2e0b';
                        }
                        
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.id = 'addon-' + addon.addon_id;
                        checkbox.style.cursor = 'pointer';
                        checkbox.checked = isIncluded;
                        checkbox.disabled = isIncluded;
                        if (!isIncluded) {
                            checkbox.addEventListener('change', updateTotalPrice);
                        }
                        
                        const text = document.createElement('span');
                        if (isIncluded) {
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

                async function fetchAddOns(productId) {
                    try {
                        // NOTE: Update the AJAX path if necessary
                        const response = await fetch(`../php/get_addons.php?product_id=${productId}`); 
                        if (!response.ok) throw new Error('Network response was not ok');
                        const addons = await response.json();
                        renderAddOns(addons);
                    } catch (error) {
                        console.error('Error fetching add-ons:', error);
                        renderAddOns([]); // Render empty list on error
                    }
                }

                function openModal(data) {
                    currentProductId = data.productId;
                    title.textContent = data.name || 'Product';
                    desc.textContent = data.desc || '';
                    basePriceEl.textContent = '₱' + Number(data.price || 0).toFixed(2);
                    currentBasePrice = Number(data.price || 0);

                    if (data.image) {
                        img.src = data.image;
                        img.alt = data.name || 'Product';
                        imgWrap.style.display = 'flex';
                    } else {
                        img.src = '';
                        img.alt = '';
                        imgWrap.style.display = 'none';
                    }

                    // Handle Sizes
                    sizeSelect.innerHTML = '';
                    sizeOptions.style.display = 'none';
                    currentSizePrice = currentBasePrice; // Default to base price

                    if (data.sizeType === 's_m_l' && data.sizePrices) {
                        try {
                            const prices = JSON.parse(data.sizePrices);
                            if (prices && typeof prices === 'object') {
                                sizeOptions.style.display = 'block';
                                
                                const sizes = ['S', 'M', 'L'];
                                
                                sizes.forEach((size) => {
                                    if (prices.hasOwnProperty(size)) {
                                        const option = document.createElement('option');
                                        option.value = prices[size];
                                        option.textContent = `${size} - ₱${Number(prices[size]).toFixed(2)}`;
                                        sizeSelect.appendChild(option);
                                    }
                                });

                                // Set initial selected size price to the smallest available (first in select)
                                currentSizePrice = Number(sizeSelect.value);
                            }
                        } catch (e) {
                            console.error('Error parsing size prices:', e);
                        }
                    }

                    selectedPriceEl.textContent = '₱' + currentSizePrice.toFixed(2);
                    
                    sizeSelect.onchange = function() {
                        currentSizePrice = Number(this.value);
                        selectedPriceEl.textContent = '₱' + currentSizePrice.toFixed(2);
                        updateTotalPrice();
                    };

                    // Fetch and render Add-ons
                    fetchAddOns(currentProductId);

                    modal.style.display = 'flex';
                    modal.setAttribute('aria-hidden', 'false');
                }

                function closeModal() {
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                    sizeSelect.onchange = null; // Clean up listener
                }

                // Attach event listeners to all view buttons and the menu item boxes
                allItems.forEach(item => {
                    const viewBtn = item.querySelector('.view-btn');
                    const data = item.dataset;
                    
                    // Listener for the entire box (role="button")
                    item.addEventListener('click', (e) => {
                        // Prevent opening twice if clicking the button inside
                        if (e.target.tagName !== 'BUTTON') {
                            openModal(data);
                        }
                    });
                    
                    // Listener for the explicit View button
                    if (viewBtn) {
                        viewBtn.addEventListener('click', (e) => {
                            e.stopPropagation(); // Prevent the parent div's click event
                            openModal(data);
                        });
                    }
                });

                closeBtn.addEventListener('click', closeModal);
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        closeModal();
                    }
                });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && modal.style.display === 'flex') {
                        closeModal();
                    }
                });
            })();
        </script>
    </body>
</html>