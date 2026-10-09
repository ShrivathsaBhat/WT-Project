<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Dash - Customer Login</title>

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

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: #0b1428;
            border: 1px solid #1d2b43;
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            width: 40px;
            height: 40px;

            object-fit: contain;

            border-radius: 12px;

            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.4));
        }

        .brand {
            color: #20aaf0;
            font-size: 15px;
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
            margin-bottom: 26px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 18px;
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
            padding: 12px;
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

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 4px 0 22px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #7e8ba0;
            font-size: 10px;
        }

        .remember input {
            width: auto;
            accent-color: #079be8;
        }

        .forgot {
            color: #2db6f7;
            font-size: 10px;
            text-decoration: none;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 7px;
            background: linear-gradient(90deg, #079fe9, #087fc9);
            color: #ffffff;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #718097;
            font-size: 10px;
        }

        .register-link a {
            color: #2db6f7;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="login-container">

    <div class="login-card">

        <div class="logo">

            <!-- ACTUAL LOGO IMAGE -->
            <img src="https://images.openai.com/static-rsc-4/dJheY5XWr5aUtZLgvRiApKBZKJjnj8j7BhcQtJcwHvQ8XKFLksWUuALTv5HrSb5ADDHVBEVnhjqJwWbZnJVVLvE60F4giSoLiDel10wEpasphUU-vV1kK6erxpmBL9RRYY16QAhS7uiQ1BCtlzw55yYzPVxV6DRxN2cAnYdjBElq8Qz5IhTkzivYA0znBKt8?purpose=inline" alt="Food Dash Logo">

            <div class="brand">FOOD DASH</div>
       </div>

        <h1>Customer Login</h1>

        <p class="description">
            Login to your Food Dash customer account.
        </p>

        <form method="post" action="cust_dash.php">

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

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    placeholder="Enter password"
                    required
                >
            </div>

            <div class="login-options">


                <a href="#" class="forgot">
                    Forgot password?
                </a>

            </div>

            <button type="submit" class="login-btn">
                Login
            </button>

        </form>

        <div class="register-link">
            Don't have an account?
            <a href="cust_reg.php">Create Account</a>
        </div>

    </div>

</div>

</body>
</html>