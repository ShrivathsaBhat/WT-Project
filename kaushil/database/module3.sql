-- Food Dash – Module 3: Cart & Order Placement Database Schema
-- Compatible with team schema (users, restaurants, menu_items, addresses, coupons)
USE food_dash;

CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    instruction VARCHAR(200) NOT NULL DEFAULT '',
    UNIQUE KEY uq_cart (user_id, menu_item_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    delivery_address VARCHAR(400) NOT NULL,
    delivery_note VARCHAR(255) NOT NULL DEFAULT '',
    subtotal DECIMAL(10,2) NOT NULL,
    packaging DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    coupon_code VARCHAR(20) DEFAULT NULL,
    total DECIMAL(10,2) NOT NULL,
    payment_method ENUM('UPI','Card','NetBanking','COD') NOT NULL,
    payment_status ENUM('Paid','Pending') NOT NULL DEFAULT 'Pending',
    status ENUM('Placed','Confirmed','Preparing','Out for Delivery','Delivered','Cancelled') NOT NULL DEFAULT 'Placed',
    rating TINYINT(4) DEFAULT NULL,
    review VARCHAR(500) DEFAULT NULL,
    eta_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT DEFAULT NULL,
    item_name VARCHAR(120) NOT NULL,
    unit_price DECIMAL(8,2) NOT NULL,
    qty INT NOT NULL,
    instruction VARCHAR(200) NOT NULL DEFAULT '',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_status_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(30) NOT NULL,
    note VARCHAR(200) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    txn_id VARCHAR(20) NOT NULL UNIQUE,
    order_id INT NOT NULL,
    method ENUM('UPI','Card','NetBanking','COD') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('SUCCESS','PENDING','FAILED') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
