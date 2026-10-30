<?php

require __DIR__ . '/config/database.php';

$message = '';
$error = '';

try {

    $pdo = db(false);

    // Create and select the database
    $pdo->exec(
        "CREATE DATABASE IF NOT EXISTS food_dash
         CHARACTER SET utf8mb4
         COLLATE utf8mb4_unicode_ci"
    );

    $pdo->exec("USE food_dash");

    // Database table creation statements
    $sql = [

        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS restaurants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            restaurant_id INT NULL,
            name VARCHAR(120) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            FOREIGN KEY (restaurant_id)
                REFERENCES restaurants(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_code VARCHAR(30) NOT NULL UNIQUE,
            user_id INT NOT NULL,
            order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status ENUM(
                'Placed',
                'Confirmed',
                'Preparing',
                'Out for Delivery',
                'Delivered',
                'Cancelled'
            ) NOT NULL DEFAULT 'Placed',
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            delivery_address VARCHAR(255) NOT NULL,
            estimated_delivery DATETIME NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id)
                REFERENCES users(id)
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            menu_item_id INT NULL,
            item_name VARCHAR(120) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id)
                REFERENCES orders(id)
                ON DELETE CASCADE,
            FOREIGN KEY (menu_item_id)
                REFERENCES menu_items(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            method ENUM('Cash on Delivery', 'Card', 'UPI') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM(
                'Pending',
                'Successful',
                'Failed',
                'Refunded'
            ) NOT NULL DEFAULT 'Pending',
            transaction_ref VARCHAR(60) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id)
                REFERENCES orders(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NULL,
            action VARCHAR(100) NOT NULL,
            details VARCHAR(500) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id)
                REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB"

    ];

    // Execute all table creation statements
    foreach ($sql as $statement) {
        $pdo->exec($statement);
    }

    // Add sample menu items only if the menu is empty
    $menuCount = (int) $pdo
        ->query("SELECT COUNT(*) FROM menu_items")
        ->fetchColumn();

    if ($menuCount === 0) {

        $pdo->exec(
            "INSERT INTO restaurants (name)
             VALUES
                ('Spice Garden'),
                ('Burger Bay')"
        );

        $pdo->exec(
            "INSERT INTO menu_items (restaurant_id, name, price)
             VALUES
                (1, 'Paneer Wrap', 160.00),
                (1, 'Veg Biryani', 220.00),
                (2, 'Classic Burger', 180.00)"
        );
    }

    $message = 'Database tables are ready. No demo accounts or sample orders were created. Register a customer account to begin.';

} catch (Throwable $e) {

    $error = $e->getMessage();
}

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Food Dash setup</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >
</head>

<body>

    <main class="container narrow">

        <section class="panel">

            <h1>Food Dash setup</h1>

            <?php if ($message): ?>

                <div class="notice success">
                    <?= htmlspecialchars($message) ?>
                </div>

                <p>
                    <a class="button" href="login.php">
                        Go to login
                    </a>
                </p>

                <p class="muted">
                    For safety, delete install.php after setup.
                </p>

            <?php else: ?>

                <div class="notice error">
                    Setup could not finish:
                    <?= htmlspecialchars($error) ?>
                </div>

                <p>
                    Check that MySQL is running and
                    config/database.php has the correct credentials.
                </p>

            <?php endif; ?>

        </section>

    </main>

</body>

</html>