CREATE DATABASE IF NOT EXISTS `food_paradise` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `food_paradise`;

--
-- Table structure for table `addons`
--
DROP TABLE IF EXISTS `addons`;

CREATE TABLE `addons` (
  `addon_id` int(11) NOT NULL AUTO_INCREMENT,
  `addon_name` varchar(100) NOT NULL,
  `addon_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`addon_id`),
  UNIQUE KEY `addon_name` (`addon_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Data for the table `addons`
--
INSERT INTO `addons` (`addon_id`, `addon_name`, `addon_price`, `created_at`) VALUES
(1, 'Boba', '25.00', '2025-12-14 12:24:33'),
(2, 'Extra Sugar', '10.00', '2025-12-14 12:24:33'),
(3, 'Pearl', '20.00', '2025-12-14 12:24:33');

--
-- Table structure for table `announcement_blog`
--
DROP TABLE IF EXISTS `announcement_blog`;

CREATE TABLE `announcement_blog` (
  `announcement_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`announcement_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Data for the table `announcement_blog`
--
INSERT INTO `announcement_blog` (`announcement_id`, `title`, `content`, `created_at`, `updated_at`) VALUES
(1, 'This is a test announcement', '**Bold**\r\n*Italic*\r\n__Underline__\r\n~~Strikethrough~~\r\n```\r\nCode Block\r\n```\r\n``Fenced Code Block``\r\n# Bigger Text\r\n## Big Text', '2025-12-14 13:14:05', '2025-12-14 13:14:05'),
(2, 'Test 2', '***text***', '2025-12-14 13:14:38', '2025-12-14 13:14:38'),
(3, 'test 3', '~~__***text***__~~', '2025-12-14 13:15:10', '2025-12-14 13:15:10'),
(4, 'code block', '``**Handle Code Block**``', '2025-12-14 13:15:50', '2025-12-14 13:15:50'),
(5, 'test', '```\r\n**text**\r\n```', '2025-12-14 13:16:05', '2025-12-14 13:16:05'),
(6, 'test5', '```\r\n**test**\r\n```', '2025-12-14 13:26:45', '2025-12-14 13:26:45'),
(7, 'quote', '> help where to do text\r\n\r\ntest', '2025-12-14 13:27:26', '2025-12-14 13:27:26'),
(8, 'Ppp', '`code blocks should ignore the other markdown`', '2025-12-14 13:31:57', '2025-12-14 13:31:57'),
(9, 'testtt', '`**code blocks should ignore the other markdown**`', '2025-12-14 13:32:24', '2025-12-14 13:32:24'),
(10, 'test', '**tesssttttt**text****', '2025-12-14 13:54:29', '2025-12-14 13:54:29'),
(11, 'amon', '> test', '2025-12-14 14:32:57', '2025-12-14 14:32:57'),
(12, 'test', '> test', '2025-12-14 14:35:18', '2025-12-14 14:35:18'),
(13, 'Announcement', 'Get ready to treat yourself! Our delicious Milk Tea varieties are now available – from classic flavors to indulgent specialties like Cookies & Cream, Taro, Red Velvet, Matcha, and Chocolate. Creamy, refreshing, and perfectly sweet – there’s a flavor for everyone! ?✨', '2025-12-14 16:47:31', '2025-12-14 16:47:31');

--
-- Table structure for table `product_addons`
--
DROP TABLE IF EXISTS `product_addons`;

CREATE TABLE `product_addons` (
  `product_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL,
  `is_included` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`product_id`,`addon_id`),
  KEY `fk_pa_addon` (`addon_id`),
  CONSTRAINT `fk_pa_addon` FOREIGN KEY (`addon_id`) REFERENCES `addons` (`addon_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pa_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Data for the table `product_addons`
--
INSERT INTO `product_addons` (`product_id`, `addon_id`, `is_included`) VALUES
(1, 1, 1),
(1, 2, 0),
(1, 3, 0),
(2, 1, 1),
(2, 3, 0),
(3, 2, 0),
(3, 3, 0);

--
-- Table structure for table `products`
--
DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'drink',
  `size_type` varchar(50) DEFAULT 'none',
  `size_prices` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`size_prices`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Data for the table `products`
--
INSERT INTO `products` (`product_id`, `product_name`, `description`, `price`, `image_path`, `category`, `size_type`, `size_prices`, `created_at`) VALUES
(1, 'Matcha', 'A smooth and creamy matcha drink made with finely ground green tea, offering an earthy flavor with a gently sweet finish.', '29.00', 'static/image/products/prod_693e74769707d0.68930576.jpg', 'drink', 's_m_l', '{"S":19,"M":29,"L":39}', '2025-12-14 12:24:33'),
(2, 'Okinawa', 'Rich okinawa brown sugar tea', '29.00', 'static/image/products/prod_693e73fbdd8010.95146945.png', 'drink', 's_m_l', '{"S":19,"M":29,"L":39}', '2025-12-14 12:24:33'),
(3, 'Iced Coffee', 'Fresh brewed cold coffee', '29.00', 'static/image/products/prod_693e72fc5a3e58.39768957.png', 'drink', 's_m_l', '{"S":19,"M":29,"L":39}', '2025-12-14 12:24:33'),
(10, 'Plain Burger with Fries', 'A classic plain burger served on a soft bun with a juicy beef patty, paired with crispy golden fries on the side. Simple, satisfying, and timeless.', '50.00', 'static/image/products/plain_burger_with_fries_20251214_084932_fb5fb4.png', 'food', 'none', NULL, '2025-12-14 15:49:32'),
(11, 'Egg Sandwich', 'A simple egg sandwich made with fluffy eggs layered between fresh bread, lightly seasoned and served warm for a comforting, satisfying bite.', '25.00', 'static/image/products/prod_693e6ede7daee1.72820364.png', 'food', 'none', NULL, '2025-12-14 15:56:58'),
(12, 'Cheesy Hotdog', 'A cheesy hotdog with a grilled sausage tucked into a soft bun and smothered with melted cheese for a rich, savory bite.', '35.00', 'static/image/products/cheesy_hotdog_20251214_085940_d490f2.png', 'food', 'none', NULL, '2025-12-14 15:59:40'),
(13, 'Cheese and Hotdog Bread Rolls', 'Soft bread rolls filled with savory hotdog slices and melted cheese, baked until warm and gooey for a delicious, comforting snack.', '65.00', 'static/image/products/cheese_and_hotdog_bread_rolls_20251214_090353_f31339.png', 'food', 'none', NULL, '2025-12-14 16:03:53'),
(14, 'Cookies & Cream', 'A creamy cookies and cream milk tea blended with smooth milk tea, crushed chocolate cookies, and a rich, sweet finish.', '0.00', 'static/image/products/cookies___cream_20251214_090825_73fb42.jpg', 'drink', 's_m_l', '{"S":19,"M":29,"L":39}', '2025-12-14 16:08:25'),
(15, 'Taro', 'A smooth and creamy taro milk tea with a subtly sweet, nutty flavor and a rich, comforting finish.', '0.00', 'static/image/products/taro_20251214_091230_3269a4.png', 'drink', 's_m_l', '{"S":19,"M":29,"L":39}', '2025-12-14 16:12:30'),
(16, 'Red Velvet', 'A rich and creamy red velvet drink with a smooth cocoa flavor and a lightly sweet, velvety finish.', '29.00', 'static/image/products/prod_693e773fed9345.59919274.png', 'drink', 's_m_l', '{"S":19,"M":29,"L":39}', '2025-12-14 16:14:56'),
(17, 'Chocolate', 'A rich and creamy chocolate milk tea blended with smooth tea, chocolate flavor, and milk for a sweet, indulgent treat.', '0.00', 'static/image/products/chocolate_20251214_093701_00b6d0.png', 'drink', 's_m_l', '{"S":19,"M":29,"L":39}', '2025-12-14 16:37:01'),
(18, 'Cheesy Hotdog Sandwich Overload', 'A loaded cheesy hotdog sandwich packed with juicy hotdog, layers of melted cheese, and served on soft bread for an extra indulgent, satisfying bite.', '60.00', 'static/image/products/cheesy_hotdog_sandwich_overload_20251214_094012_04e11d.png', 'food', 'none', NULL, '2025-12-14 16:40:12');

--
-- Table structure for table `users`
--
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin') DEFAULT 'admin',
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Data for the table `users`
--
INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `username`, `email`, `password`, `role`, `otp_code`, `otp_expiry`, `created_at`) VALUES
(1, 'admin', 'admin', 'admin', 'fishbaitssgg@gmail.com', '$2y$10$u/aVKFcL8zR3CouZSDWjjewwdzVsOW00b5CSSL.s2CKTNm1QvtzYa', 'admin', NULL, NULL, '2025-12-14 12:24:33');