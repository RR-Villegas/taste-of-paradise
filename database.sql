DROP DATABASE IF EXISTS food_paradise;

CREATE DATABASE food_paradise;

USE food_paradise;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255) NULL,
    category VARCHAR(50) DEFAULT 'drink' COMMENT 'drink, food',
    size_type VARCHAR(50) DEFAULT 'none' COMMENT 'none, s_m_l',
    size_prices JSON NULL COMMENT '{"S":100,"M":120,"L":150} or null if size_type=none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE addons (
    addon_id INT AUTO_INCREMENT PRIMARY KEY,
    addon_name VARCHAR(100) NOT NULL UNIQUE,
    addon_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE product_addons (
    product_id INT NOT NULL,
    addon_id INT NOT NULL,
    is_included BOOLEAN DEFAULT FALSE COMMENT 'true=free/included, false=optional/paid',
    PRIMARY KEY (product_id, addon_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (addon_id) REFERENCES addons(addon_id) ON DELETE CASCADE
);

-- Seed default admin user
INSERT INTO users (user_id, first_name, last_name, username, email, password, role) VALUES
(1, 'admin', 'admin', 'admin', 'fishbaitssgg@gmail.com', '$2y$10$u/aVKFcL8zR3CouZSDWjjewwdzVsOW00b5CSSL.s2CKTNm1QvtzYa', 'admin');

-- Seed test products (3 drinks, 3 food)
INSERT INTO products (product_name, description, price, category, size_type, size_prices) VALUES
('Iced Matcha Latte', 'Creamy matcha with ice', 120.00, 'drink', 's_m_l', '{"S":100,"M":120,"L":150}'),
('Okinawa Milk Tea', 'Rich okinawa brown sugar tea', 110.00, 'drink', 's_m_l', '{"S":90,"M":110,"L":140}'),
('Iced Coffee', 'Fresh brewed cold coffee', 80.00, 'drink', 's_m_l', '{"S":70,"M":80,"L":100}'),
('Chocolate Cake', 'Decadent chocolate dessert', 150.00, 'food', 'none', NULL),
('Croissant', 'Buttery flaky pastry', 80.00, 'food', 'none', NULL),
('Tiramisu', 'Classic Italian dessert', 140.00, 'food', 'none', NULL);

-- Seed test add-ons
INSERT INTO addons (addon_name, addon_price) VALUES
('Boba', 25.00),
('Extra Sugar', 10.00),
('Pearl', 20.00);

-- Link add-ons to products
-- Iced Matcha Latte: Boba (included), Pearl (optional), Extra Sugar (optional)
INSERT INTO product_addons (product_id, addon_id, is_included) VALUES
(1, 1, TRUE),   -- Boba included
(1, 3, FALSE),  -- Pearl optional
(1, 2, FALSE);  -- Extra Sugar optional

-- Okinawa Milk Tea: Boba (included), Pearl (optional)
INSERT INTO product_addons (product_id, addon_id, is_included) VALUES
(2, 1, TRUE),   -- Boba included
(2, 3, FALSE);  -- Pearl optional

-- Iced Coffee: Pearl (optional), Extra Sugar (optional)
INSERT INTO product_addons (product_id, addon_id, is_included) VALUES
(3, 3, FALSE),  -- Pearl optional
(3, 2, FALSE);  -- Extra Sugar optional

