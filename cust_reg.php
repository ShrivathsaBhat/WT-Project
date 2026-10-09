<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Dash - Customer Registration</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #020817;
            color: #ffffff;
        }

        .register-container {
            width: 100%;
            max-width: 430px;
            padding: 20px;
        }

        .register-card {
            background: #0b1428;
            border: 1px solid #1d2b43;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
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

        .brand {
            color: #20aaf0;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 7px;
            margin-left: 10px;
        }

        h1 {
            font-size: 26px;
            margin-bottom: 7px;
            margin-top: 10px;
            text-align: center;
        }

        .description {
            color: #8490a5;
            font-size: 12px;
            margin-bottom: 23px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            color: #aeb8c9;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input {
            width: 100%;
            padding: 11px;
            background: #050c19;
            border: 1px solid #1d2b43;
            border-radius: 7px;
            color: #ffffff;
            outline: none;
            font-size: 12px;
        }

        input:focus {
            border-color: #079be8;
        }

        .register-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 7px;
            background: linear-gradient(90deg, #079fe9, #087fc9);
            color: #ffffff;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            margin-top: 7px;
        }

        .login-link {
            text-align: center;
            margin-top: 18px;
            color: #718097;
            font-size: 10px;
        }

        .login-link a {
            color: #2db6f7;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="register-container">

    <div class="register-card">

       <div class="logo">

            <!-- ACTUAL LOGO IMAGE -->
            <img src="https://images.openai.com/static-rsc-4/dJheY5XWr5aUtZLgvRiApKBZKJjnj8j7BhcQtJcwHvQ8XKFLksWUuALTv5HrSb5ADDHVBEVnhjqJwWbZnJVVLvE60F4giSoLiDel10wEpasphUU-vV1kK6erxpmBL9RRYY16QAhS7uiQ1BCtlzw55yYzPVxV6DRxN2cAnYdjBElq8Qz5IhTkzivYA0znBKt8?purpose=inline" alt="Food Dash Logo">

            <div class="brand">FOOD DASH</div>
       </div>

        <h1>Create Account</h1>

        <p class="description">
            Register as a customer to order your favourite food.
        </p>

        <form method="post" action="cust_login.php">

            <!-- Customer ID -->
            <div class="form-group">
                <label for="customerId">Customer ID</label>

                <input
                    type="text"
                    id="customerId"
                    placeholder="Enter customer ID"
                    required
                >
            </div>

            <!-- Customer Name -->
            <div class="form-group">
                <label for="customerName">Customer Name</label>

                <input
                    type="text"
                    id="customerName"
                    placeholder="Enter full name"
                    required
                >
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    placeholder="Enter email address"
                    required
                >
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label for="phone">Phone Number</label>

                <input
                    type="tel"
                    id="phone"
                    placeholder="Enter phone number"
                    required
                >
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    placeholder="Create password"
                    required
                >
            </div>

            

            <button type="submit" class="register-btn" name="btn2">
                Create Account
            </button>

        </form>

        <div class="login-link">
            Already have an account?
            <a href="cust_login.php">Login</a>
        </div>

    </div>

</div>

</body>
</html>