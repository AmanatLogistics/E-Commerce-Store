-- ============================================================================
--  Meenakar — schema and sample data
--
--  Import this once in phpMyAdmin, or from the command line:
--      mysql -u root -p < database.sql
--
--  It creates the `meenakar` database, five tables, one admin account and
--  ten products. Importing it again wipes and rebuilds everything.
--
--  Admin login:  admin  /  meenakar123
--  Change that password after your first sign-in.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `meenakar`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `meenakar`;

-- Dropped child-first so the foreign keys do not complain.
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `admins`;

-- ---------------------------------------------------------------- admins --

CREATE TABLE `admins` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `username`      VARCHAR(50)  NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- password_hash('meenakar123', PASSWORD_DEFAULT)
INSERT INTO `admins` (`username`, `password_hash`) VALUES
('admin', '$2y$12$TX2sAFcJy6jaVA0fQ/LfCe2oB0YbpmUfi8BBPAasOnqMWgbi.rqw2');

-- -------------------------------------------------------------- products --

CREATE TABLE `products` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(150)   NOT NULL,
    `description` TEXT           NOT NULL,
    `price`       DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `stock`       INT            NOT NULL DEFAULT 0,
    `category`    VARCHAR(60)    NOT NULL DEFAULT '',
    `image`       VARCHAR(255)   DEFAULT NULL,
    `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`name`, `description`, `price`, `stock`, `category`, `image`) VALUES
('Multani Blue Pottery Dinner Plate, 10 inch',
 'Thrown and fired in a Multan workshop, then painted freehand in cobalt oxide before the final glaze. The pattern repeats but never lines up exactly, which is how you know a brush made it and not a transfer. Food safe, and it will take the dishwasher if you stack it carefully.',
 2450.00, 24, 'Blue Pottery', 'ring-plate.png'),

('Hand-Painted Tea Set, Six Cups',
 'Six cups and a pot in the eight-petal rosette that Multan potters have painted for two centuries. Each cup takes about forty minutes to paint. Small differences in the petal spacing are normal and are the point.',
 8900.00, 6, 'Blue Pottery', 'tea-set.png'),

('Firozi Glaze Serving Bowl',
 'A deep bowl in the turquoise the workshops call firozi, wide enough for a full biryani or a family-sized salad. The glaze pools slightly darker towards the base, which happens in the kiln and cannot be planned.',
 3200.00, 15, 'Blue Pottery', 'serving-bowl.png'),

('Hammered Brass Surahi',
 'Raised from a single brass sheet and hammered by hand, so the facets catch light differently on every side. Holds just under two litres and keeps water noticeably cool in summer. Wipe with lemon and salt when it dulls.',
 7600.00, 9, 'Brassware', 'surahi.png'),

('Brass Chai Tray, 14 inch',
 'A round tray with a chased rim, sized for a teapot and four cups without crowding. Heavy enough that it does not slide when you set it down. Lacquered, so it keeps its shine without weekly polishing.',
 4100.00, 12, 'Brassware', 'chai-tray.png'),

('Sindhi Ajrak Block-Print Throw',
 'Block-printed in Bhit Shah using the full sixteen-stage ajrak process, with madder red and indigo on cotton. Soft from the first wash and softer every year after. Roughly 110 by 210 centimetres.',
 3750.00, 18, 'Textiles', 'ajrak-throw.png'),

('Chunri Cotton Cushion Covers, Pair',
 'Two covers in tie-dyed chunri cotton, each knotted by hand before dyeing so no two dots are the same size. Hidden zip at the back, sized for a standard 45 centimetre cushion pad. Pads are not included.',
 1900.00, 30, 'Textiles', 'cushion-pair.png'),

('Chiniot Sheesham Spice Box',
 'Carved in Chiniot from seasoned sheesham, with seven lidded compartments and a sliding top. The grain is left open and finished with wax rather than varnish, so the wood keeps its smell. Wipe clean, never soak.',
 5400.00, 7, 'Woodwork', 'spice-box.png'),

('Camel-Skin Naqashi Lamp',
 'A Multani naqashi lamp: camel skin stretched over a wire frame and painted in fine gold and cobalt line work. Lit from inside, the whole shade glows amber and the painting reads as shadow. Takes a standard E27 bulb, 40 watts or less.',
 6800.00, 4, 'Lighting', 'naqashi-lamp.png'),

('Truck Art Enamel Mug',
 'An enamelled steel mug painted in the same palette as a Bedford truck panel, down to the chevrons around the base. Chip resistant rather than chip proof. Holds 350 millilitres and is happy on a camping stove.',
 1250.00, 40, 'Enamelware', 'enamel-mug.png');

-- ---------------------------------------------------------------- orders --

CREATE TABLE `orders` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `customer_name` VARCHAR(120)   NOT NULL,
    `email`         VARCHAR(150)   NOT NULL,
    `phone`         VARCHAR(40)    NOT NULL,
    `address`       TEXT           NOT NULL,
    `notes`         TEXT           NULL,
    `total`         DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `status`        ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled')
                    NOT NULL DEFAULT 'pending',
    `created_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------- order_items --

-- product_id goes NULL if the product is later deleted, so an old order
-- still shows what was bought, at the price it was bought for.
CREATE TABLE `order_items` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `order_id`     INT            NOT NULL,
    `product_id`   INT            NULL,
    `product_name` VARCHAR(150)   NOT NULL,
    `price`        DECIMAL(10, 2) NOT NULL,
    `quantity`     INT            NOT NULL DEFAULT 1,
    KEY `idx_order` (`order_id`),
    CONSTRAINT `fk_items_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------- notifications --

CREATE TABLE `notifications` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `order_id`   INT          NULL,
    `message`    VARCHAR(255) NOT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_unread` (`is_read`),
    CONSTRAINT `fk_notes_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
