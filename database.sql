-- Canteen Management System Database Schema & Seed Data
-- Database: canteen_backend

CREATE DATABASE IF NOT EXISTS `canteen_backend` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `canteen_backend`;
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Drop existing tables in reverse foreign-key order if re-initializing
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `order_item_modifications`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart_item_modifications`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `carts`;
DROP TABLE IF EXISTS `ingredient_allergens`;
DROP TABLE IF EXISTS `food_ingredients`;
DROP TABLE IF EXISTS `ingredients`;
DROP TABLE IF EXISTS `food_items`;
DROP TABLE IF EXISTS `user_allergens`;
DROP TABLE IF EXISTS `allergens`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `address` TEXT NULL,
    `allergen_profile_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Allergens Table
CREATE TABLE `allergens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon` VARCHAR(50) NULL DEFAULT 'fas fa-exclamation-triangle',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. User Allergens Table (Normalized Many-to-Many)
CREATE TABLE `user_allergens` (
    `user_id` INT NOT NULL,
    `allergen_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `allergen_id`),
    CONSTRAINT `fk_ua_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ua_allergen` FOREIGN KEY (`allergen_id`) REFERENCES `allergens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Ingredients Table
CREATE TABLE `ingredients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Ingredient Allergens Table (Many-to-Many: an ingredient can trigger one or more allergens)
CREATE TABLE `ingredient_allergens` (
    `ingredient_id` INT NOT NULL,
    `allergen_id` INT NOT NULL,
    PRIMARY KEY (`ingredient_id`, `allergen_id`),
    CONSTRAINT `fk_ia_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ia_allergen` FOREIGN KEY (`allergen_id`) REFERENCES `allergens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Food Items Table
CREATE TABLE `food_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'lunch',
    `description` TEXT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `image` VARCHAR(255) NOT NULL,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    `ingredients_verified` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Food Ingredients Table (Many-to-Many Recipe Association)
CREATE TABLE `food_ingredients` (
    `food_id` INT NOT NULL,
    `ingredient_id` INT NOT NULL,
    `is_optional_removable` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`food_id`, `ingredient_id`),
    CONSTRAINT `fk_fi_food` FOREIGN KEY (`food_id`) REFERENCES `food_items` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fi_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Carts Table (Associated with User or Session)
CREATE TABLE `carts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Cart Items Table
CREATE TABLE `cart_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cart_id` INT NOT NULL,
    `food_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `allergen_warning` TINYINT(1) NOT NULL DEFAULT 0,
    `allergen_details` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ci_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ci_food` FOREIGN KEY (`food_id`) REFERENCES `food_items` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_cart_food` (`cart_id`, `food_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Cart Item Modifications (Ingredient Removals & Notes)
CREATE TABLE `cart_item_modifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cart_item_id` INT NOT NULL,
    `ingredient_id` INT NULL,
    `ingredient_name` VARCHAR(100) NOT NULL,
    `modification_type` VARCHAR(50) NOT NULL DEFAULT 'removal',
    `instructions` TEXT NULL,
    CONSTRAINT `fk_cim_cart_item` FOREIGN KEY (`cart_item_id`) REFERENCES `cart_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Orders Table
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_code` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_email` VARCHAR(150) NOT NULL,
    `customer_phone` VARCHAR(50) NOT NULL,
    `delivery_address` TEXT NOT NULL,
    `pickup_time` VARCHAR(50) NOT NULL DEFAULT '12:30 PM',
    `subtotal` DECIMAL(10, 2) NOT NULL,
    `tax` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(10, 2) NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
    `special_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Order Items Table
CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `food_id` INT NOT NULL,
    `food_name` VARCHAR(150) NOT NULL,
    `unit_price` DECIMAL(10, 2) NOT NULL,
    `quantity` INT NOT NULL,
    `line_total` DECIMAL(10, 2) NOT NULL,
    `allergen_warning` TINYINT(1) NOT NULL DEFAULT 0,
    `allergen_details` TEXT NULL,
    CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_oi_food` FOREIGN KEY (`food_id`) REFERENCES `food_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Order Item Modifications Table
CREATE TABLE `order_item_modifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_item_id` INT NOT NULL,
    `ingredient_name` VARCHAR(100) NOT NULL,
    `modification_type` VARCHAR(50) NOT NULL DEFAULT 'removal',
    `instructions` TEXT NULL,
    CONSTRAINT `fk_oim_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================================
-- SEED DATA
-- =========================================================================

-- 1. Allergens (The 10 standard allergens specified in requirements)
INSERT INTO `allergens` (`id`, `name`, `slug`, `description`, `icon`) VALUES
(1, 'Peanuts', 'peanuts', 'Peanuts and peanut-derived oils or butter', 'fas fa-seedling'),
(2, 'Tree nuts', 'tree-nuts', 'Almonds, cashews, walnuts, pistachios, hazelnuts, etc.', 'fas fa-tree'),
(3, 'Milk and dairy', 'dairy', 'Cow, goat, sheep milk, cheese, paneer, butter, ghee, cream', 'fas fa-cheese'),
(4, 'Eggs', 'eggs', 'Chicken, duck, or other bird eggs and egg proteins', 'fas fa-egg'),
(5, 'Wheat and gluten', 'gluten', 'Wheat flour, maida, semolina, barley, rye gluten-containing grains', 'fas fa-bread-slice'),
(6, 'Soy', 'soy', 'Soybeans, soy milk, soy sauce, tofu, edamame', 'fas fa-leaf'),
(7, 'Fish', 'fish', 'Freshwater and marine fish, fish sauce, surimi', 'fas fa-fish'),
(8, 'Shellfish', 'shellfish', 'Shrimp, prawns, crab, lobster, oysters, clams', 'fas fa-shrimp'),
(9, 'Sesame', 'sesame', 'Sesame seeds, tahini, sesame oil', 'fas fa-mortar-pestle'),
(10, 'Mustard', 'mustard', 'Mustard seeds, mustard oil, prepared mustard paste, kasundi', 'fas fa-pepper-hot');

-- 2. Master Ingredients
INSERT INTO `ingredients` (`id`, `name`, `description`) VALUES
(1, 'Chicken', 'Boneless fresh chicken pieces'),
(2, 'Paneer (Cottage Cheese)', 'Fresh dairy curd cheese'),
(3, 'Cauliflower', 'Fresh cut cauliflower florets'),
(4, 'Potato (Alu)', 'Diced potatoes'),
(5, 'Pointed Gourd (Potol)', 'Sliced fresh pointed gourd'),
(6, 'Flattened Rice (Chira)', 'Pressed rice flakes'),
(7, 'Eggs', 'Fresh farm eggs'),
(8, 'Rice Flour', 'Pure stone-ground rice flour'),
(9, 'Milk', 'Pasteurized whole dairy milk'),
(10, 'Black Tea Leaves', 'Strong aromatic CTC tea leaves'),
(11, 'Sugar', 'Refined granulated white sugar'),
(12, 'Mustard Oil', 'Cold-pressed pungent mustard oil'),
(13, 'Mustard Seeds / Paste', 'Ground spicy yellow/black mustard seeds'),
(14, 'Ghee / Butter', 'Clarified cow milk butterfat'),
(15, 'Peanuts', 'Roasted crunchy peanuts'),
(16, 'Wheat Flour / Bun', 'Milled wheat flour / bakery bun'),
(17, 'Mayonnaise', 'Creamy emulsion made with eggs and oil'),
(18, 'Cheddar Cheese Slice', 'Processed dairy cheese slice'),
(19, 'Split Bengal Gram (Booter Daal)', 'Yellow split chana gram lentils'),
(20, 'Onion', 'Fresh sliced red onions'),
(21, 'Green Chili', 'Fresh spicy green chilies'),
(22, 'Ginger-Garlic Paste', 'Freshly pureed ginger and garlic'),
(23, 'Aromatic Spices', 'Cumin, coriander, turmeric, cardamom, bay leaves'),
(24, 'Soy Sauce', 'Fermented soybean sauce with wheat');

-- 3. Ingredient Allergen Mappings
-- Peanuts -> Peanuts (1 -> 1)
INSERT INTO `ingredient_allergens` (`ingredient_id`, `allergen_id`) VALUES
(15, 1), -- Peanuts -> Peanuts
(2, 3),  -- Paneer -> Milk and dairy
(9, 3),  -- Milk -> Milk and dairy
(14, 3), -- Ghee / Butter -> Milk and dairy
(18, 3), -- Cheese Slice -> Milk and dairy
(7, 4),  -- Eggs -> Eggs
(17, 4), -- Mayonnaise -> Eggs
(16, 5), -- Wheat Flour / Bun -> Wheat and gluten
(24, 6), -- Soy Sauce -> Soy
(24, 5), -- Soy Sauce -> Wheat and gluten
(12, 10),-- Mustard Oil -> Mustard
(13, 10);-- Mustard Seeds / Paste -> Mustard

-- 4. Food Items (Using pictures/menu/ images with Bangla names)
INSERT INTO `food_items` (`id`, `name`, `category`, `description`, `price`, `image`, `is_available`, `ingredients_verified`) VALUES
(1, 'আলু ভাজি', 'lunch', 'মচমচে আলু ভাজি পেঁয়াজ, কাঁচামরিচ ও সরিষার তেল দিয়ে রান্না।', 35.00, 'pictures/menu/alu bhaaji.jpg', 1, 1),
(2, 'চা', 'drinks', 'সুগন্ধি চা পাতা, দুধ ও চিনি দিয়ে তৈরি গরম চা।', 15.00, 'pictures/menu/cha.jpg', 1, 1),
(3, 'ডাল', 'lunch', 'ঘি ও শুকনো মরিচ দিয়ে তড়কা দেওয়া ধীরে রান্না করা ডাল।', 40.00, 'pictures/menu/daal.jpg', 1, 1),
(4, 'ডিম ভুনা', 'lunch', 'পেঁয়াজ, টমেটো ও মশলা দিয়ে রান্না করা সুস্বাদু ডিম ভুনা।', 55.00, 'pictures/menu/dim bhuna.jpg', 1, 1),
(5, 'ডিম রুটি', 'breakfast', 'নরম রুটির সাথে পরিবেশিত ভাজা ডিম — সকালের পছন্দের নাস্তা।', 50.00, 'pictures/menu/dim ruti.jpg', 1, 1),
(6, 'দই চিড়া', 'breakfast', 'তাজা দই ও চিড়া দিয়ে তৈরি, চিনি ও আম দিয়ে পরিবেশিত হালকা নাস্তা।', 60.00, 'pictures/menu/doi chira.jpg', 1, 1),
(7, 'ফুলকপির তরকারি', 'lunch', 'বাংলা পাঁচফোড়ন ও সরিষার তেল দিয়ে রান্না করা শীতের ফুলকপির তরকারি।', 45.00, 'pictures/menu/fulkopir torkari.jpg', 1, 1),
(8, 'গরুর মাংস', 'lunch', 'মশলাদার গ্রেভিতে রান্না করা নরম ও সুস্বাদু গরুর মাংস।', 150.00, 'pictures/menu/gorur mangsho.jpg', 1, 1),
(9, 'খিচুড়ি', 'lunch', 'ডাল ও চাল একসাথে রান্না করা ঘি দিয়ে তৈরি উষ্ণ ও পুষ্টিকর খিচুড়ি।', 70.00, 'pictures/menu/khichuri.jpg', 1, 1),
(10, 'কুমড়া পাতার তরকারি', 'lunch', 'সরিষার তেল ও মশলা দিয়ে রান্না করা তাজা কুমড়া পাতার তরকারি।', 30.00, 'pictures/menu/kumro patar torkari.jpg', 1, 1),
(11, 'লাল চা', 'drinks', 'সুগন্ধি দার্জিলিং চা পাতায় তৈরি হালকা ও সতেজ লাল চা।', 10.00, 'pictures/menu/laal cha.jpg', 1, 1),
(12, 'লতি ভাজি', 'lunch', 'কুমড়ার লতি পেঁয়াজ ও মশলা দিয়ে হালকা ভাজা।', 30.00, 'pictures/menu/loti bhaaji.jpg', 1, 1),
(13, 'মাছের ঝোল', 'lunch', 'তাজা মাছ হলুদ, জিরা ও কাঁচামরিচ দিয়ে হালকা মশলায় রান্না করা।', 90.00, 'pictures/menu/macher jhol.jpg', 1, 1),
(14, 'মসুর ডাল', 'lunch', 'পেঁয়াজ, রসুন ও টমেটো দিয়ে রান্না করা মসুর ডাল।', 35.00, 'pictures/menu/masoor daal.jpg', 1, 1),
(15, 'মুরগির মাংস', 'lunch', 'দই ও মশলায় ম্যারিনেট করে রান্না করা সুস্বাদু মুরগির কারি।', 120.00, 'pictures/menu/murgir mangsho.jpg', 1, 1),
(16, 'নিহারি', 'lunch', 'ধীরে রান্না করা গরুর মাংসের পা ও মশলা দিয়ে তৈরি সুস্বাদু নিহারি।', 160.00, 'pictures/menu/nihari.jpg', 1, 1),
(17, 'পরোটা', 'breakfast', 'খাস্তা ও নরম পরোটা ঘি দিয়ে তৈরি — সকালের পছন্দের নাস্তা।', 20.00, 'pictures/menu/porotha.jpg', 1, 1),
(18, 'রুটি', 'breakfast', 'আটা দিয়ে তৈরি নরম ও পুষ্টিকর রুটি — তরকারির সাথে পরিবেশিত।', 10.00, 'pictures/menu/ruti.jpg', 1, 1),
(19, 'শিম ভাজি', 'lunch', 'পেঁয়াজ ও মশলা দিয়ে হালকা ভাজা মৌসুমি শিম।', 35.00, 'pictures/menu/sheem bhaji.jpg', 1, 1),
(20, 'সর্ষে ইলিশ', 'lunch', 'সর্ষের পেস্ট ও কাঁচামরিচ দিয়ে রান্না করা তাজা ইলিশ মাছ — বাঙালির প্রিয় পদ।', 180.00, 'pictures/menu/shorshe ilish.jpg', 1, 1),
(21, 'সিঙারা', 'breakfast', 'আলু ও মশলা ভরা খাস্তা সিঙারা — বিকেলের পছন্দের নাস্তা।', 15.00, 'pictures/menu/singara.jpg', 1, 1),
(22, 'তেহারি', 'lunch', 'গরুর মাংস ও সুগন্ধি চাল দিয়ে রান্না করা মজাদার তেহারি।', 130.00, 'pictures/menu/teheri.jpg', 1, 1);

-- 5. Food Ingredients Association (for 22 Bangla menu items)

-- 1. আলু ভাজি (Alu Bhaaji) - Mustard Oil
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(1, 4, 0),  -- Potato
(1, 12, 0), -- Mustard Oil
(1, 20, 1), -- Onion
(1, 21, 1); -- Green Chili

-- 2. চা (Cha) - Dairy
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(2, 10, 0), -- Tea Leaves
(2, 9, 0),  -- Milk
(2, 11, 1); -- Sugar (removable)

-- 3. ডাল (Daal) - Dairy (Ghee)
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(3, 19, 0), -- Split Gram / Daal
(3, 14, 1), -- Ghee (removable)
(3, 20, 1), -- Onion
(3, 23, 0); -- Spices

-- 4. ডিম ভুনা (Dim Bhuna) - Eggs
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(4, 7, 0),  -- Eggs (core)
(4, 20, 1), -- Onion
(4, 21, 1), -- Green Chili
(4, 22, 0), -- Ginger-Garlic
(4, 23, 0); -- Spices

-- 5. ডিম রুটি (Dim Ruti) - Eggs + Gluten
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(5, 7, 0),  -- Eggs (core)
(5, 16, 0), -- Wheat Flour
(5, 20, 1), -- Onion
(5, 21, 1); -- Green Chili

-- 6. দই চিড়া (Doi Chira) - Dairy
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(6, 6, 0),  -- Flattened Rice (Chira)
(6, 9, 0),  -- Milk/Yogurt (Dairy)
(6, 11, 1); -- Sugar (removable)

-- 7. ফুলকপির তরকারি (Fulkopir Torkari) - Mustard Oil
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(7, 3, 0),  -- Cauliflower
(7, 4, 1),  -- Potato
(7, 12, 0), -- Mustard Oil
(7, 21, 1), -- Green Chili
(7, 23, 0); -- Spices

-- 8. গরুর মাংস (Gorur Mangsho) - Mustard Oil
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(8, 12, 0), -- Mustard Oil
(8, 20, 1), -- Onion
(8, 21, 1), -- Green Chili
(8, 22, 0), -- Ginger-Garlic
(8, 23, 0); -- Spices

-- 9. খিচুড়ি (Khichuri) - Dairy (Ghee)
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(9, 14, 1), -- Ghee (removable)
(9, 19, 0), -- Lentils
(9, 20, 1), -- Onion
(9, 23, 0); -- Spices

-- 10. কুমড়া পাতার তরকারি (Kumro Patar Torkari) - Mustard Oil
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(10, 12, 0), -- Mustard Oil
(10, 20, 1), -- Onion
(10, 21, 1), -- Green Chili
(10, 23, 0); -- Spices

-- 11. লাল চা (Laal Cha) - plain, no major allergens
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(11, 10, 0), -- Tea Leaves
(11, 11, 1); -- Sugar (removable)

-- 12. লতি ভাজি (Loti Bhaaji) - Mustard Oil
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(12, 12, 0), -- Mustard Oil
(12, 20, 1), -- Onion
(12, 21, 1); -- Green Chili

-- 13. মাছের ঝোল (Macher Jhol) - Fish + Mustard Oil
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(13, 12, 0), -- Mustard Oil
(13, 20, 1), -- Onion
(13, 21, 1), -- Green Chili
(13, 23, 0); -- Spices

-- 14. মসুর ডাল (Masoor Daal) - plain
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(14, 19, 0), -- Lentils
(14, 20, 1), -- Onion
(14, 22, 0), -- Ginger-Garlic
(14, 23, 0); -- Spices

-- 15. মুরগির মাংস (Murgir Mangsho) - Mustard Oil
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(15, 1, 0),  -- Chicken
(15, 12, 0), -- Mustard Oil
(15, 20, 1), -- Onion
(15, 21, 1), -- Green Chili
(15, 22, 0), -- Ginger-Garlic
(15, 23, 0); -- Spices

-- 16. নিহারি (Nihari) - Wheat (thickener)
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(16, 16, 0), -- Wheat Flour (thickener)
(16, 20, 1), -- Onion
(16, 22, 0), -- Ginger-Garlic
(16, 23, 0); -- Spices

-- 17. পরোটা (Porotha) - Wheat + Dairy (Ghee)
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(17, 16, 0), -- Wheat Flour
(17, 14, 1); -- Ghee (removable)

-- 18. রুটি (Ruti) - Wheat
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(18, 16, 0); -- Wheat Flour

-- 19. শিম ভাজি (Sheem Bhaji) - Mustard Oil
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(19, 12, 0), -- Mustard Oil
(19, 20, 1), -- Onion
(19, 21, 1); -- Green Chili

-- 20. সর্ষে ইলিশ (Shorshe Ilish) - Fish + Mustard
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(20, 12, 0), -- Mustard Oil
(20, 13, 0), -- Mustard Paste
(20, 21, 1); -- Green Chili

-- 21. সিঙারা (Singara) - Wheat
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(21, 4, 0),  -- Potato (filling)
(21, 16, 0); -- Wheat Flour (pastry)

-- 22. তেহারি (Teheri) - Dairy (Ghee)
INSERT INTO `food_ingredients` (`food_id`, `ingredient_id`, `is_optional_removable`) VALUES
(22, 14, 1), -- Ghee (removable)
(22, 20, 1), -- Onion
(22, 22, 0), -- Ginger-Garlic
(22, 23, 0); -- Spices

-- 6. Demo Users (Pre-hashed with password_hash('password123', PASSWORD_DEFAULT))
-- Hash for 'password123'
-- $2y$10$7vN3lM8C4jQ8U2iWqL6h7eT0rU9h7yF1Qx6lI4m5z7o9p1q2r3s4t
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `phone`, `address`, `allergen_profile_completed`) VALUES
(1, 'Alex Morgan', 'test@example.com', '$2y$10$tCYF0bY6/wcuBk2Srfq13.w3Zow3oT5/GFCiWqIa7FG.w9aT84BkO', '+880 1711 000111', 'Room 304, North Hall, University Campus', 1),
(2, 'Sarah Student', 'newuser@example.com', '$2y$10$tCYF0bY6/wcuBk2Srfq13.w3Zow3oT5/GFCiWqIa7FG.w9aT84BkO', '+880 1811 222333', 'Dormitory B, Room 102', 0);

-- Seed user 1 (Alex Morgan) allergen profile: Dairy (3) and Peanuts (1)
INSERT INTO `user_allergens` (`user_id`, `allergen_id`) VALUES
(1, 1), -- Peanuts
(1, 3); -- Milk and dairy
