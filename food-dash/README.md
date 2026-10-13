# Food Dash - Module 1: User Management

PHP + MySQL (PDO) + HTML/CSS/JavaScript + AJAX. Runs on XAMPP or WAMP.

## Setup
1. Copy the `food-dash` folder into `C:\xampp\htdocs\` (XAMPP) or `C:\wamp64\www\` (WAMP).
2. Start Apache and MySQL.
3. phpMyAdmin (http://localhost/phpmyadmin) -> SQL tab -> paste `sql/database.sql` -> Go
   (or Import -> choose `sql/database.sql`). It drops and recreates the `food_dash` database.
4. If your MySQL root user has a password, edit `config/db.php`.
5. Import `sql/restaurants.sql` the same way, with a UTF-8 client, for example
   `mysql --default-character-set=utf8mb4 -u root < sql/restaurants.sql`
   (adds restaurant and menu sample data; users are not touched)
6. Open http://localhost/food-dash/ (homepage). Restaurants: http://localhost/food-dash/restaurants.php

## Pages
| Page | Purpose |
|---|---|
| `register.php` | Create account: name, email, phone, password with live checklist, confirm, terms |
| `login.php` | Sign in with email or phone, show/hide password, remember me (30 days), lockout |
| `logout.php` | Sign out |
| `forgot_password.php`, `reset_password.php` | Verify email + phone, then set a new password (15-minute link) |
| `dashboard.php` | Account overview: greeting, stats, personal information |
| `profile.php` | Edit name, email, phone (duplicate check) |
| `addresses.php` | Add, edit, delete, set default delivery addresses |
| `security.php` | Change password; danger zone link to account deletion |
| `delete_account.php` | Delete account after password + typing DELETE |
| `notifications.php` | Email/SMS switches for order, account and promotion messages |
| `sessions.php` | Active devices (revoke one or all others) and remember-me cookie |
| `index.php` | Public homepage: area and search, categories, top restaurants, chef's specials, offers, footer. Home link and logo always go here |
| `restaurants.php` | Restaurant listing with search, area and category filters (Module 2) |
| `restaurant.php?id=N` | Restaurant details, address, hours and menu grouped by category (Module 2) |
| `menu.php`, `cart.php`, `orders.php`, `offers.php` | Header only (Modules 3 to 5, not built yet) |

## Validation (matches the module list)
- Name, email, Indian mobile number (starts 6-9), city/state (letters), 6-digit PIN (not starting 0)
- Password: 8-64 characters, uppercase and lowercase, a number and a special symbol
- Duplicate email/phone detection (live AJAX check on the form and server check on submit)
- Address fields: house and locality 3-100 characters
- Confirm-password match, terms agreement, confirmation prompts before sensitive actions
- Server-side validation on every form; client-side checks are for convenience only

## Files
| File | Purpose |
|---|---|
| `sql/database.sql` | Database and tables: users, addresses, password_resets, remember_tokens, user_sessions |
| `sql/restaurants.sql` | Sample restaurants and menu items (restaurants, menu_items) |
| `config/db.php` | PDO connection |
| `includes/functions.php` | Sessions, CSRF, validation rules, form helpers, login and remember-me |
| `includes/header.php`, `footer.php` | Shared layout, top bar, account sidebar |
| `assets/css/style.css`, `assets/js/main.js` | Styling and client-side validation |
| `ajax/check_duplicate.php` | JSON endpoint for live email/phone availability |

## Notes
- Passwords use `password_hash()`; every query uses prepared statements; output is escaped with `e()`; every POST form has a CSRF token.
- Signing out another device also turns off remember-me everywhere, because the remember-me cookie is not linked to a single device.
- The reset link is shown on screen because the project has no mail server (a documented limitation).
