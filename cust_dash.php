<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Food Dash - Customer Dashboard</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f5f7fb;
            color: #172033;
        }

        /* ================= HEADER ================= */

        header {
            height: 72px;
            background: #ffffff;
            border-bottom: 1px solid #e4e8ef;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 40px;

            position: fixed;
            top: 0;
            left: 0;
            right: 0;

            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .logo img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 10px;
        }

        .brand h2 {
            font-size: 21px;
            color: #172033;
        }

        .brand h2 span {
            color: #079be8;
        }

        .brand p {
            font-size: 10px;
            color: #8a94a6;
            margin-top: 2px;
        }

        /* ================= SEARCH ================= */

        .search-box {
            width: 380px;
            height: 40px;

            display: flex;
            align-items: center;

            background: #f4f6f9;
            border: 1px solid #e0e5ec;
            border-radius: 8px;

            padding: 0 14px;
        }

        .search-box span {
            font-size: 17px;
            margin-right: 8px;
            color: #7b8798;
        }

        .search-box input {
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            font-size: 14px;
        }

        /* ================= PROFILE ================= */

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;
            background: #f2f5f9;

            font-size: 18px;
        }

        .user-icon {
            width: 40px;
            height: 40px;

            border-radius: 50%;

            background: #079be8;
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: bold;
        }

        .user-info strong {
            display: block;
            font-size: 14px;
        }

        .user-info small {
            color: #8b95a5;
            font-size: 11px;
        }

        /* ================= SIDEBAR ================= */

        .sidebar {
            position: fixed;
            top: 72px;
            left: 0;
            bottom: 0;

            width: 225px;

            background: #ffffff;
            border-right: 1px solid #e4e8ef;

            padding: 25px 15px;
        }

        .sidebar-title {
            font-size: 11px;
            color: #9aa4b3;
            text-transform: uppercase;
            letter-spacing: 1px;

            padding: 0 12px;
            margin-bottom: 12px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;

            padding: 13px 14px;

            margin-bottom: 5px;

            border-radius: 8px;

            text-decoration: none;
            color: #667085;

            font-size: 14px;

            transition: 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #eaf7fd;
            color: #079be8;
            font-weight: 600;
        }

        .sidebar-icon {
            width: 22px;
            text-align: center;
        }

        /* ================= MAIN ================= */

        main {
            margin-left: 225px;
            padding: 105px 35px 40px;
        }

        .welcome {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 25px;
        }

        .welcome h1 {
            font-size: 27px;
            margin-bottom: 7px;
        }

        .welcome p {
            color: #7b8494;
            font-size: 14px;
        }

        .order-button {
            background: #079be8;
            color: white;

            padding: 12px 22px;

            border-radius: 7px;

            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        .order-button:hover {
            background: #087fc9;
        }

        /* ================= QUICK CARDS ================= */

        .quick-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;

            margin-bottom: 30px;
        }

        .quick-card {
            background: white;
            border: 1px solid #e4e8ef;
            border-radius: 10px;

            padding: 20px;

            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quick-icon {
            width: 48px;
            height: 48px;

            border-radius: 10px;

            background: #eaf7fd;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 21px;
        }

        .quick-card h3 {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .quick-card p {
            color: #8b95a5;
            font-size: 12px;
        }

        /* ================= SECTION ================= */

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 15px;
        }

        .section-header h2 {
            font-size: 19px;
        }

        .view-all {
            color: #079be8;
            font-size: 13px;
            text-decoration: none;
        }

        /* ================= RESTAURANTS ================= */

        .restaurants {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;

            margin-bottom: 35px;
        }

        .restaurant-card {
            background: white;

            border: 1px solid #e4e8ef;
            border-radius: 10px;

            overflow: hidden;

            transition: 0.25s;
        }

        .restaurant-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.07);
        }

        .restaurant-image {
            height: 150px;
            width: 100%;

            object-fit: cover;
        }

        .restaurant-info {
            padding: 15px;
        }

        .restaurant-info h3 {
            font-size: 16px;
            margin-bottom: 7px;
        }

        .restaurant-info p {
            font-size: 12px;
            color: #7d8797;
            margin-bottom: 10px;
        }

        .rating {
            color: #f59e0b;
            font-size: 13px;
        }

        .restaurant-info small {
            color: #7d8797;
            margin-left: 5px;
        }

        /* ================= BOTTOM SECTION ================= */

        .bottom-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
        }

        .panel {
            background: white;
            border: 1px solid #e4e8ef;
            border-radius: 10px;
            padding: 20px;
        }

        /* ================= ORDERS ================= */

        .order {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 14px 0;

            border-bottom: 1px solid #edf0f4;
        }

        .order:last-child {
            border-bottom: none;
        }

        .order-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .food-icon {
            width: 42px;
            height: 42px;

            border-radius: 8px;

            background: #f2f5f8;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20px;
        }

        .order-info strong {
            display: block;
            font-size: 13px;
        }

        .order-info small {
            color: #8a94a3;
            font-size: 11px;
        }

        .status {
            padding: 5px 9px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: bold;
        }

        .delivered {
            background: #e7f8f1;
            color: #0b9b68;
        }

        .preparing {
            background: #fff5df;
            color: #d88a00;
        }

        /* ================= OFFERS ================= */

        .offer {
            padding: 15px;

            border-radius: 8px;

            background: #f0f9fd;

            margin-bottom: 12px;
        }

        .offer:last-child {
            margin-bottom: 0;
        }

        .offer strong {
            display: block;
            color: #079be8;
            margin-bottom: 5px;
        }

        .offer p {
            color: #6f7a8a;
            font-size: 12px;
        }

        /* ================= RESPONSIVE ================= */

        @media (max-width: 1100px) {

            .quick-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .restaurants {
                grid-template-columns: repeat(2, 1fr);
            }

            .search-box {
                width: 280px;
            }
        }

        @media (max-width: 800px) {

            header {
                padding: 0 15px;
            }

            .search-box {
                display: none;
            }

            .sidebar {
                width: 70px;
                padding: 20px 8px;
            }

            .sidebar-title,
            .sidebar a span:last-child {
                display: none;
            }

            .sidebar a {
                justify-content: center;
            }

            main {
                margin-left: 70px;
                padding-left: 20px;
                padding-right: 20px;
            }

            .restaurants {
                grid-template-columns: 1fr;
            }

            .bottom-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 550px) {

            .quick-cards {
                grid-template-columns: 1fr;
            }

            .welcome {
                display: block;
            }

            .order-button {
                display: inline-block;
                margin-top: 15px;
            }

            .user-info {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- ================= HEADER ================= -->

    <header>

        <div class="logo">

            <img src="https://images.openai.com/static-rsc-4/dJheY5XWr5aUtZLgvRiApKBZKJjnj8j7BhcQtJcwHvQ8XKFLksWUuALTv5HrSb5ADDHVBEVnhjqJwWbZnJVVLvE60F4giSoLiDel10wEpasphUU-vV1kK6erxpmBL9RRYY16QAhS7uiQ1BCtlzw55yYzPVxV6DRxN2cAnYdjBElq8Qz5IhTkzivYA0znBKt8?purpose=inline" alt="Food Dash Logo">

            <div class="brand">
                <h2>Food <span>Dash</span></h2>
                <p>Your Food. Delivered Fast.</p>
            </div>

        </div>


        <div class="search-box">
            <span>⌕</span>
            <input type="text" placeholder="Search restaurants, dishes...">
        </div>


        <div class="profile">

            <div class="notification">
                🔔
            </div>

            <div class="user-icon">
                S
            </div>

            <div class="user-info">
                <strong>Customer</strong>
                <small>Customer ID: CUST001</small>
            </div>

        </div>

    </header>


    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <div class="sidebar-title">
            Customer Menu
        </div>

        <a href="#" class="active">
            <span class="sidebar-icon">⌂</span>
            <span>Dashboard</span>
        </a>

        <a href="#">
            <span class="sidebar-icon">🍽️</span>
            <span>Browse Restaurants</span>
        </a>

        <a href="#">
            <span class="sidebar-icon">📋</span>
            <span>My Orders</span>
        </a>

        <a href="#">
            <span class="sidebar-icon">🛒</span>
            <span>My Cart</span>
        </a>

        <a href="#">
            <span class="sidebar-icon">❤️</span>
            <span>Favourites</span>
        </a>

        <a href="#">
            <span class="sidebar-icon">🏷️</span>
            <span>Offers</span>
        </a>

        <a href="#">
            <span class="sidebar-icon">📍</span>
            <span>Track Order</span>
        </a>

        <a href="#">
            <span class="sidebar-icon">👤</span>
            <span>My Profile</span>
        </a>

    </aside>


    <!-- ================= MAIN CONTENT ================= -->

    <main>

        <!-- WELCOME -->

        <div class="welcome">

            <div>
                <h1>Welcome back, Customer! 👋</h1>
                <p>Find your favourite food and order something delicious.</p>
            </div>

            <a href="#" class="order-button">
                + Start New Order
            </a>

        </div>


        <!-- QUICK ACTIONS -->

        <div class="quick-cards">

            <div class="quick-card">
                <div class="quick-icon">🍽️</div>
                <div>
                    <h3>Browse Restaurants</h3>
                    <p>Find restaurants near you</p>
                </div>
            </div>

            <div class="quick-card">
                <div class="quick-icon">🛒</div>
                <div>
                    <h3>My Cart</h3>
                    <p>2 items in your cart</p>
                </div>
            </div>

            <div class="quick-card">
                <div class="quick-icon">📦</div>
                <div>
                    <h3>Active Order</h3>
                    <p>Order #FD1024</p>
                </div>
            </div>

            <div class="quick-card">
                <div class="quick-icon">❤️</div>
                <div>
                    <h3>Favourites</h3>
                    <p>8 saved restaurants</p>
                </div>
            </div>

        </div>


        <!-- RESTAURANTS -->

        <div class="section-header">

            <h2>Popular Restaurants</h2>

            <a href="#" class="view-all">
                View All →
            </a>

        </div>


        <div class="restaurants">

            <div class="restaurant-card">

                <img
                    class="restaurant-image"
                    src="https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800&q=80"
                    alt="Pizza">

                <div class="restaurant-info">

                    <h3>Pizza Paradise</h3>

                    <p>Pizza • Italian • Fast Food</p>

                    <span class="rating">★ 4.8</span>
                    <small>30-40 min</small>

                </div>

            </div>


            <div class="restaurant-card">

                <img
                    class="restaurant-image"
                    src="https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=800&q=80"
                    alt="Indian Food">

                <div class="restaurant-info">

                    <h3>Spice Garden</h3>

                    <p>Indian • North Indian • Thali</p>

                    <span class="rating">★ 4.7</span>
                    <small>25-35 min</small>

                </div>

            </div>


            <div class="restaurant-card">

                <img
                    class="restaurant-image"
                    src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=800&q=80"
                    alt="Burger">

                <div class="restaurant-info">

                    <h3>Burger House</h3>

                    <p>Burgers • Fast Food • Beverages</p>

                    <span class="rating">★ 4.6</span>
                    <small>20-30 min</small>

                </div>

            </div>

        </div>


        <!-- BOTTOM CONTENT -->

        <div class="bottom-grid">


            <!-- RECENT ORDERS -->

            <div class="panel">

                <div class="section-header">
                    <h2>Recent Orders</h2>
                    <a href="#" class="view-all">View All</a>
                </div>


                <div class="order">

                    <div class="order-left">

                        <div class="food-icon">
                            🍕
                        </div>

                        <div class="order-info">
                            <strong>Pizza Paradise</strong>
                            <small>Order #FD1024 • ₹549</small>
                        </div>

                    </div>

                    <span class="status preparing">
                        Preparing
                    </span>

                </div>


                <div class="order">

                    <div class="order-left">

                        <div class="food-icon">
                            🍔
                        </div>

                        <div class="order-info">
                            <strong>Burger House</strong>
                            <small>Order #FD1018 • ₹399</small>
                        </div>

                    </div>

                    <span class="status delivered">
                        Delivered
                    </span>

                </div>


                <div class="order">

                    <div class="order-left">

                        <div class="food-icon">
                            🍛
                        </div>

                        <div class="order-info">
                            <strong>Spice Garden</strong>
                            <small>Order #FD1009 • ₹620</small>
                        </div>

                    </div>

                    <span class="status delivered">
                        Delivered
                    </span>

                </div>

            </div>


            <!-- OFFERS -->

            <div class="panel">

                <div class="section-header">
                    <h2>Special Offers</h2>
                    <a href="#" class="view-all">View All</a>
                </div>


                <div class="offer">

                    <strong>20% OFF</strong>

                    <p>
                        Get 20% off on your next order.
                        Use code: <b>FOOD20</b>
                    </p>

                </div>


                <div class="offer">

                    <strong>FREE DELIVERY</strong>

                    <p>
                        Enjoy free delivery on orders above ₹499.
                    </p>

                </div>


                <div class="offer">

                    <strong>NEW CUSTOMER</strong>

                    <p>
                        Get ₹100 OFF on your first order.
                    </p>

                </div>

            </div>

        </div>

    </main>

</body>
</html>