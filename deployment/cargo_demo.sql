-- =====================================================================
-- CarGO Transport Services — Deployment / Demo Database Dump
-- Generated: 2026-10-19 11:30:10
-- Character Set: utf8mb4 / utf8mb4_general_ci
-- Application: CarGO PHP/MySQL Transport Services Portfolio
-- Note: All user data and payment credentials have been sanitized
--       with synthetic demo accounts and mock payment records.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+08:00';

-- ---------------------------------------------------------------------
-- Table structure for `users`
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `surname` varchar(50) NOT NULL,
  `birthday` date NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Demo data for `users` (Credentials documented in deployment notes)
INSERT INTO `users` (`user_id`, `username`, `first_name`, `surname`, `birthday`, `email`, `password_hash`, `created_at`) VALUES
(1, 'demo_user', 'Demo', 'Account', '1995-01-01', 'demo@cargo-example.com', '$2y$10$RNT3nYLRzIlcUv6xdtBScuQz1etWgNr4j855ZCsimcMEa3PBYkCq.', '2025-06-01 10:00:00'),
(2, 'test_driver', 'Test', 'Driver', '1998-05-15', 'driver@cargo-example.com', '$2y$10$VwVNcXev7ZxXic2ALb.ReOE0fvaw8FaJz37SRhCHpmcNV9c1CR1/S', '2025-06-01 11:00:00');

-- ---------------------------------------------------------------------
-- Table structure for `cars`
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `cars`;
CREATE TABLE `cars` (
  `car_id` int(11) NOT NULL AUTO_INCREMENT,
  `car_name` varchar(100) NOT NULL,
  `car_picture` varchar(255) DEFAULT NULL,
  `max_passengers` int(11) NOT NULL,
  `gear_type` enum('Auto','Manual') NOT NULL,
  `rental_price_per_day` decimal(10,2) NOT NULL,
  `rating` decimal(2,1) NOT NULL DEFAULT 5.0,
  `review_count` int(11) NOT NULL DEFAULT 1,
  `free_cancellation` tinyint(1) NOT NULL DEFAULT 1,
  `price_guaranteed` tinyint(1) NOT NULL DEFAULT 1,
  `instantly_confirmed` tinyint(1) NOT NULL DEFAULT 1,
  `third_party_liability` tinyint(1) NOT NULL DEFAULT 1,
  `car_type` enum('Sedan','SUV','Sports','Hatchback','Convertible','Truck','Minivan','Other') NOT NULL DEFAULT 'Sedan',
  PRIMARY KEY (`car_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Vehicle catalogue data for `cars` (55 vehicles, paths normalized to images/Cars/)
INSERT INTO `cars` (`car_id`, `car_name`, `car_picture`, `max_passengers`, `gear_type`, `rental_price_per_day`, `rating`, `review_count`, `free_cancellation`, `price_guaranteed`, `instantly_confirmed`, `third_party_liability`, `car_type`) VALUES
(1, 'Ferrari 360', 'images/Cars/Ferrari_360.png', 2, 'Manual', 3000.00, 4.5, 371, 1, 1, 1, 1, 'Sports'),
(2, 'Lamborghini Huracan', 'images/Cars/Lamborghini_Huracan.png', 2, 'Auto', 2800.00, 4.3, 109, 1, 1, 1, 1, 'Sports'),
(3, 'Toyota Corolla', 'images/Cars/Toyota_Corolla.png', 5, 'Auto', 120.00, 4.5, 7729, 1, 1, 1, 1, 'Sedan'),
(4, 'BMW X5', 'images/Cars/BMW_X5.png', 7, 'Auto', 300.00, 3.9, 540, 1, 1, 1, 1, 'SUV'),
(5, 'Chevrolet Spark', 'images/Cars/Chevrolet_Spark.png', 4, 'Auto', 100.00, 4.3, 445, 1, 1, 1, 1, 'Hatchback'),
(6, 'Ford Mustang', 'images/Cars/Ford_Mustang.png', 4, 'Auto', 500.00, 3.5, 772, 1, 1, 1, 1, 'Sports'),
(7, 'Honda Civic', 'images/Cars/Honda_Civic.png', 5, 'Auto', 150.00, 4.0, 989, 1, 1, 1, 1, 'Sedan'),
(8, 'Kia Carnival', 'images/Cars/Kia_Carnival.png', 7, 'Auto', 250.00, 3.8, 605, 1, 1, 1, 1, 'Minivan'),
(9, 'Kia Sportage', 'images/Cars/Kia_Sportage.png', 5, 'Auto', 150.00, 4.3, 345, 1, 1, 1, 1, 'SUV'),
(10, 'Mazda MX-5', 'images/Cars/Mazda_MX-5.png', 2, 'Manual', 400.00, 4.4, 223, 1, 1, 1, 1, 'Convertible'),
(11, 'Mini Cooper', 'images/Cars/Mini_Cooper.png', 4, 'Manual', 100.00, 3.2, 107, 1, 1, 1, 1, 'Hatchback'),
(12, 'Tesla Model 3', 'images/Cars/Tesla_Model3.png', 5, 'Auto', 150.00, 4.2, 802, 1, 1, 1, 1, 'Sedan'),
(13, 'Toyota Tundra', 'images/Cars/Toyota_Tundra.png', 5, 'Auto', 200.00, 3.7, 576, 1, 1, 1, 1, 'Truck'),
(14, 'Toyota Camry', 'images/Cars/Toyota_Camry.png', 5, 'Auto', 180.00, 4.6, 180, 1, 1, 1, 1, 'Sedan'),
(15, 'Honda HR-V', 'images/Cars/Honda_HR-V.png', 5, 'Auto', 160.00, 4.3, 257, 1, 1, 1, 1, 'SUV'),
(16, 'Perodua Myvi', 'images/Cars/Perodua_Myvi.png', 5, 'Auto', 90.00, 4.0, 1176, 1, 1, 1, 1, 'Hatchback'),
(17, 'Hyundai Elantra', 'images/Cars/Hyundai_Elantra.png', 5, 'Auto', 140.00, 3.9, 550, 1, 1, 1, 1, 'Sedan'),
(18, 'Proton X70', 'images/Cars/Proton_X70.png', 5, 'Auto', 170.00, 4.1, 345, 1, 1, 1, 1, 'SUV'),
(19, 'Isuzu D-Max', 'images/Cars/Isuzu_D-Max.png', 5, 'Manual', 200.00, 3.7, 100, 1, 1, 1, 1, 'Truck'),
(20, 'Nissan Almera', 'images/Cars/Nissan_Almera.png', 5, 'Auto', 120.00, 4.1, 560, 1, 1, 1, 1, 'Sedan'),
(21, 'Mercedes-Benz GLC', 'images/Cars/Mercedes-Benz_GLC.png', 5, 'Auto', 500.00, 4.5, 234, 1, 1, 1, 1, 'SUV'),
(22, 'Toyota Innova', 'images/Cars/Toyota_Innova.png', 7, 'Auto', 230.00, 3.8, 435, 1, 1, 1, 1, 'Minivan'),
(23, 'BMW 3 Series', 'images/Cars/BMW_3_Series.png', 5, 'Auto', 350.00, 3.6, 214, 1, 1, 1, 1, 'Sedan'),
(24, 'Suzuki Swift Sport', 'images/Cars/Suzuki_Swift_Sport.png', 4, 'Manual', 130.00, 3.8, 425, 1, 1, 1, 1, 'Hatchback'),
(25, 'Volkswagen Golf R', 'images/Cars/Volkswagen_Golf_R.png', 5, 'Auto', 320.00, 4.4, 545, 1, 1, 1, 1, 'Sports'),
(26, 'Chevrolet Cruze', 'images/Cars/Chevrolet_Cruze.png', 5, 'Auto', 135.00, 4.2, 738, 1, 1, 1, 1, 'Sedan'),
(27, 'Hyundai Tucson', 'images/Cars/Hyundai_Tucson.png', 5, 'Auto', 190.00, 3.9, 1244, 1, 1, 1, 1, 'SUV'),
(28, 'Mitsubishi Triton', 'images/Cars/Mitsubishi_Triton.png', 5, 'Manual', 210.00, 4.5, 897, 1, 1, 1, 1, 'Truck'),
(29, 'Peugeot 208', 'images/Cars/Peugeot_208.png', 5, 'Auto', 115.00, 3.4, 556, 1, 1, 1, 1, 'Hatchback'),
(30, 'Subaru XV', 'images/Cars/Subaru_XV.png', 5, 'Auto', 195.00, 4.1, 1130, 1, 1, 1, 1, 'SUV'),
(31, 'Renault Megane RS', 'images/Cars/Renault_Megane_RS.png', 4, 'Manual', 280.00, 4.7, 1466, 1, 1, 1, 1, 'Sports'),
(32, 'Toyota Hiace', 'images/Cars/Toyota_Hiace.png', 10, 'Manual', 300.00, 3.6, 905, 1, 1, 1, 1, 'Minivan'),
(33, 'Honda Jazz', 'images/Cars/Honda_Jazz.png', 5, 'Auto', 100.00, 3.8, 783, 1, 1, 1, 1, 'Hatchback'),
(34, 'Volkswagen Polo', 'images/Cars/Volkswagen_Polo.png', 5, 'Auto', 110.00, 4.0, 630, 1, 1, 1, 1, 'Hatchback'),
(35, 'Ford Everest', 'images/Cars/Ford_Everest.png', 7, 'Auto', 250.00, 4.6, 1345, 1, 1, 1, 1, 'SUV'),
(36, 'Kia Rio', 'images/Cars/Kia_Rio.png', 5, 'Manual', 95.00, 3.3, 487, 1, 1, 1, 1, 'Hatchback'),
(37, 'Nissan Serena', 'images/Cars/Nissan_Serena.png', 7, 'Auto', 220.00, 3.9, 1021, 1, 1, 1, 1, 'Minivan'),
(38, 'Chery Omoda 5', 'images/Cars/Chery_Omoda_5.png', 5, 'Auto', 170.00, 4.2, 1183, 1, 1, 1, 1, 'SUV'),
(39, 'Fiat 500', 'images/Cars/Fiat_500.png', 4, 'Auto', 140.00, 3.5, 687, 1, 1, 1, 1, 'Convertible'),
(40, 'Jeep Wrangler', 'images/Cars/Jeep_Wrangler.png', 5, 'Manual', 310.00, 4.4, 1156, 1, 1, 1, 1, 'SUV'),
(41, 'Audi A4', 'images/Cars/Audi_A4.png', 5, 'Auto', 270.00, 4.3, 1221, 1, 1, 1, 1, 'Sedan'),
(42, 'Honda CR-V', 'images/Cars/Honda_CR-V.png', 5, 'Auto', 190.00, 4.1, 1387, 1, 1, 1, 1, 'SUV'),
(43, 'Proton Saga', 'images/Cars/Proton_Saga.png', 5, 'Auto', 85.00, 3.9, 1145, 1, 1, 1, 1, 'Sedan'),
(44, 'Toyota Vios', 'images/Cars/Toyota_Vios.png', 5, 'Auto', 130.00, 4.0, 950, 1, 1, 1, 1, 'Sedan'),
(45, 'Ford Ranger', 'images/Cars/Ford_Ranger.png', 5, 'Manual', 210.00, 3.7, 698, 1, 1, 1, 1, 'Truck'),
(46, 'BMW Z4', 'images/Cars/BMW_Z4.png', 2, 'Auto', 520.00, 4.6, 328, 1, 1, 1, 1, 'Convertible'),
(47, 'Perodua Bezza', 'images/Cars/Perodua_Bezza.png', 5, 'Auto', 80.00, 3.8, 888, 1, 1, 1, 1, 'Sedan'),
(48, 'Nissan X-Trail', 'images/Cars/Nissan_X-Trail.png', 5, 'Auto', 200.00, 4.2, 1032, 1, 1, 1, 1, 'SUV'),
(49, 'Mazda CX-5', 'images/Cars/Mazda_CX-5.png', 5, 'Auto', 220.00, 4.4, 1199, 1, 1, 1, 1, 'SUV'),
(50, 'Toyota Alphard', 'images/Cars/Toyota_Alphard.png', 7, 'Auto', 400.00, 4.5, 1320, 1, 1, 1, 1, 'Minivan'),
(51, 'Hyundai i30', 'images/Cars/Hyundai_i30.png', 5, 'Manual', 110.00, 3.6, 765, 1, 1, 1, 1, 'Hatchback'),
(52, 'Audi Q5', 'images/Cars/Audi_Q5.png', 5, 'Auto', 320.00, 4.3, 890, 1, 1, 1, 1, 'SUV'),
(53, 'Toyota Avanza', 'images/Cars/Toyota_Avanza.png', 7, 'Auto', 180.00, 3.8, 1075, 1, 1, 1, 1, 'Minivan'),
(54, 'Volvo XC60', 'images/Cars/Volvo_XC60.png', 5, 'Auto', 340.00, 4.4, 781, 1, 1, 1, 1, 'SUV'),
(55, 'Geely Coolray', 'images/Cars/Geely_Coolray.png', 5, 'Auto', 160.00, 4.1, 998, 1, 1, 1, 1, 'SUV');

-- ---------------------------------------------------------------------
-- Table structure for `cart`
-- ---------------------------------------------------------------------
CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `car_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `begin_date` date NOT NULL,
  `end_date` date NOT NULL,
  `rental_days` int(11) NOT NULL CHECK (`rental_days` >= 1),
  `single_price` decimal(10,2) NOT NULL CHECK (`single_price` >= 0),
  `final_price` decimal(10,2) NOT NULL CHECK (`final_price` >= 0),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cart_id`),
  KEY `user_id` (`user_id`),
  KEY `car_id` (`car_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Table structure for `orders`
-- ---------------------------------------------------------------------
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `payment_method` enum('card','bank','cash') NOT NULL,
  `card_number` varchar(30) DEFAULT NULL,
  `cardholder_name` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `addition_payment` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sanitized demo data for `orders` (9 orders, all real PII replaced with synthetic records)
INSERT INTO `orders` (`order_id`, `user_id`, `username`, `full_name`, `email`, `phone_number`, `payment_method`, `card_number`, `cardholder_name`, `bank_name`, `bank_account`, `created_at`, `addition_payment`, `total_price`) VALUES
(18, 1, 'demo_user', 'Demo Account', 'demo@cargo-example.com', '+60 12-345 6789', 'cash', NULL, NULL, NULL, NULL, '2025-06-13 15:28:37', 0.00, 9000.00),
(19, 2, 'test_driver', 'Test Driver', 'driver@cargo-example.com', '+60 19-876 5432', 'card', '**** **** **** 1234', 'Test Driver', NULL, NULL, '2025-06-13 15:42:39', 0.00, 12000.00),
(20, 1, 'demo_user', 'Demo Account', 'demo@cargo-example.com', '+60 12-345 6789', 'bank', NULL, NULL, 'Maybank (Demo)', '******1234', '2025-06-13 18:07:49', 0.00, 2850.00),
(21, 1, 'demo_user', 'Demo Account', 'demo@cargo-example.com', '+60 12-345 6789', 'cash', NULL, NULL, NULL, NULL, '2025-06-14 00:53:45', 0.00, 200.00),
(26, 1, 'demo_user', 'Demo Account', 'demo@cargo-example.com', '+60 12-345 6789', 'card', '**** **** **** 5678', 'Demo Account', NULL, NULL, '2025-06-19 18:50:07', 450.00, 1050.00),
(29, 2, 'test_driver', 'Test Driver', 'driver@cargo-example.com', '+60 19-876 5432', 'cash', NULL, NULL, NULL, NULL, '2026-10-15 14:03:08', 0.00, 9000.00),
(31, 2, 'test_driver', 'Test Driver', 'driver@cargo-example.com', '+60 19-876 5432', 'bank', NULL, NULL, 'CIMB Bank (Demo)', '******5678', '2026-10-15 22:08:33', 0.00, 2000.00),
(36, 1, 'demo_user', 'Demo Account', 'demo@cargo-example.com', '+60 12-345 6789', 'cash', NULL, NULL, NULL, NULL, '2026-10-16 12:15:27', 0.00, 300.00),
(40, 1, 'demo_user', 'Demo Account', 'demo@cargo-example.com', '+60 12-345 6789', 'cash', NULL, NULL, NULL, NULL, '2026-10-16 12:19:10', 0.00, 300.00);

-- ---------------------------------------------------------------------
-- Table structure for `order_items`
-- ---------------------------------------------------------------------
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `car_name` varchar(100) NOT NULL,
  `single_price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `begin_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `rental_days` int(11) DEFAULT NULL,
  `final_price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Demo data for `order_items` (12 items linked to demo orders)
INSERT INTO `order_items` (`order_item_id`, `order_id`, `car_id`, `car_name`, `single_price`, `quantity`, `begin_date`, `end_date`, `rental_days`, `final_price`) VALUES
(22, 18, 1, 'Ferrari 360', 3000.00, 3, '2025-06-13', '2025-06-14', 1, 9000.00),
(23, 19, 1, 'Ferrari 360', 3000.00, 4, '2025-06-13', '2025-06-14', 1, 12000.00),
(24, 20, 9, 'Kia Sportage', 150.00, 3, '2025-06-13', '2025-06-14', 1, 450.00),
(25, 20, 12, 'Tesla Model 3', 150.00, 2, '2025-06-15', '2025-06-16', 1, 300.00),
(26, 20, 13, 'Toyota Tundra', 200.00, 3, '2025-06-17', '2025-06-20', 3, 1800.00),
(27, 20, 4, 'BMW X5', 300.00, 1, '2025-06-20', '2025-06-21', 1, 300.00),
(28, 21, 5, 'Chevrolet Spark', 100.00, 2, '2025-06-15', '2025-06-16', 1, 200.00),
(35, 26, 5, 'Chevrolet Spark', 100.00, 3, '2025-06-19', '2025-06-21', 2, 600.00),
(40, 29, 1, 'Ferrari 360', 3000.00, 1, '2026-10-21', '2026-10-24', 3, 9000.00),
(42, 31, 13, 'Toyota Tundra', 200.00, 5, '2026-11-17', '2026-11-19', 2, 2000.00),
(45, 36, 1, 'Test Audi A4', 100.00, 1, '2026-10-25', '2026-10-28', 3, 300.00),
(48, 40, 1, 'Test Audi A4', 100.00, 1, '2026-10-25', '2026-10-28', 3, 300.00);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- End of Dump
-- =====================================================================