-- ============================================================
--  Food Dash - Online Food Ordering & Delivery Platform
--  Database schema + demo seed data (MySQL 5.7+ / MariaDB 10.3+)
--
--  Import with:  mysql -u root -p < database/food_dash.sql
--  (or phpMyAdmin -> Import)
--
--  Demo logins
--    Customer : demo@fooddash.test  /  Demo@123
--    Admin    : admin@fooddash.test /  Admin@123   (admin panel: /admin/)
-- ============================================================

DROP DATABASE IF EXISTS food_dash;
CREATE DATABASE food_dash CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE food_dash;

-- ---------- ADMIN (Module 6) ----------
CREATE TABLE admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(120)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- USERS (Module 1) ----------
CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)  NOT NULL,
    email           VARCHAR(120)  NOT NULL UNIQUE,
    phone           VARCHAR(15)   NOT NULL UNIQUE,
    password_hash   VARCHAR(255)  NOT NULL,
    avatar          VARCHAR(255)  NULL,                -- profile photo file name (uploads/avatars)
    status          ENUM('Active','Suspended') NOT NULL DEFAULT 'Active',
    notify_orders   TINYINT(1)    NOT NULL DEFAULT 1,
    notify_account  TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE addresses (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    label       ENUM('Home','Work','Other') NOT NULL DEFAULT 'Home',
    house       VARCHAR(150) NOT NULL,           -- Flat / House / Building
    locality    VARCHAR(150) NOT NULL,
    city        VARCHAR(80)  NOT NULL,
    state       VARCHAR(80)  NOT NULL,
    pincode     CHAR(6)      NOT NULL,
    is_default  TINYINT(1)   NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- "Remember me" persistent login tokens (cookie-based)
CREATE TABLE remember_tokens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT         NOT NULL,
    selector    VARCHAR(32) NOT NULL UNIQUE,
    token_hash  CHAR(64)    NOT NULL,
    expires_at  DATETIME    NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- CATEGORIES & RESTAURANTS & MENU (Module 2 / Module 6) ----------
CREATE TABLE categories (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE restaurants (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(120) NOT NULL,
    cuisine        VARCHAR(200) NOT NULL,
    address        VARCHAR(255) NOT NULL,
    phone          VARCHAR(20)  NOT NULL,
    hours          VARCHAR(60)  NOT NULL DEFAULT '11:00 AM - 11:00 PM',
    rating         DECIMAL(2,1) NOT NULL DEFAULT 4.0,
    delivery_time  VARCHAR(20)  NOT NULL DEFAULT '30-40 min',
    banner_url     VARCHAR(255) NULL,
    status         ENUM('Open','Closed','Pending') NOT NULL DEFAULT 'Open',
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE menu_items (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id     INT           NOT NULL,
    category_id       INT           NOT NULL,
    name              VARCHAR(120)  NOT NULL,
    description       VARCHAR(500)  NULL,
    price             DECIMAL(8,2)  NOT NULL,
    is_veg            TINYINT(1)    NOT NULL DEFAULT 1,
    is_vegan          TINYINT(1)    NOT NULL DEFAULT 0,
    spice_level       TINYINT       NOT NULL DEFAULT 0,   -- 0 = Mild, 1 = Medium, 2 = Spicy
    calories          INT           NULL,
    allergens         VARCHAR(200)  NOT NULL DEFAULT '',  -- comma separated, e.g. Gluten,Dairy
    image_url         VARCHAR(255)  NULL,
    rating            DECIMAL(2,1)  NOT NULL DEFAULT 4.0,
    is_chefs_special  TINYINT(1)    NOT NULL DEFAULT 0,
    in_stock          TINYINT(1)    NOT NULL DEFAULT 1,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id)   REFERENCES categories(id)
) ENGINE=InnoDB;

-- ---------- OFFERS (Module 5) ----------
CREATE TABLE coupons (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(20)   NOT NULL UNIQUE,
    title           VARCHAR(120)  NOT NULL,
    description     VARCHAR(255)  NULL,
    offer_category  ENUM('FLAT','BANK','FIRST','BOGO') NOT NULL DEFAULT 'FLAT',
    discount_type   ENUM('PERCENT','FLAT','DELIVERY')  NOT NULL,
    discount_value  DECIMAL(8,2)  NOT NULL DEFAULT 0,
    max_discount    DECIMAL(8,2)  NOT NULL DEFAULT 0,
    min_order       DECIMAL(8,2)  NOT NULL DEFAULT 0,
    badge           VARCHAR(30)   NULL,
    expiry_date     DATE          NOT NULL,
    status          ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB;

-- ---------- CART (Module 3) ----------
CREATE TABLE cart_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT          NOT NULL,
    menu_item_id  INT          NOT NULL,
    qty           INT          NOT NULL DEFAULT 1,
    instruction   VARCHAR(200) NOT NULL DEFAULT '',
    UNIQUE KEY uq_cart (user_id, menu_item_id),
    FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- FAVOURITES (Module 5) ----------
CREATE TABLE favourite_restaurants (
    user_id       INT NOT NULL,
    restaurant_id INT NOT NULL,
    saved_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, restaurant_id),
    FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE favourite_items (
    user_id       INT NOT NULL,
    menu_item_id  INT NOT NULL,
    saved_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, menu_item_id),
    FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- ORDERS (Module 3 / 4) ----------
CREATE TABLE orders (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    order_code       VARCHAR(20)   NOT NULL UNIQUE,         -- e.g. ORD-4821-X9
    user_id          INT           NOT NULL,
    restaurant_id    INT           NOT NULL,
    delivery_address VARCHAR(400)  NOT NULL,
    delivery_note    VARCHAR(255)  NOT NULL DEFAULT '',
    subtotal         DECIMAL(10,2) NOT NULL,
    packaging        DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax              DECIMAL(10,2) NOT NULL DEFAULT 0,
    delivery_fee     DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount         DECIMAL(10,2) NOT NULL DEFAULT 0,
    coupon_code      VARCHAR(20)   NULL,
    total            DECIMAL(10,2) NOT NULL,
    payment_method   ENUM('UPI','Card','NetBanking','COD') NOT NULL,
    payment_status   ENUM('Paid','Pending') NOT NULL,
    status           ENUM('Placed','Confirmed','Preparing','Out for Delivery','Delivered','Cancelled') NOT NULL DEFAULT 'Placed',
    rating           TINYINT       NULL,                    -- 1..5
    review           VARCHAR(500)  NULL,
    eta_at           DATETIME      NOT NULL,
    created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    order_id      INT           NOT NULL,
    menu_item_id  INT           NULL,                       -- kept even if the dish is later removed
    item_name     VARCHAR(120)  NOT NULL,
    unit_price    DECIMAL(8,2)  NOT NULL,
    qty           INT           NOT NULL,
    instruction   VARCHAR(200)  NOT NULL DEFAULT '',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_status_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT          NOT NULL,
    status      VARCHAR(30)  NOT NULL,
    note        VARCHAR(200) NOT NULL DEFAULT '',
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- SIMULATED PAYMENTS & ADMIN AUDIT (Module 6) ----------
CREATE TABLE payments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    txn_id      VARCHAR(20)   NOT NULL UNIQUE,
    order_id    INT           NOT NULL,
    method      ENUM('UPI','Card','NetBanking','COD') NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    status      ENUM('SUCCESS','PENDING','FAILED') NOT NULL,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE admin_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT NULL,
    message     VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
--  SEED DATA
-- =====================================================================

-- Admin: admin@fooddash.test / Admin@123
INSERT INTO admins (name, email, password_hash) VALUES
('Super Admin', 'admin@fooddash.test', '$2y$10$l6BhqmHqyQ90nUPbOLzQ6eodltPHc.bs0CrI5DeSjlzv6T5Aemwnq');

-- Customers (all use password: Demo@123)
INSERT INTO users (id, name, email, phone, password_hash, status) VALUES
(1, 'Demo Customer', 'demo@fooddash.test', '9876543210', '$2y$10$iHbM7TDXoicKOzu3v.IJ2ehXmx318fxi6FYcSJXDvcy3m/RMh2D0.', 'Active'),
(2, 'Sarah Smith',   'sarah@fooddash.test', '9876501234', '$2y$10$iHbM7TDXoicKOzu3v.IJ2ehXmx318fxi6FYcSJXDvcy3m/RMh2D0.', 'Active'),
(3, 'Alex Johnson',  'alex@fooddash.test',  '9123456780', '$2y$10$iHbM7TDXoicKOzu3v.IJ2ehXmx318fxi6FYcSJXDvcy3m/RMh2D0.', 'Suspended');

INSERT INTO addresses (user_id, label, house, locality, city, state, pincode, is_default) VALUES
(1, 'Home', 'Flat 301, Royal Heights', 'Near Car Street, Kunjibettu', 'Udupi', 'Karnataka', '576101', 1),
(1, 'Work', 'Office 5B, Tech Park',     'Manipal Road',                'Udupi', 'Karnataka', '576104', 0),
(2, 'Home', '12 Lotus Apartments',     'Kadri Hills',                 'Mangaluru', 'Karnataka', '575002', 1);

INSERT INTO categories (id, name) VALUES
(1, 'Starters'), (2, 'Main Course'), (3, 'Desserts'), (4, 'Beverages');

INSERT INTO restaurants (id, name, cuisine, address, phone, hours, rating, delivery_time, banner_url, status) VALUES
(1, 'The Gourmet Bistro',   'Italian, Pasta, Woodfired Pizza', '452 Car Street, Udupi',        '+91 820 252 1049', '11:00 AM - 11:00 PM', 4.8, '25-30 min', 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&auto=format&fit=crop&q=80', 'Open'),
(2, 'Spice Symphony',       'Indian, Curry, Tandoor',          '108 Ocean Drive, Malpe',       '+91 820 252 2011', '12:00 PM - 10:30 PM', 4.9, '30-40 min', 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&auto=format&fit=crop&q=80', 'Open'),
(3, 'Burger & Craft House', 'American, Burgers, Fries',           '12 Grill Road, Manipal',       '+91 820 252 3300', '11:30 AM - 11:00 PM', 4.6, '20-25 min', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80', 'Open'),
(4, 'Sakura Sushi Bar',     'Japanese, Sushi, Ramen',          '88 Eastern Boulevard, Udupi',  '+91 820 252 4412', '01:00 PM - 10:00 PM', 4.9, '30-40 min', 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=600&auto=format&fit=crop&q=80', 'Closed'),
(5, 'Taco Del Sol',         'Mexican, Tacos, Quesadillas',     '7 Market Lane, Kunjibettu',    '+91 820 252 5520', '11:00 AM - 10:30 PM', 4.5, '25-35 min', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&auto=format&fit=crop&q=80', 'Open');

-- Menu items (explicit IDs so the seeded order history can reference them)
INSERT INTO menu_items (id, restaurant_id, category_id, name, description, price, is_veg, is_vegan, spice_level, calories, allergens, image_url, rating, is_chefs_special, in_stock) VALUES
(1,  1, 2, 'Truffle Mushroom Fettuccine', 'Handcrafted fettuccine with wild mushrooms, black truffle oil and aged parmesan.', 450.00, 1, 0, 0, 680, 'Gluten,Dairy', 'https://images.unsplash.com/photo-1621996346565-e3d5d6281288?w=500&auto=format&fit=crop&q=80', 4.9, 1, 1),
(2,  1, 2, 'Woodfired Margherita Pizza', 'San Marzano tomato, fresh buffalo mozzarella and basil on a sourdough base.', 375.00, 1, 0, 0, 820, 'Gluten,Dairy', 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?w=500&auto=format&fit=crop&q=80', 4.7, 0, 1),
(3,  1, 3, 'Classic Tiramisu', 'Espresso-soaked ladyfingers layered with mascarpone and cocoa.', 225.00, 1, 0, 0, 410, 'Gluten,Dairy,Egg', 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=500&auto=format&fit=crop&q=80', 4.9, 0, 1),
(4,  1, 1, 'Garlic Bread Sticks', 'Golden sticks brushed with garlic butter and oregano.', 210.00, 1, 0, 0, 390, 'Gluten,Dairy', NULL, 4.4, 0, 1),
(5,  2, 2, 'Fiery Chicken Tikka Masala', 'Roasted chicken simmered in a rich, spiced tomato cream sauce with fenugreek.', 499.00, 0, 0, 2, 750, 'Dairy', 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=500&auto=format&fit=crop&q=80', 4.9, 1, 1),
(6,  2, 1, 'Crispy Samosa Platter', 'Flaky pastry stuffed with spiced potatoes and peas, served with mint chutney.', 210.00, 1, 1, 1, 340, 'Gluten', 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=500&auto=format&fit=crop&q=80', 4.5, 0, 1),
(7,  2, 2, 'Paneer Butter Masala', 'Soft paneer cubes in a buttery, mildly spiced tomato gravy.', 360.00, 1, 0, 1, 560, 'Dairy', 'https://images.unsplash.com/photo-1631452180519-c014fe946bc7?w=500&auto=format&fit=crop&q=80', 4.9, 0, 1),
(8,  2, 4, 'Mango Lassi', 'Chilled yogurt blend with Alphonso mango pulp and cardamom.', 135.00, 1, 0, 0, 220, 'Dairy', 'https://images.unsplash.com/photo-1546173159-315724a31696?w=500&auto=format&fit=crop&q=80', 4.6, 0, 1),
(9,  2, 2, 'Garlic Naan (3 pcs)', 'Tandoor-baked naan brushed with garlic and coriander.', 120.00, 1, 0, 0, 310, 'Gluten,Dairy', NULL, 4.5, 0, 1),
(10, 3, 2, 'Double Bacon Smash Burger', 'Two smashed patties, crispy bacon, cheddar and house sauce.', 475.00, 0, 0, 0, 890, 'Gluten,Dairy,Egg', 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=500&auto=format&fit=crop&q=80', 4.7, 0, 1),
(11, 3, 1, 'Seasoned Potato Wedges', 'Crispy wedges with smoked paprika and rosemary salt.', 165.00, 1, 1, 1, 320, '', NULL, 4.3, 0, 1),
(12, 3, 4, 'Classic Cola (330 ml)', 'Chilled classic cola.', 90.00, 1, 1, 0, 150, '', NULL, 4.2, 0, 1),
(13, 4, 1, 'Dragon Roll (8 pcs)', 'Tempura prawn roll topped with avocado, spicy mayo and tobiko.', 480.00, 0, 0, 1, 450, 'Shellfish,Soy', 'https://images.unsplash.com/photo-1611143669185-af224c5e3252?w=500&auto=format&fit=crop&q=80', 4.8, 1, 1),
(14, 4, 2, 'Spicy Tonkotsu Ramen', 'Rich pork broth, soft-boiled egg, chashu pork, chilli oil and scallions.', 520.00, 0, 0, 2, 890, 'Gluten,Soy,Egg', 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=500&auto=format&fit=crop&q=80', 4.7, 0, 1),
(15, 4, 2, 'Salmon Nigiri Set (6 pcs)', 'Fresh salmon over seasoned rice.', 540.00, 0, 0, 0, 380, 'Fish,Soy', NULL, 4.8, 0, 0),
(16, 4, 4, 'Hot Matcha Green Tea', 'Ceremonial-grade matcha whisked hot.', 150.00, 1, 1, 0, 30, '', NULL, 4.4, 0, 1),
(17, 5, 2, 'Carne Asada Tacos (3 pcs)', 'Grilled marinated beef, onions, coriander and salsa verde.', 380.00, 0, 0, 2, 620, 'Gluten,Dairy', NULL, 4.6, 0, 1),
(18, 5, 2, 'Veggie Quesadilla', 'Grilled tortilla with cheese, peppers and corn.', 330.00, 1, 0, 1, 580, 'Gluten,Dairy', NULL, 4.4, 0, 1),
(19, 5, 4, 'Horchata Cold Drink', 'Cinnamon rice drink served chilled.', 150.00, 1, 1, 0, 240, '', NULL, 4.5, 0, 1),
(20, 5, 3, 'Churros with Hazelnut Chocolate', 'Cinnamon sugar churros with warm hazelnut chocolate dip.', 210.00, 1, 0, 0, 520, 'Gluten,Dairy,Egg,Nuts', NULL, 4.8, 0, 1);

INSERT INTO coupons (code, title, description, offer_category, discount_type, discount_value, max_discount, min_order, badge, expiry_date, status) VALUES
('NEXUS50',    '50% OFF up to Rs.150',    'Get 50% off on orders above Rs.200. Valid at all restaurants.', 'FLAT',  'PERCENT',  50.00,  150.00, 200.00, 'POPULAR',     '2027-12-31', 'Active'),
('HDFC150',    'Flat Rs.150 OFF with HDFC', 'Valid on card and net banking payments for orders of Rs.600+.', 'BANK',  'FLAT',    150.00,  150.00, 600.00, 'BANK SPECIAL', '2027-12-31', 'Active'),
('WELCOME100', 'First Order Rs.100 OFF',  'Exclusive welcome discount for new users on orders above Rs.300.', 'FIRST', 'FLAT',    100.00,  100.00, 300.00, 'NEW USER',    '2027-12-31', 'Active'),
('FREEDEL',    'Free Delivery on Rs.500+', 'Delivery fee waived for orders above Rs.500.', 'FLAT',  'DELIVERY',   0.00,    0.00, 500.00, 'FREE SHIP',   '2027-12-31', 'Active'),
('BOGOBURGER', 'Burger Fest 25% OFF',    '25% off on burger combos, up to Rs.100.', 'BOGO',  'PERCENT',  25.00,  100.00, 300.00, 'BOGO',        '2027-12-31', 'Active');

INSERT INTO favourite_restaurants (user_id, restaurant_id) VALUES (1, 1), (1, 3), (1, 4);
INSERT INTO favourite_items (user_id, menu_item_id) VALUES (1, 1), (1, 10);

-- Cart for demo user
INSERT INTO cart_items (user_id, menu_item_id, qty, instruction) VALUES
(1, 1, 1, 'Extra crispy base'),   -- ignored by the cart logic if restaurant differs; kept for demo
(1, 3, 2, 'Less sugar');

-- Order history for demo user
-- ORD-2001: delivered (Burger & Craft House)
INSERT INTO orders (id, order_code, user_id, restaurant_id, delivery_address, delivery_note, subtotal, packaging, tax, delivery_fee, discount, coupon_code, total, payment_method, payment_status, status, rating, review, eta_at, created_at) VALUES
(1, 'ORD-2001-B7', 1, 3, 'Flat 301, Royal Heights, Near Car Street, Kunjibettu, Udupi, Karnataka - 576101', 'Leave at door', 1115.00, 25.00, 55.75, 40.00, 0.00, NULL, 1235.75, 'UPI', 'Paid', 'Delivered', 4, 'Super fresh burgers! Fast delivery.', NOW() - INTERVAL 2 DAY + INTERVAL 30 MINUTE, NOW() - INTERVAL 2 DAY);
INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, qty, instruction) VALUES
(1, 10, 'Double Bacon Smash Burger', 475.00, 2, 'Extra cheese'),
(1, 11, 'Seasoned Potato Wedges', 165.00, 1, '');
INSERT INTO order_status_log (order_id, status, note, created_at) VALUES
(1, 'Placed', 'Order received', NOW() - INTERVAL 2 DAY),
(1, 'Confirmed', 'Restaurant confirmed', NOW() - INTERVAL 2 DAY + INTERVAL 2 MINUTE),
(1, 'Preparing', 'Chef cooking', NOW() - INTERVAL 2 DAY + INTERVAL 8 MINUTE),
(1, 'Out for Delivery', 'Rider assigned', NOW() - INTERVAL 2 DAY + INTERVAL 18 MINUTE),
(1, 'Delivered', 'Delivered at door', NOW() - INTERVAL 2 DAY + INTERVAL 31 MINUTE);
INSERT INTO payments (txn_id, order_id, method, amount, status) VALUES ('TXN-88120001', 1, 'UPI', 1235.75, 'SUCCESS');

-- ORD-2002: delivered (Sakura Sushi Bar), NEXUS50 applied
INSERT INTO orders (id, order_code, user_id, restaurant_id, delivery_address, delivery_note, subtotal, packaging, tax, delivery_fee, discount, coupon_code, total, payment_method, payment_status, status, rating, review, eta_at, created_at) VALUES
(2, 'ORD-2002-K9', 1, 4, 'Flat 301, Royal Heights, Near Car Street, Kunjibettu, Udupi, Karnataka - 576101', 'Ring the doorbell', 1000.00, 25.00, 42.50, 40.00, 150.00, 'NEXUS50', 957.50, 'Card', 'Paid', 'Delivered', 5, 'Amazing fresh sashimi platter.', NOW() - INTERVAL 10 DAY + INTERVAL 30 MINUTE, NOW() - INTERVAL 10 DAY);
INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, qty, instruction) VALUES
(2, 13, 'Dragon Roll (8 pcs)', 480.00, 1, ''),
(2, 14, 'Spicy Tonkotsu Ramen', 520.00, 1, 'Less spicy');
INSERT INTO order_status_log (order_id, status, note, created_at) VALUES
(2, 'Placed', 'Order received', NOW() - INTERVAL 10 DAY),
(2, 'Delivered', 'Delivered at door', NOW() - INTERVAL 10 DAY + INTERVAL 33 MINUTE);
INSERT INTO payments (txn_id, order_id, method, amount, status) VALUES ('TXN-88120002', 2, 'Card', 957.50, 'SUCCESS');

-- ORD-2003: cancelled (Taco Del Sol)
INSERT INTO orders (id, order_code, user_id, restaurant_id, delivery_address, delivery_note, subtotal, packaging, tax, delivery_fee, discount, coupon_code, total, payment_method, payment_status, status, rating, review, eta_at, created_at) VALUES
(3, 'ORD-2003-M3', 1, 5, 'Flat 301, Royal Heights, Near Car Street, Kunjibettu, Udupi, Karnataka - 576101', '', 680.00, 25.00, 34.00, 40.00, 0.00, NULL, 779.00, 'COD', 'Pending', 'Cancelled', NULL, NULL, NOW() - INTERVAL 5 DAY + INTERVAL 30 MINUTE, NOW() - INTERVAL 5 DAY);
INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, qty, instruction) VALUES
(3, 17, 'Carne Asada Tacos (3 pcs)', 380.00, 1, ''),
(3, 19, 'Horchata Cold Drink', 150.00, 2, '');
INSERT INTO order_status_log (order_id, status, note, created_at) VALUES
(3, 'Placed', 'Order received', NOW() - INTERVAL 5 DAY),
(3, 'Cancelled', 'Cancelled by customer', NOW() - INTERVAL 5 DAY + INTERVAL 4 MINUTE);

-- ORD-2004: ACTIVE order, currently out for delivery (live tracking demo)
INSERT INTO orders (id, order_code, user_id, restaurant_id, delivery_address, delivery_note, subtotal, packaging, tax, delivery_fee, discount, coupon_code, total, payment_method, payment_status, status, rating, review, eta_at, created_at) VALUES
(4, 'ORD-2004-A2', 1, 1, 'Flat 301, Royal Heights, Near Car Street, Kunjibettu, Udupi, Karnataka - 576101', 'Call when outside', 660.00, 25.00, 33.00, 40.00, 0.00, NULL, 758.00, 'UPI', 'Paid', 'Out for Delivery', NULL, NULL, NOW() + INTERVAL 15 MINUTE, NOW() - INTERVAL 20 MINUTE);
INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, qty, instruction) VALUES
(4, 1, 'Truffle Mushroom Fettuccine', 450.00, 1, 'Extra crispy base'),
(4, 4, 'Garlic Bread Sticks', 210.00, 1, '');
INSERT INTO order_status_log (order_id, status, note, created_at) VALUES
(4, 'Placed', 'Order received', NOW() - INTERVAL 20 MINUTE),
(4, 'Confirmed', 'Restaurant confirmed', NOW() - INTERVAL 18 MINUTE),
(4, 'Preparing', 'Chef cooking', NOW() - INTERVAL 14 MINUTE),
(4, 'Out for Delivery', 'Rider assigned', NOW() - INTERVAL 5 MINUTE);
INSERT INTO payments (txn_id, order_id, method, amount, status) VALUES ('TXN-88120004', 4, 'UPI', 758.00, 'SUCCESS');

INSERT INTO admin_logs (admin_id, message, created_at) VALUES
(1, 'System initialised with demo data', NOW() - INTERVAL 3 DAY),
(1, 'Coupon NEXUS50 published', NOW() - INTERVAL 2 DAY),
(1, 'Customer Alex Johnson marked Suspended', NOW() - INTERVAL 1 DAY);
