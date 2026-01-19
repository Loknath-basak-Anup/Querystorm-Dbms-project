-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 17, 2026 at 06:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart_marketplace`
--

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `banner_id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `subtitle` varchar(220) DEFAULT NULL,
  `image_url` text NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `position` varchar(30) NOT NULL DEFAULT 'home_hero',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`banner_id`, `title`, `subtitle`, `image_url`, `link_url`, `position`, `is_active`, `sort_order`, `starts_at`, `ends_at`, `created_at`) VALUES
(1, 'QuickMart Mega Sale 2026', 'Up to 40% off', 'https://i.postimg.cc/8cTLqh08/rflbulti.jpg', '/html/products_page.php', 'products_top', 1, 1, NULL, NULL, '2026-01-10 05:02:16'),
(2, 'New Arrivals', 'Fresh stock added', 'https://i.postimg.cc/dtq9XKbL/ban3.jpg', '/html/products_page.php', 'products_top', 1, 2, NULL, NULL, '2026-01-10 05:02:16');

-- --------------------------------------------------------

--
-- Table structure for table `buyer_profiles`
--

CREATE TABLE `buyer_profiles` (
  `buyer_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buyer_profiles`
--

INSERT INTO `buyer_profiles` (`buyer_id`, `address`, `created_at`) VALUES
(2, 'Dhaka, Bangladesh', '2026-01-10 05:02:15'),
(5, 'Rd. 10, Apt. 177, Dhaka, 1216, BD', '2026-01-10 06:09:48'),
(6, 'Rd. 10, Apt. 177, Dhaka, 1216, BD', '2026-01-10 06:27:08');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `icon_url` varchar(255) DEFAULT NULL,
  `icon_class` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `icon_url`, `icon_class`) VALUES
(1, 'Electronics', NULL, 'fa-solid fa-wrench'),
(2, 'Fashion', NULL, NULL),
(3, 'Home & Kitchen', NULL, NULL),
(4, 'Beauty', NULL, 'fa-solid fa-hand-sparkles'),
(5, 'Sports', NULL, NULL),
(6, 'Books', NULL, 'fa-solid fa-book'),
(7, 'Toys', NULL, NULL),
(8, 'Groceries', NULL, NULL),
(9, 'Furniture', NULL, NULL),
(10, 'Stationery', NULL, NULL),
(11, 'Health & Pharmacy', NULL, NULL),
(12, 'Pet Supplies', NULL, NULL),
(13, 'Automobile', NULL, 'fa-solid fa-car'),
(14, 'Mobile Accessories', NULL, NULL),
(15, 'Computer Accessories', NULL, 'fa-solid fa-computer'),
(16, 'Handicrafts', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `conversation_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `coupon_id` int(11) NOT NULL,
  `code` varchar(40) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `seller_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `stock_qty` int(11) DEFAULT 0,
  `sku` varchar(60) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`inventory_id`, `product_id`, `stock_qty`, `sku`, `updated_at`) VALUES
(1, 1, 38, 'QM-01-0001', '2026-01-10 05:02:16'),
(2, 2, 31, 'QM-01-0002', '2026-01-10 05:02:16'),
(3, 3, 101, 'QM-01-0003', '2026-01-10 05:02:16'),
(4, 4, 23, 'QM-01-0004', '2026-01-10 05:02:16'),
(5, 5, 89, 'QM-02-0005', '2026-01-10 05:02:16'),
(6, 6, 99, 'QM-02-0006', '2026-01-10 05:02:16'),
(7, 7, 31, 'QM-02-0007', '2026-01-10 05:02:16'),
(8, 8, 10, 'QM-02-0008', '2026-01-10 05:02:16'),
(9, 9, 73, 'QM-03-0009', '2026-01-10 05:02:16'),
(10, 10, 38, 'QM-03-0010', '2026-01-10 05:02:16'),
(11, 11, 67, 'QM-03-0011', '2026-01-10 05:02:16'),
(12, 12, 63, 'QM-03-0012', '2026-01-10 05:02:16'),
(13, 13, 112, 'QM-04-0013', '2026-01-10 05:02:16'),
(14, 14, 43, 'QM-04-0014', '2026-01-10 05:02:16'),
(15, 15, 93, 'QM-04-0015', '2026-01-10 05:02:16'),
(16, 16, 57, 'QM-04-0016', '2026-01-10 05:02:16'),
(17, 17, 36, 'QM-05-0017', '2026-01-10 05:02:16'),
(18, 18, 77, 'QM-05-0018', '2026-01-10 05:02:16'),
(19, 19, 9, 'QM-05-0019', '2026-01-10 05:02:16'),
(20, 20, 15, 'QM-05-0020', '2026-01-10 05:02:16'),
(21, 21, 65, 'QM-06-0021', '2026-01-10 05:02:16'),
(22, 22, 53, 'QM-06-0022', '2026-01-10 05:02:16'),
(23, 23, 21, 'QM-06-0023', '2026-01-10 05:02:16'),
(24, 24, 96, 'QM-06-0024', '2026-01-10 05:02:16'),
(25, 25, 43, 'QM-07-0025', '2026-01-10 05:02:16'),
(26, 26, 19, 'QM-07-0026', '2026-01-10 05:02:16'),
(27, 27, 119, 'QM-07-0027', '2026-01-10 05:02:16'),
(28, 28, 62, 'QM-07-0028', '2026-01-10 05:02:16'),
(29, 29, 53, 'QM-08-0029', '2026-01-10 05:02:16'),
(30, 30, 5, 'QM-08-0030', '2026-01-10 05:02:16'),
(31, 31, 85, 'QM-08-0031', '2026-01-10 05:02:16'),
(32, 32, 9, 'QM-08-0032', '2026-01-10 05:02:16'),
(33, 33, 97, 'QM-09-0033', '2026-01-10 05:02:16'),
(34, 34, 71, 'QM-09-0034', '2026-01-10 05:02:16'),
(35, 35, 73, 'QM-09-0035', '2026-01-10 05:02:16'),
(36, 36, 101, 'QM-09-0036', '2026-01-10 05:02:16'),
(37, 37, 112, 'QM-10-0037', '2026-01-10 05:02:16'),
(38, 38, 104, 'QM-10-0038', '2026-01-10 05:02:16'),
(39, 39, 40, 'QM-10-0039', '2026-01-10 05:02:16'),
(40, 40, 43, 'QM-10-0040', '2026-01-10 05:02:16'),
(41, 41, 88, 'QM-11-0041', '2026-01-10 05:02:16'),
(42, 42, 44, 'QM-11-0042', '2026-01-10 05:02:16'),
(43, 43, 76, 'QM-11-0043', '2026-01-10 05:02:16'),
(44, 44, 63, 'QM-11-0044', '2026-01-10 05:02:16'),
(45, 45, 74, 'QM-12-0045', '2026-01-10 05:02:16'),
(46, 46, 102, 'QM-12-0046', '2026-01-10 05:02:16'),
(47, 47, 58, 'QM-12-0047', '2026-01-10 05:02:16'),
(48, 48, 8, 'QM-12-0048', '2026-01-10 05:02:16'),
(49, 49, 107, 'QM-13-0049', '2026-01-10 05:02:16'),
(50, 50, 11, 'QM-13-0050', '2026-01-10 05:02:16'),
(51, 51, 120, 'QM-13-0051', '2026-01-10 05:02:16'),
(52, 52, 34, 'QM-13-0052', '2026-01-10 05:02:16'),
(53, 53, 60, 'QM-14-0053', '2026-01-10 05:02:16'),
(54, 54, 89, 'QM-14-0054', '2026-01-10 05:02:16'),
(55, 55, 85, 'QM-14-0055', '2026-01-10 05:02:16'),
(56, 56, 8, 'QM-14-0056', '2026-01-10 05:02:16'),
(57, 57, 7, 'QM-15-0057', '2026-01-10 05:02:16'),
(58, 58, 93, 'QM-15-0058', '2026-01-10 05:02:16'),
(59, 59, 89, 'QM-15-0059', '2026-01-10 05:02:16'),
(60, 60, 39, 'QM-15-0060', '2026-01-10 05:02:16');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `subcategory_id` int(11) NOT NULL,
  `name` varchar(140) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `seller_id`, `category_id`, `subcategory_id`, `name`, `description`, `price`, `status`, `created_at`) VALUES
(1, 3, 1, 1, 'Walton Smart TV 43\"', 'Walton Smart TV 43\" — Bangladesh market price (2026). Category: Electronics.', 43645.00, 'active', '2026-01-10 05:02:15'),
(2, 3, 1, 1, 'Xiaomi Redmi Note 13', 'Xiaomi Redmi Note 13 — Bangladesh market price (2026). Category: Electronics.', 20972.00, 'active', '2026-01-10 05:02:15'),
(3, 3, 1, 1, 'HP Laptop 15', 'HP Laptop 15 — Bangladesh market price (2026). Category: Electronics.', 52950.00, 'active', '2026-01-10 05:02:15'),
(4, 3, 1, 1, 'Sony Headphones', 'Sony Headphones — Bangladesh market price (2026). Category: Electronics.', 86519.00, 'active', '2026-01-10 05:02:15'),
(5, 3, 2, 2, 'Men\'s Polo T-Shirt', 'Men\'s Polo T-Shirt — Bangladesh market price (2026). Category: Fashion.', 745.00, 'active', '2026-01-10 05:02:15'),
(6, 3, 2, 2, 'Women\'s Salwar Kameez', 'Women\'s Salwar Kameez — Bangladesh market price (2026). Category: Fashion.', 943.00, 'active', '2026-01-10 05:02:15'),
(7, 3, 2, 2, 'Denim Jeans', 'Denim Jeans — Bangladesh market price (2026). Category: Fashion.', 4739.00, 'active', '2026-01-10 05:02:15'),
(8, 3, 2, 2, 'Cotton Hijab', 'Cotton Hijab — Bangladesh market price (2026). Category: Fashion.', 1121.00, 'active', '2026-01-10 05:02:15'),
(9, 3, 3, 3, 'Non-stick Fry Pan', 'Non-stick Fry Pan — Bangladesh market price (2026). Category: Home & Kitchen.', 12432.00, 'active', '2026-01-10 05:02:15'),
(10, 3, 3, 3, 'Electric Kettle', 'Electric Kettle — Bangladesh market price (2026). Category: Home & Kitchen.', 2350.00, 'active', '2026-01-10 05:02:15'),
(11, 3, 3, 3, 'Rice Cooker', 'Rice Cooker — Bangladesh market price (2026). Category: Home & Kitchen.', 17077.00, 'active', '2026-01-10 05:02:15'),
(12, 3, 3, 3, 'Blender', 'Blender — Bangladesh market price (2026). Category: Home & Kitchen.', 7485.00, 'active', '2026-01-10 05:02:15'),
(13, 3, 4, 4, 'Vaseline Body Lotion', 'Vaseline Body Lotion — Bangladesh market price (2026). Category: Beauty.', 303.00, 'active', '2026-01-10 05:02:15'),
(14, 3, 4, 4, 'Face Wash', 'Face Wash — Bangladesh market price (2026). Category: Beauty.', 502.00, 'active', '2026-01-10 05:02:15'),
(15, 3, 4, 4, 'Lip Balm', 'Lip Balm — Bangladesh market price (2026). Category: Beauty.', 1926.00, 'active', '2026-01-10 05:02:15'),
(16, 3, 4, 4, 'Hair Oil', 'Hair Oil — Bangladesh market price (2026). Category: Beauty.', 1862.00, 'active', '2026-01-10 05:02:15'),
(17, 3, 5, 5, 'Cricket Bat', 'Cricket Bat — Bangladesh market price (2026). Category: Sports.', 1444.00, 'active', '2026-01-10 05:02:15'),
(18, 3, 5, 5, 'Badminton Racket', 'Badminton Racket — Bangladesh market price (2026). Category: Sports.', 4243.00, 'active', '2026-01-10 05:02:15'),
(19, 3, 5, 5, 'Yoga Mat', 'Yoga Mat — Bangladesh market price (2026). Category: Sports.', 1786.00, 'active', '2026-01-10 05:02:15'),
(20, 3, 5, 5, 'Football', 'Football — Bangladesh market price (2026). Category: Sports.', 9328.00, 'active', '2026-01-10 05:02:15'),
(21, 3, 6, 6, 'HSC Physics Guide', 'HSC Physics Guide — Bangladesh market price (2026). Category: Books.', 989.00, 'active', '2026-01-10 05:02:15'),
(22, 3, 6, 6, 'DBMS Handbook', 'DBMS Handbook — Bangladesh market price (2026). Category: Books.', 241.00, 'active', '2026-01-10 05:02:15'),
(23, 3, 6, 6, 'Novel Collection', 'Novel Collection — Bangladesh market price (2026). Category: Books.', 1278.00, 'active', '2026-01-10 05:02:15'),
(24, 3, 6, 6, 'English Grammar', 'English Grammar — Bangladesh market price (2026). Category: Books.', 373.00, 'active', '2026-01-10 05:02:15'),
(25, 3, 7, 7, 'Remote Car Toy', 'Remote Car Toy — Bangladesh market price (2026). Category: Toys.', 2028.00, 'active', '2026-01-10 05:02:15'),
(26, 3, 7, 7, 'Building Blocks', 'Building Blocks — Bangladesh market price (2026). Category: Toys.', 5366.00, 'active', '2026-01-10 05:02:15'),
(27, 3, 7, 7, 'Puzzle 500pcs', 'Puzzle 500pcs — Bangladesh market price (2026). Category: Toys.', 5339.00, 'active', '2026-01-10 05:02:15'),
(28, 3, 7, 7, 'Doll Set', 'Doll Set — Bangladesh market price (2026). Category: Toys.', 4975.00, 'active', '2026-01-10 05:02:15'),
(29, 3, 8, 8, 'Soybean Oil 5L', 'Soybean Oil 5L — Bangladesh market price (2026). Category: Groceries.', 206.00, 'active', '2026-01-10 05:02:15'),
(30, 3, 8, 8, 'Aromatic Rice 5kg', 'Aromatic Rice 5kg — Bangladesh market price (2026). Category: Groceries.', 1261.00, 'active', '2026-01-10 05:02:15'),
(31, 3, 8, 8, 'Tea 400g', 'Tea 400g — Bangladesh market price (2026). Category: Groceries.', 1279.00, 'active', '2026-01-10 05:02:15'),
(32, 3, 8, 8, 'Sugar 2kg', 'Sugar 2kg — Bangladesh market price (2026). Category: Groceries.', 892.00, 'active', '2026-01-10 05:02:15'),
(33, 3, 9, 9, 'Plastic Chair', 'Plastic Chair — Bangladesh market price (2026). Category: Furniture.', 3949.00, 'active', '2026-01-10 05:02:15'),
(34, 3, 9, 9, 'Study Table', 'Study Table — Bangladesh market price (2026). Category: Furniture.', 15188.00, 'active', '2026-01-10 05:02:15'),
(35, 3, 9, 9, 'Wardrobe', 'Wardrobe — Bangladesh market price (2026). Category: Furniture.', 3752.00, 'active', '2026-01-10 05:02:15'),
(36, 3, 9, 9, 'Sofa Cushion', 'Sofa Cushion — Bangladesh market price (2026). Category: Furniture.', 37181.00, 'active', '2026-01-10 05:02:15'),
(37, 3, 10, 10, 'Notebook A4', 'Notebook A4 — Bangladesh market price (2026). Category: Stationery.', 166.00, 'active', '2026-01-10 05:02:15'),
(38, 3, 10, 10, 'Ball Pen Pack', 'Ball Pen Pack — Bangladesh market price (2026). Category: Stationery.', 326.00, 'active', '2026-01-10 05:02:15'),
(39, 3, 10, 10, 'Geometry Box', 'Geometry Box — Bangladesh market price (2026). Category: Stationery.', 459.00, 'active', '2026-01-10 05:02:15'),
(40, 3, 10, 10, 'Marker Set', 'Marker Set — Bangladesh market price (2026). Category: Stationery.', 177.00, 'active', '2026-01-10 05:02:15'),
(41, 3, 11, 11, 'Paracetamol 500mg', 'Paracetamol 500mg — Bangladesh market price (2026). Category: Health & Pharmacy.', 2234.00, 'active', '2026-01-10 05:02:15'),
(42, 3, 11, 11, 'Oral Saline', 'Oral Saline — Bangladesh market price (2026). Category: Health & Pharmacy.', 502.00, 'active', '2026-01-10 05:02:15'),
(43, 3, 11, 11, 'Hand Sanitizer', 'Hand Sanitizer — Bangladesh market price (2026). Category: Health & Pharmacy.', 2358.00, 'active', '2026-01-10 05:02:15'),
(44, 3, 11, 11, 'Bandage Roll', 'Bandage Roll — Bangladesh market price (2026). Category: Health & Pharmacy.', 1283.00, 'active', '2026-01-10 05:02:15'),
(45, 3, 12, 12, 'Cat Food 1kg', 'Cat Food 1kg — Bangladesh market price (2026). Category: Pet Supplies.', 3161.00, 'active', '2026-01-10 05:02:15'),
(46, 3, 12, 12, 'Dog Leash', 'Dog Leash — Bangladesh market price (2026). Category: Pet Supplies.', 1888.00, 'active', '2026-01-10 05:02:15'),
(47, 3, 12, 12, 'Pet Shampoo', 'Pet Shampoo — Bangladesh market price (2026). Category: Pet Supplies.', 3278.00, 'active', '2026-01-10 05:02:15'),
(48, 3, 12, 12, 'Bird Seed', 'Bird Seed — Bangladesh market price (2026). Category: Pet Supplies.', 6301.00, 'active', '2026-01-10 05:02:15'),
(49, 3, 13, 13, 'Car Phone Holder', 'Car Phone Holder — Bangladesh market price (2026). Category: Automobile.', 1846.00, 'active', '2026-01-10 05:02:15'),
(50, 3, 13, 13, 'Engine Oil 1L', 'Engine Oil 1L — Bangladesh market price (2026). Category: Automobile.', 9224.00, 'active', '2026-01-10 05:02:15'),
(51, 3, 13, 13, 'Car Vacuum', 'Car Vacuum — Bangladesh market price (2026). Category: Automobile.', 11917.00, 'active', '2026-01-10 05:02:15'),
(52, 3, 13, 13, 'Helmet', 'Helmet — Bangladesh market price (2026). Category: Automobile.', 1278.00, 'active', '2026-01-10 05:02:15'),
(53, 3, 14, 14, 'Fast Charger 25W', 'Fast Charger 25W — Bangladesh market price (2026). Category: Mobile Accessories.', 4743.00, 'active', '2026-01-10 05:02:15'),
(54, 3, 14, 14, 'USB Type-C Cable', 'USB Type-C Cable — Bangladesh market price (2026). Category: Mobile Accessories.', 608.00, 'active', '2026-01-10 05:02:15'),
(55, 3, 14, 14, 'Power Bank 20000mAh', 'Power Bank 20000mAh — Bangladesh market price (2026). Category: Mobile Accessories.', 5190.00, 'active', '2026-01-10 05:02:15'),
(56, 3, 14, 14, 'Bluetooth Earbuds', 'Bluetooth Earbuds — Bangladesh market price (2026). Category: Mobile Accessories.', 1807.00, 'active', '2026-01-10 05:02:15'),
(57, 3, 15, 15, 'Wireless Mouse', 'Wireless Mouse — Bangladesh market price (2026). Category: Computer Accessories.', 16566.00, 'active', '2026-01-10 05:02:15'),
(58, 3, 15, 15, 'Mechanical Keyboard', 'Mechanical Keyboard — Bangladesh market price (2026). Category: Computer Accessories.', 22595.00, 'active', '2026-01-10 05:02:15'),
(59, 3, 15, 15, 'SSD 512GB', 'SSD 512GB — Bangladesh market price (2026). Category: Computer Accessories.', 17723.00, 'active', '2026-01-10 05:02:15'),
(60, 3, 15, 15, 'Webcam 1080p', 'Webcam 1080p — Bangladesh market price (2026). Category: Computer Accessories.', 14311.00, 'active', '2026-01-10 05:02:15');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `image_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_url`, `created_at`) VALUES
(1, 1, 'https://cdn.waltonplaza.com.bd/ba0f9f51-3746-47e6-8c2f-4eab842af24c.jpeg', '2026-01-10 05:02:15'),
(2, 2, 'https://www.mobiledokan.com/media/1710078708cnQp7.webp', '2026-01-10 05:02:15'),
(3, 3, 'https://www.electrosonicbd.com/media/catalog/product/cache/1/image/800x800/9df78eab33525d08d6e5fb8d27136e95/5/1/51rxa1nphel._ac_sl1000_.jpg', '2026-01-10 05:02:15'),
(4, 4, 'https://smartbd.com/wp-content/uploads/2023/05/wh1.jpg', '2026-01-10 05:02:15'),
(5, 5, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSNhkYakmhF81Q751gLH4nC-nr6y3tcvei4dg&s', '2026-01-10 05:02:15'),
(6, 6, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcROxjKE0ORcZoBFMnLybnFrZlJw07vvV7wIGA&s', '2026-01-10 05:02:15'),
(7, 7, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSLzW8pMSirtIaE89C61AVkIHAgy33lpKsgzg&s', '2026-01-10 05:02:15'),
(8, 8, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRFgGx2m93tfsHCkFjBtKpsHWfgIFfyIrSBQQ&s', '2026-01-10 05:02:15'),
(9, 9, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS-CmHNrnvsANwH_W6xzoxdl4BbSxYHyYHJiQ&s', '2026-01-10 05:02:15'),
(10, 10, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRWds7TCCjvyl_bqGrHq5Mj-LxnLTzCTj1__w&s', '2026-01-10 05:02:15'),
(11, 11, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ5G4XY2A_DKnqidqlkul9fZ2dZRwABIk3uBg&s', '2026-01-10 05:02:15'),
(12, 12, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRAo6-hAJuOsC1oExpOsEawFimMT54F44lsqg&s', '2026-01-10 05:02:15'),
(13, 13, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSZyxMsmc05nNolFTLKYNZor62QLXi_2oTm0w&s', '2026-01-10 05:02:15'),
(14, 14, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ0tOKXcZiMH2256mJPTPocvBlh2ocOXVP5dw&s', '2026-01-10 05:02:15'),
(15, 15, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRWay6z7m8oL5IMxZEVHmzSjvrNX2_0-y0F8A&s', '2026-01-10 05:02:15'),
(16, 16, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRabJmNa063OC1UMKqT-gaSAjwwLEqS1OD4Yg&s', '2026-01-10 05:02:15'),
(17, 17, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSBe3UY0Z0Uf4N2ypqFxOtcv8wmwDbOH0Z2CQ&s', '2026-01-10 05:02:15'),
(18, 18, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSrcmQ9D0DgTy1RL-AzKKhvXCGy0PjxLItO-A&s', '2026-01-10 05:02:15'),
(19, 19, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQwELwFxwmO-i3of4nJqmP0VBhkiySQwnMGRg&s', '2026-01-10 05:02:15'),
(20, 20, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTvyBpaSu4CXa5WJdBrXj2iA9jF8yYIyyYcnQ&s', '2026-01-10 05:02:15'),
(21, 21, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSnpGCma999xZUssuNAQ5cpgbN4JNlUDpO_8Q&s', '2026-01-10 05:02:15'),
(22, 22, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTh77BNIqdmOF1fTINR46mrEAYMx22Jx0EQpg&s', '2026-01-10 05:02:15'),
(23, 23, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzVMmdraLqBWw1_8VB7rZPzKflwE7pMgE7GQ&s', '2026-01-10 05:02:15'),
(24, 24, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSGTuKrTGAO91-QfrhuM-7pxt34L7oxOMfrfQ&s', '2026-01-10 05:02:15'),
(25, 25, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRlLAQSR4BHPqkhzSLN6tPRdTiincweU1GF5g&s', '2026-01-10 05:02:15'),
(26, 26, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQmazxJ5FTvg5taAYRqGDzaUKSbxqZpFEodbw&s', '2026-01-10 05:02:15'),
(27, 27, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQJdNlzTAZLk9o0BE4v-fyivvbFVQYYHs346g&s', '2026-01-10 05:02:15'),
(28, 28, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSpNp9pz_U7yVyvRJpWjZAOXbOzgnOSrz7o8Q&s', '2026-01-10 05:02:15'),
(29, 29, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTem35Ccpc025N2YWRMoTHVyS9YWoY_Czoshw&s', '2026-01-10 05:02:15'),
(30, 30, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRH9zJGk1uhA5RARfOcxAM43bOn_trIYjx4rA&s', '2026-01-10 05:02:15'),
(31, 31, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS8USZMOK9yvFaO_VNgOuF0NJCzSgfuh36jaA&s', '2026-01-10 05:02:15'),
(32, 32, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTdk-1De-bmwpl-o9uZQ4TOEkoJ4EeEx6UCAQ&s', '2026-01-10 05:02:15'),
(33, 33, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTEoJZupEQ8RG5j03CoIOdIIoMXeghY5BBMqw&s', '2026-01-10 05:02:15'),
(34, 34, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRe_ceMdIQXCApH1E5CB7EJrFmH5gJlzC5SwA&s', '2026-01-10 05:02:15'),
(35, 35, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQFF5ExwMPe7s_bwN24zMbyOhoXTrz-U4AM_Q&s', '2026-01-10 05:02:15'),
(36, 36, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSxmSlmyO-MMnQU5x0xTsGTdqQPWQdPxk50Kg&s', '2026-01-10 05:02:15'),
(37, 37, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT4nuto8zyEqexogI162s9ltOVq68UOmCZFBQ&s', '2026-01-10 05:02:15'),
(38, 38, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQiQg_Uc79QvrgxhnEHAC50AkAa_Y9ZVN0rWA&s', '2026-01-10 05:02:15'),
(39, 39, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRsodVibQMoLG-54UZuuEpaPtD4o_-KZ_7JQg&s', '2026-01-10 05:02:15'),
(40, 40, 'https://grabieart.com/cdn/shop/files/1_38b2afb4-171e-4f41-b05a-ce0ef97b932d.png?v=1747713824', '2026-01-10 05:02:15'),
(41, 41, 'https://5.imimg.com/data5/SELLER/Default/2023/2/UV/TS/HB/6918745/paracetamol-500mg-tablets-intemol-500-2--1000x1000.jpeg', '2026-01-10 05:02:15'),
(42, 42, 'https://medex.com.bd/storage/images/packaging/orsaline-1025-gm-powder-76097023982-i1-QMxepI8jWqF5kru9O2w7.jpg', '2026-01-10 05:02:15'),
(43, 43, 'https://chaldn.com/_mpimage/sepnil-instant-hand-sanitizer-40-ml?src=https%3A%2F%2Feggyolk.chaldal.com%2Fapi%2FPicture%2FRaw%3FpictureId%3D60016&q=best&v=1&m=400&webp=1', '2026-01-10 05:02:15'),
(44, 44, 'https://epharma.com.bd/storage/app/public/FSaKLTm8a61REnekDS8hSnpZ3KwPvVt2lFcUewA2.jpg', '2026-01-10 05:02:15'),
(45, 45, 'https://petzonebd.com/wp-content/uploads/2022/08/IMG_20240404_021443.jpg', '2026-01-10 05:02:15'),
(46, 46, 'https://k9pro.com.au/cdn/shop/products/DSC_3840__04452.jpg?v=1722490970&width=900', '2026-01-10 05:02:15'),
(47, 47, 'https://petzonebd.com/wp-content/uploads/2021/01/ep-600x600-1.webp', '2026-01-10 05:02:15'),
(48, 48, 'https://petzonebd.com/wp-content/uploads/2024/10/Quik-005.jpg', '2026-01-10 05:02:15'),
(49, 49, 'https://www.gadstyle.com/wp-content/uploads/2025/12/REMAX-RM-C31-Smart-Wireless-Char.webp', '2026-01-10 05:02:15'),
(50, 50, 'https://img.drz.lazcdn.com/static/bd/p/d1070496c8319d623d932446f12a016c.jpg_400x400q75.avif', '2026-01-10 05:02:15'),
(51, 51, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTRSuDkDSVVosRJrtAu4-cJzI7IZHY7f1MRkA&s', '2026-01-10 05:02:15'),
(52, 52, 'https://i0.wp.com/speedoz.com.bd/wp-content/uploads/2021/06/best-helmet-price-in-bd.jpg?resize=1280%2C720&ssl=1', '2026-01-10 05:02:15'),
(53, 53, 'https://img.drz.lazcdn.com/static/bd/p/2799ccdf1d4dadc5100875b77a41a2b0.jpg_400x400q75.avif', '2026-01-10 05:02:15'),
(54, 54, 'https://img.drz.lazcdn.com/g/kf/S4b78221e5b8f42139619bcc89ec26a141.jpg_400x400q75.avif', '2026-01-10 05:02:15'),
(55, 55, 'https://img.drz.lazcdn.com/g/kf/Sbdf963a39372408896e4bb7b6f4d3c1do.jpg_400x400q75.avif', '2026-01-10 05:02:15'),
(56, 56, 'https://img.drz.lazcdn.com/static/bd/p/7e9d7872723cd415d7bee78872e04c36.jpg_400x400q75.avif', '2026-01-10 05:02:15'),
(57, 57, 'https://img.drz.lazcdn.com/static/bd/p/e61843ec5307e224339a1a662148f25a.png_400x400q75.avif', '2026-01-10 05:02:15'),
(58, 58, 'https://img.drz.lazcdn.com/static/bd/p/2a3e1b84c17245dd5db957f633b533e5.jpg_400x400q75.avif', '2026-01-10 05:02:15'),
(59, 59, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQcGHq0_wiDInSc9aj2VcCCkbFUtEE5cNVXCw&s', '2026-01-10 05:02:15'),
(60, 60, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTONT_Ih2jiAHFpVpsfCfb0bUrgfuvpr_iujA&s', '2026-01-10 05:02:15');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `rating` tinyint(3) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'admin'),
(2, 'buyer'),
(3, 'seller');

-- --------------------------------------------------------

--
-- Table structure for table `seller_profiles`
--

CREATE TABLE `seller_profiles` (
  `seller_id` int(11) NOT NULL,
  `shop_name` varchar(120) NOT NULL,
  `shop_description` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_profiles`
--

INSERT INTO `seller_profiles` (`seller_id`, `shop_name`, `shop_description`, `verified`, `created_at`) VALUES
(3, 'Demo Shop', 'Main demo seller shop', 1, '2026-01-10 05:02:15'),
(4, 'Demo Shop 2', 'Second demo seller shop', 1, '2026-01-10 05:02:15');

-- --------------------------------------------------------

--
-- Table structure for table `seller_verification_requests`
--

CREATE TABLE `seller_verification_requests` (
  `request_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `nid` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `business_type` varchar(40) DEFAULT NULL,
  `business_category` varchar(60) DEFAULT NULL,
  `tax_id` varchar(60) DEFAULT NULL,
  `business_license` varchar(80) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `bank_name` varchar(120) DEFAULT NULL,
  `account_name` varchar(120) DEFAULT NULL,
  `account_number` varchar(80) DEFAULT NULL,
  `routing_number` varchar(80) DEFAULT NULL,
  `branch_name` varchar(120) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_reviews`
--

CREATE TABLE `seller_reviews` (
  `review_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `rating` tinyint(3) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--

CREATE TABLE `subcategories` (
  `subcategory_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `icon_url` varchar(255) DEFAULT NULL,
  `icon_class` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subcategories`
--

INSERT INTO `subcategories` (`subcategory_id`, `category_id`, `name`, `icon_url`, `icon_class`, `created_at`) VALUES
(1, 1, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(2, 2, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(3, 3, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(4, 4, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(5, 5, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(6, 6, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(7, 7, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(8, 8, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(9, 9, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(10, 10, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(11, 11, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(12, 12, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(13, 13, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(14, 14, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(15, 15, 'General', NULL, NULL, '2026-01-17 17:23:01'),
(16, 16, 'General', NULL, NULL, '2026-01-17 17:23:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(30) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role_id`, `full_name`, `email`, `phone`, `password`, `status`, `created_at`) VALUES
(1, 1, 'Admin', 'admin@quickmart.test', '01000000000', 'admin123', 'active', '2026-01-10 05:02:15'),
(2, 2, 'Demo Buyer', 'buyer@quickmart.test', '01900000000', 'buyer123', 'active', '2026-01-10 05:02:15'),
(3, 3, 'Demo Seller', 'seller@quickmart.test', '01700000000', 'seller123', 'active', '2026-01-10 05:02:15'),
(4, 3, 'Demo Seller 2', 'seller2@quickmart.test', '01800000000', 'seller123', 'active', '2026-01-10 05:02:15'),
(5, 2, 'Shahriar Ahmed Riaz', 'riazmia@gmail.com', '0199977845612', '*Nobita*', 'active', '2026-01-10 06:09:47'),
(6, 2, 'TM Prince', 'tmprince@gmail.com', '0123547896325', '*NobitaSizuka*', 'active', '2026-01-10 06:27:08');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `txn_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `txn_type` varchar(30) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`banner_id`);

--
-- Indexes for table `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  ADD PRIMARY KEY (`buyer_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `idx_cart_buyer` (`buyer_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD UNIQUE KEY `uq_cart_product` (`cart_id`,`product_id`),
  ADD KEY `fk_ci_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`conversation_id`),
  ADD UNIQUE KEY `uq_conv_pair` (`buyer_id`,`seller_id`),
  ADD KEY `fk_conv_seller` (`seller_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_coupon_active` (`is_active`),
  ADD KEY `idx_coupon_seller` (`seller_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD UNIQUE KEY `product_id` (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_msg_sender` (`sender_id`),
  ADD KEY `idx_msg_conv` (`conversation_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_orders_buyer` (`buyer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_oi_product` (`product_id`),
  ADD KEY `idx_oi_order` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_products_cat` (`category_id`),
  ADD KEY `idx_products_subcat` (`subcategory_id`),
  ADD KEY `idx_products_seller` (`seller_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_pi_product` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `idx_pr_product` (`product_id`),
  ADD KEY `idx_pr_buyer` (`buyer_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `seller_profiles`
--
ALTER TABLE `seller_profiles`
  ADD PRIMARY KEY (`seller_id`);

--
-- Indexes for table `seller_verification_requests`
--
ALTER TABLE `seller_verification_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `idx_svr_seller` (`seller_id`),
  ADD KEY `idx_svr_status` (`status`);

--
-- Indexes for table `seller_reviews`
--
ALTER TABLE `seller_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `idx_sr_seller` (`seller_id`),
  ADD KEY `idx_sr_buyer` (`buyer_id`);

--
-- Indexes for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`subcategory_id`),
  ADD UNIQUE KEY `uq_subcategory` (`category_id`,`name`),
  ADD KEY `idx_subcategories_category` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_role` (`role_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`txn_id`),
  ADD KEY `idx_wallet_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `banner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `coupon_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `seller_reviews`
--
ALTER TABLE `seller_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `subcategory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `txn_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  ADD CONSTRAINT `fk_buyer_user` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_cart_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_ci_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ci_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_conv_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conv_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `coupons`
--
ALTER TABLE `coupons`
  ADD CONSTRAINT `fk_coupon_seller` FOREIGN KEY (`seller_id`) REFERENCES `seller_profiles` (`seller_id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `fk_products_seller` FOREIGN KEY (`seller_id`) REFERENCES `seller_profiles` (`seller_id`),
  ADD CONSTRAINT `fk_products_subcategory` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`subcategory_id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_pr_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pr_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_profiles`
--
ALTER TABLE `seller_profiles`
  ADD CONSTRAINT `fk_seller_user` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `seller_verification_requests`
  ADD CONSTRAINT `fk_svr_seller` FOREIGN KEY (`seller_id`) REFERENCES `seller_profiles` (`seller_id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_reviews`
--
ALTER TABLE `seller_reviews`
  ADD CONSTRAINT `fk_sr_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sr_seller` FOREIGN KEY (`seller_id`) REFERENCES `seller_profiles` (`seller_id`) ON DELETE CASCADE;

--
-- Constraints for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `fk_subcategories_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
