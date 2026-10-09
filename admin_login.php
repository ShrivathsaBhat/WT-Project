<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Dash - Admin Login</title>

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
            background: black;
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
            color: #18aaf3;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 8px;
            margin-left: 10px;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 8px;
            margin-top: 10px;
            text-align: center;
        }

        .description {
            color: #8490a5;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 28px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            color: #aeb8c9;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            background: #050c19;
            color: #ffffff;
            border: 1px solid #1d2b43;
            border-radius: 7px;
            outline: none;
            font-size: 13px;
        }

        .form-group input:focus {
            border-color: #079be8;
        }

        .password-box {
            position: relative;
        }

        .password-box input {
            padding-right: 60px;
        }

        .show-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #2db6f7;
            font-size: 11px;
            cursor: pointer;
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0 22px;
            font-size: 11px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #8995a9;
        }

        .remember input {
            accent-color: #079be8;
        }

        .forgot {
            color: #2db6f7;
            text-decoration: none;
        }

        .forgot:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 7px;
            background: linear-gradient(90deg, #079fe9, #087fc9);
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.1);
        }

        .admin-note {
            text-align: center;
            margin-top: 22px;
            color: #59677d;
            font-size: 10px;
        }

        @media (max-width: 500px) {
            .login-card {
                padding: 28px 22px;
            }

            h1 {
                font-size: 24px;
            }
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

            <h1>Admin Login</h1>

            <p class="description">
                Sign in to access the Food Dash administration panel.
            </p>

            <form method="POST" action="admin_dashboard.php">

                <div class="form-group">
                    <label for="email">Email</label>

                    <input
                        type="email"
                        id="email"
                        placeholder="Enter admin email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="password-box">

                        <input
                            type="password"
                            id="password"
                            placeholder="Enter password"
                            required
                        >

                        <button
                            type="button"
                            class="show-password"
                            onclick="togglePassword()"
                        >
                            Show
                        </button>

                    </div>
                </div>

                
                <button type="submit" class="login-btn" name="btn">
                    Sign In to Admin Panel
                </button>

            </form>

            <div class="admin-note">
                Authorized administrators only
            </div>

        </div>

    </div>

    
</body>
</html>
