-- Food Dash – Development Seed Script for Standalone Module 3 Testing
-- Use this script only if setting up a fresh local test database from scratch.
CREATE DATABASE IF NOT EXISTS food_dash CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE food_dash;

-- 1. Users table (Module 1)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('Active','Suspended') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Addresses table (Module 1)
CREATE TABLE IF NOT EXISTS addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label ENUM('Home','Work','Other') NOT NULL DEFAULT 'Home',
    house VARCHAR(150) NOT NULL,
    locality VARCHAR(150) NOT NULL,
    city VARCHAR(80) NOT NULL,
    state VARCHAR(80) NOT NULL,
    pincode CHAR(6) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Restaurants & Categories (Module 2)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    cuisine VARCHAR(200) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    hours VARCHAR(60) NOT NULL DEFAULT '11:00 AM - 11:00 PM',
    rating DECIMAL(2,1) NOT NULL DEFAULT 4.0,
    delivery_time VARCHAR(20) NOT NULL DEFAULT '25-30 min',
    status ENUM('Open','Closed','Pending') NOT NULL DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Menu Items (Module 2)
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(500) NULL,
    price DECIMAL(8,2) NOT NULL,
    is_veg TINYINT(1) NOT NULL DEFAULT 1,
    in_stock TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- 5. Coupons (Module 5)
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    offer_category ENUM('FLAT','BANK','FIRST','BOGO') NOT NULL DEFAULT 'FLAT',
    discount_type ENUM('PERCENT','FLAT','DELIVERY') NOT NULL,
    discount_value DECIMAL(8,2) NOT NULL DEFAULT 0,
    max_discount DECIMAL(8,2) NOT NULL DEFAULT 0,
    min_order DECIMAL(8,2) NOT NULL DEFAULT 0,
    badge VARCHAR(30) NULL,
    expiry_date DATE NOT NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB;

-- Seed Data (INSERT IGNORE preserves any existing records)
INSERT IGNORE INTO users (id, name, email, phone, password_hash, status) VALUES
(1, 'Demo Customer', 'demo@fooddash.test', '9876543210', '$2y$10$iHbM7TDXoicKOzu3v.IJ2ehXmx318fxi6FYcSJXDvcy3m/RMh2D0.', 'Active'),
(2, 'Sarah Smith', 'sarah@fooddash.test', '9876501234', '$2y$10$iHbM7TDXoicKOzu3v.IJ2ehXmx318fxi6FYcSJXDvcy3m/RMh2D0.', 'Active');

INSERT IGNORE INTO addresses (id, user_id, label, house, locality, city, state, pincode, is_default) VALUES
(1, 1, 'Home', 'Flat 301, Royal Heights', 'Near Car Street, Kunjibettu', 'Udupi', 'Karnataka', '576101', 1),
(2, 1, 'Work', 'Office 5B, Tech Park', 'Manipal Road', 'Udupi', 'Karnataka', '576104', 0);

INSERT IGNORE INTO categories (id, name) VALUES
(1, 'Starters'), (2, 'Main Course'), (3, 'Desserts'), (4, 'Beverages');

INSERT IGNORE INTO restaurants (id, name, cuisine, address, phone, rating, delivery_time) VALUES
(1, 'The Gourmet Bistro', 'Italian, Pasta, Woodfired Pizza', '452 Car Street, Udupi', '+91 820 252 1049', 4.8, '25-30 min'),
(2, 'Spice Symphony', 'Indian, Curry, Tandoor', '108 Ocean Drive, Malpe', '+91 820 252 2011', 4.9, '30-40 min'),
(3, 'Burger & Craft House', 'American, Burgers, Fries', '12 Grill Road, Manipal', '+91 820 252 3300', 4.6, '20-25 min');

INSERT IGNORE INTO menu_items (id, restaurant_id, category_id, name, price, is_veg, in_stock) VALUES
(1, 1, 2, 'Truffle Mushroom Fettuccine', 450.00, 1, 1),
(2, 1, 2, 'Woodfired Margherita Pizza', 375.00, 1, 1),
(3, 1, 3, 'Classic Tiramisu', 225.00, 1, 1),
(4, 1, 1, 'Garlic Bread Sticks', 210.00, 1, 1),
(5, 2, 2, 'Fiery Chicken Tikka Masala', 499.00, 0, 1),
(6, 2, 1, 'Crispy Samosa Platter', 180.00, 1, 1),
(7, 3, 2, 'Double Bacon Smash Burger', 380.00, 0, 1);

INSERT IGNORE INTO coupons (id, code, title, description, discount_type, discount_value, max_discount, min_order, badge, expiry_date, status) VALUES
(1, 'NEXUS50', '50% OFF up to Rs.150', 'Get 50% off on orders above Rs.200', 'PERCENT', 50.00, 150.00, 200.00, 'POPULAR', '2027-12-31', 'Active'),
(2, 'HDFC150', 'Flat Rs.150 OFF', 'Flat 150 off on orders Rs.600+', 'FLAT', 150.00, 150.00, 600.00, 'BANK SPECIAL', '2027-12-31', 'Active'),
(3, 'WELCOME100', 'First Order Rs.100 OFF', 'Rs.100 discount for orders Rs.300+', 'FLAT', 100.00, 100.00, 300.00, 'NEW USER', '2027-12-31', 'Active'),
(4, 'FREEDEL', 'Free Delivery on Rs.500+', 'Delivery fee waived for orders above Rs.500', 'DELIVERY', 0.00, 0.00, 500.00, 'FREE SHIP', '2027-12-31', 'Active');
