<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Dash - Admin Dashboard</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #020817;
            color: #ffffff;
            min-height: 100vh;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* ================= SIDEBAR ================= */

        .sidebar {
            width: 235px;
            min-height: 100vh;
            background: #0a1428;
            border-right: 1px solid #1b2a43;
            padding: 18px 12px;
            display: flex;
            flex-direction: column;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            width: 50px;
            height: 50px;

            object-fit: contain;

            border-radius: 12px;

            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.4));
        }
        .brand{
        color: #20aaf0;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 7px;
            margin-left: 10px;
        }       

        .logo-text span {
            display: block;
            color: #20aaf0;
            font-size: 8px;
            letter-spacing: 1px;
            margin-top: 3px;
        }

        .menu-title {
            color: #56657c;
            font-size: 9px;
            font-weight: bold;
            margin: 10px 10px 8px;
            text-transform: uppercase;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nav-item {
            text-decoration: none;
            color: #8995a9;
            padding: 11px 12px;
            border-radius: 7px;
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-item:hover {
            background: #111f36;
            color: #ffffff;
        }

        .nav-item.active {
            background: linear-gradient(90deg, #078fd1, #0878bd);
            color: #ffffff;
        }

        .nav-icon {
            width: 17px;
            text-align: center;
            font-size: 13px;
        }

        .admin-profile {
            margin-top: auto;
            padding: 14px 5px 3px;
            border-top: 1px solid #17263d;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .profile-image {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #17324f;
            color: #43baf6;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 9px;
            font-weight: bold;
        }

        .profile-info {
            flex: 1;
        }

        .profile-info strong {
            display: block;
            font-size: 9px;
        }

        .profile-info span {
            color: #65748a;
            font-size: 7px;
        }

        .logout {
            color: #7b899e;
            font-size: 14px;
            cursor: pointer;
        }

        /* ================= MAIN ================= */

        .main {
            flex: 1;
            min-width: 0;
        }

        .topbar {
            height: 62px;
            background: #0a1428;
            border-bottom: 1px solid #17263d;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 22px;
        }

        .page-heading h1 {
            font-size: 17px;
            margin-bottom: 4px;
        }

        .page-heading p {
            color: #6f7d93;
            font-size: 9px;
        }

        .top-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search {
            width: 210px;
            height: 32px;
            background: #050d1b;
            border: 1px solid #1b2a42;
            border-radius: 6px;
            padding: 0 11px;
            color: #ffffff;
            outline: none;
            font-size: 10px;
        }

        .notification {
            width: 32px;
            height: 32px;
            border: 1px solid #1b2a42;
            background: #071022;
            color: #a6b3c6;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ================= CONTENT ================= */

        .content {
            padding: 20px;
        }

        .welcome {
            margin-bottom: 18px;
        }

        .welcome h2 {
            font-size: 15px;
            margin-bottom: 5px;
        }

        .welcome p {
            color: #718097;
            font-size: 9px;
        }

        /* ================= STAT CARDS ================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .stat-card {
            background: linear-gradient(145deg, #0d1830, #091326);
            border: 1px solid #192840;
            border-radius: 8px;
            padding: 15px;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-title {
            color: #8290a6;
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .stat-icon {
            width: 27px;
            height: 27px;
            border-radius: 6px;
            background: #082d47;
            color: #31b7f5;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
        }

        .stat-number {
            font-size: 21px;
            font-weight: bold;
            margin-top: 11px;
        }

        .stat-change {
            color: #00d99c;
            font-size: 8px;
            margin-top: 5px;
        }

        .stat-change.red {
            color: #ff5e79;
        }

        /* ================= PANELS ================= */

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        .panel {
            background: linear-gradient(145deg, #0d1830, #091326);
            border: 1px solid #192840;
            border-radius: 8px;
            padding: 15px;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 13px;
        }

        .panel-header h3 {
            font-size: 10px;
        }

        .view-all {
            border: none;
            background: transparent;
            color: #2bb7f7;
            font-size: 8px;
            cursor: pointer;
        }

        /* ================= ORDERS ================= */

        .order-table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-table th {
            color: #65748a;
            font-size: 7px;
            text-align: left;
            padding: 8px;
            background: #050c19;
            border-bottom: 1px solid #17253d;
        }

        .order-table td {
            padding: 9px 8px;
            font-size: 8px;
            color: #b5c0d0;
            border-bottom: 1px solid #142139;
        }

        .order-id {
            color: #ffffff;
            font-weight: bold;
        }

        .status {
            display: inline-block;
            padding: 4px 7px;
            border-radius: 4px;
            font-size: 7px;
        }

        .status.success {
            background: #063c30;
            color: #00dba0;
        }

        .status.pending {
            background: #40350a;
            color: #ffd24c;
        }

        .status.delivery {
            background: #073654;
            color: #42bdf7;
        }

        /* ================= ACTIVITY ================= */

        .activity {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .activity-item {
            display: flex;
            gap: 9px;
            padding-bottom: 9px;
            border-bottom: 1px solid #15233a;
        }

        .activity-icon {
            width: 25px;
            height: 25px;
            border-radius: 5px;
            background: #082e47;
            color: #36b9f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        .activity-text {
            flex: 1;
        }

        .activity-text strong {
            display: block;
            font-size: 8px;
            margin-bottom: 3px;
        }

        .activity-text span {
            color: #69788e;
            font-size: 7px;
        }

        /* ================= QUICK ACTIONS ================= */

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .quick-action {
            background: #071022;
            border: 1px solid #17263e;
            border-radius: 7px;
            padding: 13px;
            color: #9ba8bb;
            text-decoration: none;
        }

        .quick-action:hover {
            border-color: #078fd1;
            background: #0b1a30;
        }

        .quick-icon {
            width: 29px;
            height: 29px;
            background: #082e48;
            color: #35baf6;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 9px;
        }

        .quick-action strong {
            display: block;
            color: #ffffff;
            font-size: 9px;
            margin-bottom: 4px;
        }

        .quick-action span {
            font-size: 7px;
            color: #68778d;
        }

        /* ================= RESPONSIVE ================= */

        @media (max-width: 1000px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                width: 70px;
                padding: 15px 8px;
            }

            .logo-text,
            .menu-title,
            .nav-item span,
            .profile-info,
            .logout {
                display: none;
            }

            .logo-section {
                justify-content: center;
            }

            .nav-item {
                justify-content: center;
                padding: 12px 5px;
            }

            .admin-profile {
                justify-content: center;
            }

            .topbar {
                padding: 0 12px;
            }

            .search {
                width: 130px;
            }

            .content {
                padding: 12px;
            }
        }

        @media (max-width: 500px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .top-right .search {
                display: none;
            }

            .order-table {
                min-width: 550px;
            }

            .panel {
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>

<div class="dashboard">
    <form method="POST" action="cust_login.php">
    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">

            <!-- ACTUAL LOGO IMAGE -->
            <img src="https://images.openai.com/static-rsc-4/dJheY5XWr5aUtZLgvRiApKBZKJjnj8j7BhcQtJcwHvQ8XKFLksWUuALTv5HrSb5ADDHVBEVnhjqJwWbZnJVVLvE60F4giSoLiDel10wEpasphUU-vV1kK6erxpmBL9RRYY16QAhS7uiQ1BCtlzw55yYzPVxV6DRxN2cAnYdjBElq8Qz5IhTkzivYA0znBKt8?purpose=inline" alt="Food Dash Logo">

            <div class="brand">FOOD DASH</div>
       </div>
        

        <div class="menu-title">Main Menu</div>

        <nav class="nav">

            <a href="#" class="nav-item active">
                <div class="nav-icon">◈</div>
                <span>Dashboard</span>
            </a>

            <a href="cust_reg.php" class="nav-item">
                <div class="nav-icon">♙</div>
                <span>Customers</span>
            

            <a href="#" class="nav-item">
                <div class="nav-icon">▦</div>
                <span>Restaurants</span>
            </a>

            <a href="#" class="nav-item">
                <div class="nav-icon">☷</div>
                <span>Menu Management</span>
            </a>

            <a href="#" class="nav-item">
                <div class="nav-icon">◇</div>
                <span>Offers & Pricing</span>
            </a>

            <a href="#" class="nav-item">
                <div class="nav-icon">◫</div>
                <span>Orders</span>
            </a>

            <a href="#" class="nav-item">
                <div class="nav-icon">▤</div>
                <span>Audit Logs</span>
            </a>

        </nav>

        <div class="admin-profile">

            <div class="profile-image">
                AD
            </div>

            <div class="profile-info">
                <strong>System Admin</strong>
                <span>admin@fooddash.com</span>
            </div>

            <div class="logout">
                ↪
            </div>

        </div>

    </aside>
    </form>

    <!-- MAIN CONTENT -->
    <main class="main">

        <!-- TOP BAR -->
        <header class="topbar">

            <div class="page-heading">
                <h1>Admin Dashboard</h1>
                <p>Food Dash platform management overview</p>
            </div>

            <div class="top-right">

                <input
                    type="text"
                    class="search"
                    placeholder="Search dashboard..."
                >

                <button class="notification">
                    ♢
                </button>

            </div>

        </header>


        <!-- CONTENT -->
        <section class="content">

            <div class="welcome">
                <h2>Welcome back, Admin</h2>
                <p>Here's what's happening across your Food Dash platform today.</p>
            </div>


            
            <!-- ORDERS + ACTIVITY -->
            <div class="dashboard-grid">

                <div class="panel">

                    <div class="panel-header">
                        <h3>Recent Orders</h3>
                        <button class="view-all">View All Orders →</button>
                    </div>

                    <table class="order-table">

                        <thead>
                            <tr>
                                <th>ORDER ID</th>
                                <th>CUSTOMER</th>
                                <th>RESTAURANT</th>
                                <th>AMOUNT</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>

                       
                    </table>

                </div>


                
            <!-- QUICK ACTIONS -->
            <div class="panel">

                <div class="panel-header">
                    <h3>Quick Management</h3>
                </div>

                <div class="quick-actions">

                    <a href="#" class="quick-action">

                        <div class="quick-icon">♙</div>

                        <strong>Manage Customers</strong>

                        <span>View and manage customer accounts</span>

                    </a>


                    <a href="#" class="quick-action">

                        <div class="quick-icon">▦</div>

                        <strong>Manage Restaurants</strong>

                        <span>Review restaurant partners</span>

                    </a>


                    <a href="#" class="quick-action">

                        <div class="quick-icon">◇</div>

                        <strong>Create Offer</strong>

                        <span>Create promotional campaigns</span>

                    </a>


                    <a href="#" class="quick-action">

                        <div class="quick-icon">▤</div>

                        <strong>View Audit Logs</strong>

                        <span>Monitor platform activity</span>

                    </a>

                </div>

            </div>

        </section>

    </main>

</div>

</body>
</html>