-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 04, 2026 at 05:56 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bihar_food_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `email`, `full_name`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$rmlh6CBKWtzNUzXsXYsETuPLXjrFPSTcPaFew.vHqJQKez5tTTudm', 'admin@biharfood.com', 'Admin User', NULL, '2026-02-03 18:30:49');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `special_instructions` text DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `food_id`, `quantity`, `special_instructions`, `added_at`) VALUES
(1, 1, 1, 2, NULL, '2026-02-03 18:30:49'),
(2, 1, 3, 1, NULL, '2026-02-03 18:30:49'),
(3, 2, 5, 3, NULL, '2026-02-03 18:30:49'),
(4, 2, 10, 2, NULL, '2026-02-03 18:30:49'),
(5, 3, 1, 1, NULL, '2026-02-03 18:30:49'),
(6, 3, 7, 1, NULL, '2026-02-03 18:30:49'),
(8, 4, 9, 3, NULL, '2026-02-03 18:43:57'),
(9, 4, 3, 1, NULL, '2026-02-04 04:32:49');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `is_active`, `display_order`, `created_at`) VALUES
(1, 'Litti Chokha', 'Traditional Bihari roasted wheat balls with mashed vegetables', 'assets/uploads/categories/litti-chokha.jpg', 1, 1, '2026-02-03 18:30:49'),
(2, 'Sattu Dishes', 'Nutritious roasted gram flour specialties', 'assets/uploads/categories/sattu-dishes.jpg', 1, 2, '2026-02-03 18:30:49'),
(3, 'Breads & Parathas', 'Traditional Indian breads and stuffed parathas', 'assets/uploads/categories/breads.jpg', 1, 3, '2026-02-03 18:30:49'),
(4, 'Rice Dishes', 'Authentic Bihari rice preparations', 'assets/uploads/categories/rice-dishes.jpg', 1, 4, '2026-02-03 18:30:49'),
(5, 'Snacks & Starters', 'Delicious snacks and appetizers', 'assets/uploads/categories/snacks.jpg', 1, 5, '2026-02-03 18:30:49'),
(6, 'Sweets & Desserts', 'Traditional Bihari sweets and desserts', 'assets/uploads/categories/sweets.jpg', 1, 6, '2026-02-03 18:30:49'),
(7, 'Beverages', 'Refreshing drinks and beverages', 'assets/uploads/categories/1770146163_Beverages.jpg', 1, 7, '2026-02-03 18:30:49');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_until` date NOT NULL,
  `usage_limit` int(11) DEFAULT 1,
  `used_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `description`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount_amount`, `valid_from`, `valid_until`, `usage_limit`, `used_count`, `is_active`, `created_at`) VALUES
(1, 'WELCOME20', 'Welcome discount for new customers', 'percentage', 20.00, 200.00, 100.00, '2024-01-01', '2024-12-31', 1, 0, 1, '2026-02-03 18:30:49'),
(2, 'FIRST50', 'Flat ₹50 off on first order', 'fixed', 50.00, 300.00, 50.00, '2024-01-01', '2024-12-31', 1, 0, 1, '2026-02-03 18:30:49'),
(3, 'HOLI30', 'Holi special 30% off', 'percentage', 30.00, 500.00, 200.00, '2024-03-01', '2024-03-31', 100, 0, 1, '2026-02-03 18:30:49'),
(4, 'FREEDEL', 'Free delivery on orders above ₹300', 'fixed', 40.00, 300.00, 40.00, '2024-01-01', '2024-12-31', 9999, 0, 1, '2026-02-03 18:30:49'),
(5, 'WEEKEND25', 'Weekend special 25% off', 'percentage', 25.00, 400.00, 150.00, '2024-01-01', '2024-12-31', 1000, 0, 1, '2026-02-03 18:30:49');

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usage`
--

CREATE TABLE `coupon_usage` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_addresses`
--

CREATE TABLE `delivery_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(50) NOT NULL,
  `full_address` text NOT NULL,
  `landmark` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_addresses`
--

INSERT INTO `delivery_addresses` (`id`, `user_id`, `label`, `full_address`, `landmark`, `city`, `state`, `pincode`, `latitude`, `longitude`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 1, 'Home', '123 MG Road, Patna', 'Near Golambar', 'Patna', 'Bihar', '800001', NULL, NULL, 1, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(2, 1, 'Office', '456 Frazer Road, Patna', 'Opposite Bank of India', 'Patna', 'Bihar', '800002', NULL, NULL, 0, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(3, 2, 'Home', '789 Gandhi Maidan, Gaya', 'Near Temple', 'Gaya', 'Bihar', '823001', NULL, NULL, 1, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(4, 3, 'Residence', '321 Bhagalpur Road', 'Near College', 'Bhagalpur', 'Bihar', '812001', NULL, NULL, 1, '2026-02-03 18:30:49', '2026-02-03 18:30:49');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `food_quality` int(11) DEFAULT NULL CHECK (`food_quality` >= 1 and `food_quality` <= 5),
  `delivery_service` int(11) DEFAULT NULL CHECK (`delivery_service` >= 1 and `delivery_service` <= 5),
  `suggestions` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','archived') DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `order_id`, `message`, `rating`, `food_quality`, `delivery_service`, `suggestions`, `status`, `admin_response`, `responded_at`, `responded_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Excellent food! The litti chokha was authentic and delicious. Delivery was on time.', 5, 5, 5, NULL, 'resolved', NULL, NULL, NULL, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(2, 2, 2, 'Thekua was very fresh and tasty. Will order again!', 4, 4, 5, NULL, 'reviewed', NULL, NULL, NULL, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(3, 3, 3, 'Food was good but delivery was delayed by 30 minutes.', 3, 4, 3, 'Improve delivery time', 'pending', NULL, NULL, NULL, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(4, 4, NULL, 'your product wrong', 1, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-02-03 18:40:18', '2026-02-03 18:40:18'),
(5, 4, NULL, 'your product wrong', 1, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-02-03 18:41:00', '2026-02-03 18:41:00');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_archive`
--

CREATE TABLE `feedback_archive` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `food_quality` int(11) DEFAULT NULL CHECK (`food_quality` >= 1 and `food_quality` <= 5),
  `delivery_service` int(11) DEFAULT NULL CHECK (`delivery_service` >= 1 and `delivery_service` <= 5),
  `suggestions` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','archived') DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `foods`
--

CREATE TABLE `foods` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_popular` tinyint(1) DEFAULT 0,
  `preparation_time` int(11) DEFAULT NULL COMMENT 'In minutes',
  `calories` int(11) DEFAULT NULL,
  `ingredients` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `foods`
--

INSERT INTO `foods` (`id`, `name`, `description`, `price`, `original_price`, `category_id`, `image_path`, `is_available`, `is_featured`, `is_popular`, `preparation_time`, `calories`, `ingredients`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Litti Chokha Combo', 'Freshly roasted littis with baingan chokha, aloo chokha, and green chutney. Served with ghee and pickles.', 180.00, 200.00, 1, 'foods/litti-chokha.jpg', 1, 1, 1, 25, 450, 'Wheat flour, sattu, baingan, potatoes, tomatoes, onions, spices, ghee', 1, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(2, 'Masala Litti', 'Spicy littis stuffed with masala sattu filling, served with chokha', 150.00, NULL, 1, 'foods/masala-litti.jpg', 1, 0, 1, 20, 380, 'Wheat flour, sattu, spices, herbs', 2, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(3, 'Sattu Paratha', 'Stuffed paratha with spiced sattu filling, served with curd and pickle', 90.00, 100.00, 2, 'foods/sattu-paratha.jpg', 1, 1, 1, 15, 320, 'Wheat flour, sattu, spices, ghee', 1, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(4, 'Sattu Sharbat', 'Refreshing sattu drink with spices and lemon', 40.00, 50.00, 2, 'foods/sattu-sharbat.jpg', 1, 1, 1, 5, 150, 'Sattu, lemon, spices, sugar', 2, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(5, 'Thekua', 'Traditional Bihari sweet biscuit', 80.00, 100.00, 3, 'foods/thekua.jpg', 1, 1, 1, 20, 250, 'Wheat flour, sugar, ghee, cardamom', 1, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(6, 'Dal Puri', 'Stuffed puri with spiced lentil filling', 120.00, NULL, 3, 'foods/dal-puri.jpg', 1, 0, 1, 25, 380, 'Wheat flour, lentils, spices, oil', 2, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(7, 'Kadhi Chawal', 'Chickpea flour curry with rice', 130.00, 150.00, 4, 'foods/kadhi-chawal.jpg', 1, 1, 1, 30, 450, 'Rice, chickpea flour, curd, spices', 1, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(8, 'Samosa', 'Crispy pastry with spiced potato filling', 40.00, 50.00, 5, 'foods/samosa.jpg', 1, 1, 1, 15, 200, 'Flour, potatoes, peas, spices, oil', 1, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(9, 'Khaja', 'Layered sweet pastry', 200.00, 220.00, 6, 'foods/khaja.jpg', 1, 1, 1, 30, 400, 'Flour, sugar, ghee, cardamom', 1, '2026-02-03 18:30:49', '2026-02-03 18:30:49'),
(10, 'Masala Chai', 'Spiced Indian tea', 30.00, 40.00, 7, 'foods/masala-chai.jpg', 1, 1, 1, 10, 80, 'Tea leaves, milk, spices, sugar', 1, '2026-02-03 18:30:49', '2026-02-03 18:30:49');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order','promotion','system','feedback') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `related_id`, `created_at`) VALUES
(1, 1, 'Order Confirmed', 'Your order #BHF000120401 has been confirmed and is being prepared.', 'order', 1, 1, '2026-02-03 18:30:49'),
(2, 1, 'Order Delivered', 'Your order #BHF000120401 has been delivered successfully.', 'order', 1, 1, '2026-02-03 18:30:49'),
(3, 2, 'Order Confirmed', 'Your order #BHF000220401 has been confirmed.', 'order', 1, 2, '2026-02-03 18:30:49'),
(4, 2, 'Order Delivered', 'Your order #BHF000220401 has been delivered.', 'order', 1, 2, '2026-02-03 18:30:49'),
(5, 3, 'Order Confirmed', 'Your order #BHF000320401 has been confirmed.', 'order', 0, 3, '2026-02-03 18:30:49'),
(6, 1, 'Special Offer', 'Get 20% off on your next order with code WELCOME20', 'promotion', 0, NULL, '2026-02-03 18:30:49');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(20) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_charge` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  `payment_method` enum('cash','online','card') DEFAULT 'cash',
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_id` varchar(100) DEFAULT NULL,
  `delivery_address` text NOT NULL,
  `delivery_instructions` text DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(15) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `estimated_delivery_time` timestamp NULL DEFAULT NULL,
  `actual_delivery_time` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `total_price`, `subtotal`, `delivery_charge`, `tax_amount`, `discount_amount`, `status`, `payment_method`, `payment_status`, `payment_id`, `delivery_address`, `delivery_instructions`, `customer_name`, `customer_phone`, `customer_email`, `estimated_delivery_time`, `actual_delivery_time`, `cancelled_at`, `cancellation_reason`, `order_date`, `updated_at`) VALUES
(1, 1, 'BHF000120401', 265.00, 225.00, 40.00, 0.00, 0.00, 'delivered', 'cash', 'completed', NULL, 'Patna, Bihar', NULL, 'Rahul Kumar', '9876543210', 'rahul@example.com', NULL, NULL, NULL, NULL, '2024-01-20 07:00:00', '2026-02-03 18:30:49'),
(2, 2, 'BHF000220401', 180.00, 180.00, 0.00, 0.00, 0.00, 'delivered', 'online', 'completed', NULL, 'Gaya, Bihar', NULL, 'Priya Sharma', '8765432109', 'priya@example.com', NULL, NULL, NULL, NULL, '2024-01-22 09:15:00', '2026-02-03 18:30:49'),
(3, 3, 'BHF000320401', 350.00, 320.00, 30.00, 0.00, 0.00, 'preparing', 'cash', 'pending', NULL, 'Bhagalpur, Bihar', NULL, 'Amit Singh', '7654321098', 'amit@example.com', NULL, NULL, NULL, NULL, '2024-01-25 12:50:00', '2026-02-03 18:30:49'),
(4, 4, 'ORD202602031222', 229.00, 180.00, 40.00, 9.00, 0.00, 'cancelled', 'cash', 'pending', NULL, 'jamui bihar\r\n', '', 'ajay', '+919608783528', 'ajay@gmail.com', '2026-02-03 19:19:21', NULL, '2026-02-03 18:41:31', 'Cancelled by user', '2026-02-03 18:34:21', '2026-02-03 18:41:31');

-- --------------------------------------------------------

--
-- Table structure for table `orders_archive`
--

CREATE TABLE `orders_archive` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(20) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_charge` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  `payment_method` enum('cash','online','card') DEFAULT 'cash',
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_id` varchar(100) DEFAULT NULL,
  `delivery_address` text NOT NULL,
  `delivery_instructions` text DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(15) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `estimated_delivery_time` timestamp NULL DEFAULT NULL,
  `actual_delivery_time` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `food_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `special_instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `food_id`, `food_name`, `quantity`, `unit_price`, `total_price`, `special_instructions`) VALUES
(1, 1, 1, 'Litti Chokha Combo', 1, 180.00, 180.00, NULL),
(2, 1, 3, 'Sattu Paratha', 1, 90.00, 90.00, NULL),
(3, 2, 5, 'Thekua', 2, 80.00, 160.00, NULL),
(4, 2, 10, 'Masala Chai', 1, 30.00, 30.00, NULL),
(5, 3, 1, 'Litti Chokha Combo', 2, 180.00, 360.00, NULL),
(6, 4, 1, 'Litti Chokha Combo', 1, 180.00, 180.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items_archive`
--

CREATE TABLE `order_items_archive` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `food_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `special_instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
--

CREATE TABLE `order_tracking` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled') NOT NULL,
  `message` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `tracked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_tracking`
--

INSERT INTO `order_tracking` (`id`, `order_id`, `status`, `message`, `location`, `tracked_at`) VALUES
(1, 1, 'pending', 'Order placed successfully', NULL, '2024-01-20 07:00:00'),
(2, 1, 'confirmed', 'Order confirmed by restaurant', NULL, '2024-01-20 07:05:00'),
(3, 1, 'preparing', 'Food is being prepared', NULL, '2024-01-20 07:20:00'),
(4, 1, 'out_for_delivery', 'Order is out for delivery', NULL, '2024-01-20 07:50:00'),
(5, 1, 'delivered', 'Order delivered successfully', NULL, '2024-01-20 08:15:00'),
(6, 2, 'pending', 'Order placed successfully', NULL, '2024-01-22 09:15:00'),
(7, 2, 'confirmed', 'Order confirmed by restaurant', NULL, '2024-01-22 09:20:00'),
(8, 2, 'delivered', 'Order delivered successfully', NULL, '2024-01-22 10:00:00'),
(9, 3, 'pending', 'Order placed successfully', NULL, '2024-01-25 12:50:00'),
(10, 3, 'confirmed', 'Order confirmed by restaurant', NULL, '2024-01-25 12:55:00'),
(11, 3, 'preparing', 'Food is being prepared', NULL, '2024-01-25 13:10:00'),
(12, 4, 'cancelled', 'Order cancelled by user', NULL, '2026-02-03 18:41:31');

-- --------------------------------------------------------

--
-- Stand-in structure for view `sales_report`
-- (See below for the actual view)
--
CREATE TABLE `sales_report` (
`sale_date` date
,`total_orders` bigint(21)
,`total_revenue` decimal(32,2)
,`avg_order_value` decimal(14,6)
,`delivered_orders` decimal(22,0)
,`cancelled_orders` decimal(22,0)
,`online_payments` decimal(22,0)
,`cash_payments` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'site_name', 'Bihar Traditional Food', 'text', 'Website name', '2026-02-03 18:30:49'),
(2, 'site_email', 'orders@biharfood.com', 'text', 'Contact email', '2026-02-03 18:30:49'),
(3, 'site_phone', '+91 123 456 7890', 'text', 'Contact phone', '2026-02-03 18:30:49'),
(4, 'delivery_charge', '40', 'number', 'Default delivery charge', '2026-02-03 18:30:49'),
(5, 'free_delivery_minimum', '300', 'number', 'Minimum order for free delivery', '2026-02-03 18:30:49'),
(6, 'tax_rate', '5', 'number', 'Tax percentage', '2026-02-03 18:30:49'),
(7, 'currency', '₹', 'text', 'Currency symbol', '2026-02-03 18:30:49'),
(8, 'opening_time', '10:00', 'text', 'Opening time', '2026-02-03 18:30:49'),
(9, 'closing_time', '22:00', 'text', 'Closing time', '2026-02-03 18:30:49'),
(10, 'maintenance_mode', '0', 'boolean', 'Maintenance mode status', '2026-02-03 18:30:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Rahul Kumar', 'rahul@gmail.com', '$2y$10$YQTmfDfL.fZn6w7Ov3nfMOlNTm4Q8xp8wN4QyF9wHNdBpZXZvK3fS', '9876543210', 'Patna, Bihar', 'user', '2024-01-15 05:00:00', '2026-02-03 18:31:25'),
(2, 'Priya Sharma', 'priya@example.com', '$2y$10$YQTmfDfL.fZn6w7Ov3nfMOlNTm4Q8xp8wN4QyF9wHNdBpZXZvK3fS', '8765432109', 'Gaya, Bihar', 'user', '2024-01-20 09:15:00', '2026-02-03 18:30:49'),
(3, 'Amit Singh', 'amit@example.com', '$2y$10$YQTmfDfL.fZn6w7Ov3nfMOlNTm4Q8xp8wN4QyF9wHNdBpZXZvK3fS', '7654321098', 'Bhagalpur, Bihar', 'user', '2024-02-01 03:45:00', '2026-02-03 18:30:49'),
(4, 'ajay', 'ajay@gmail.com', '$2y$10$rmlh6CBKWtzNUzXsXYsETuPLXjrFPSTcPaFew.vHqJQKez5tTTudm', '+919608783528', 'jamui bihar\r\n', 'user', '2026-02-03 18:33:25', '2026-02-03 18:33:25');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `food_id`, `added_at`) VALUES
(1, 1, 1, '2026-02-03 18:30:49'),
(2, 1, 4, '2026-02-03 18:30:49'),
(3, 2, 5, '2026-02-03 18:30:49'),
(4, 2, 8, '2026-02-03 18:30:49'),
(5, 3, 1, '2026-02-03 18:30:49'),
(6, 3, 7, '2026-02-03 18:30:49');

-- --------------------------------------------------------

--
-- Structure for view `sales_report`
--
DROP TABLE IF EXISTS `sales_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sales_report`  AS SELECT cast(`o`.`order_date` as date) AS `sale_date`, count(0) AS `total_orders`, sum(`o`.`total_price`) AS `total_revenue`, avg(`o`.`total_price`) AS `avg_order_value`, sum(case when `o`.`status` = 'delivered' then 1 else 0 end) AS `delivered_orders`, sum(case when `o`.`status` = 'cancelled' then 1 else 0 end) AS `cancelled_orders`, sum(case when `o`.`payment_method` = 'online' then 1 else 0 end) AS `online_payments`, sum(case when `o`.`payment_method` = 'cash' then 1 else 0 end) AS `cash_payments` FROM `orders` AS `o` GROUP BY cast(`o`.`order_date` as date) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_last_login` (`last_login`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`food_id`),
  ADD KEY `food_id` (`food_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_added_at` (`added_at`),
  ADD KEY `idx_cart_user_food` (`user_id`,`food_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_validity` (`valid_from`,`valid_until`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_coupons_validity` (`valid_from`,`valid_until`,`is_active`);

--
-- Indexes for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_coupon_order` (`coupon_id`,`order_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `delivery_addresses`
--
ALTER TABLE `delivery_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_is_default` (`is_default`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `responded_by` (`responded_by`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_feedback_user_date` (`user_id`,`created_at`);

--
-- Indexes for table `feedback_archive`
--
ALTER TABLE `feedback_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `responded_by` (`responded_by`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_availability` (`is_available`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_popular` (`is_popular`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_foods_category_price` (`category_id`,`price`,`is_available`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_notifications_user_date` (`user_id`,`created_at`,`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_order_date` (`order_date`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_orders_user_status` (`user_id`,`status`),
  ADD KEY `idx_orders_date_status` (`order_date`,`status`);

--
-- Indexes for table `orders_archive`
--
ALTER TABLE `orders_archive`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_order_date` (`order_date`),
  ADD KEY `idx_order_number` (`order_number`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_food` (`food_id`),
  ADD KEY `idx_order_items_order_food` (`order_id`,`food_id`);

--
-- Indexes for table `order_items_archive`
--
ALTER TABLE `order_items_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_food` (`food_id`);

--
-- Indexes for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_tracked_at` (`tracked_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist_item` (`user_id`,`food_id`),
  ADD KEY `food_id` (`food_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_addresses`
--
ALTER TABLE `delivery_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `feedback_archive`
--
ALTER TABLE `feedback_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders_archive`
--
ALTER TABLE `orders_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items_archive`
--
ALTER TABLE `order_items_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD CONSTRAINT `coupon_usage_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `coupon_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `coupon_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `delivery_addresses`
--
ALTER TABLE `delivery_addresses`
  ADD CONSTRAINT `delivery_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`responded_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `foods`
--
ALTER TABLE `foods`
  ADD CONSTRAINT `foods_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `order_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
