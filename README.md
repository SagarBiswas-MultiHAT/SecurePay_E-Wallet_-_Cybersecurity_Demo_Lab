# SecurePay E-Wallet & Cybersecurity Demo Lab

## Project Overview

SecurePay is a full-stack PHP web app simulating a secure E-Wallet system and a Cybersecurity Attack Demo Lab. The project is organized into modules: authentication, wallet (funds, transfer, history, dashboard), `/cyberlab/` for security demos, and `/admin/` for admin panel features. Uses PHP (with MySQL), HTML, CSS, and minimal JavaScript. **No frameworks.**

## Features

- User registration, login, logout (secure, session-based; password hashing)
- E-wallet: add funds, send money, transaction history (prepared statements, server-side validation)
- Dashboard: balance, recent transactions, frequent recipients
- Cybersecurity Lab: XSS, SQLi, CSRF demos (secure/vulnerable toggle via `secure_toggle.php`)
- Admin Panel: dashboard, user management, transaction logs (prepared statements, column existence checks)
- Minimalist, responsive UI (HTML/CSS, fintech-inspired)
- MySQL database (users, wallets, transactions)

## Setup

1. Import `users_table.sql` and `wallets_transactions_tables.sql` into MySQL.
2. Update DB credentials in `includes/db_connect.php`.
3. Run with PHP built-in server:
   ```
   php -S localhost:8000
   ```
   Or use XAMPP at `http://localhost/Web_Tech_Project/`

## Usage

- Main app: `http://localhost/Web_Tech_Project/`
- Admin panel: `http://localhost/Web_Tech_Project/admin/index.php` (admin login required)
- User management: `http://localhost/Web_Tech_Project/admin/users.php`
- Transaction logs: `http://localhost/Web_Tech_Project/admin/transactions.php`
- Cyber Lab: `http://localhost/Web_Tech_Project/cyberlab/` (toggle secure/vulnerable mode)

## Admin Panel

- `/admin/index.php`: Dashboard with user, wallet, and transaction stats
- `/admin/users.php`: Manage users (reset password, block/unblock, delete)
- `/admin/transactions.php`: View/filter all transactions (paginated, filterable)
- All admin features use prepared statements and check for column existence to avoid errors

## Cybersecurity Lab

- `/cyberlab/xss_demo.php`, `/cyberlab/sqli_demo.php`, `/cyberlab/csrf_demo.php`: Each demo toggles between vulnerable and secure modes using `secure_toggle.php` and `isSecureMode()`.
- Demos are self-contained, with clear UI and educational comments.

## Troubleshooting

- "Not Found" errors: Check your URL and project folder location.
- "Unknown column ... in field list" errors: Update your table or code to match column names.
- Undefined array key warnings: The code will show "-" for missing fields.

## Conventions & Security

- All user input is sanitized server-side (see `includes/functions.php`)
- All DB operations use prepared statements
- Use `$_SESSION['user_id']` for user context
- Success/error messages shown in styled alert boxes (`.alert.success`, `.alert.error`)
- All forms use POST and basic HTML validation; some have additional JS validation (`assets/js/validate.js`)
- Minimalist, fintech-inspired UI (see `assets/css/style.css`)
- All protected pages call `require_login()` and implement session timeout (5 min inactivity)
- `require_login()` ensures correct login redirect for both user and admin
- Transaction sign formatting, column alignment, card background color, CSS variable usage, image/logo display, and error handling are implemented for UI consistency
- All resource paths (images, CSS, JS) are checked and corrected for each module
- All code is commented for beginners; educational notes in Cyber Lab demos and admin panel

## Developer Notes

- To add a new secure/vulnerable demo, follow the pattern in `cyberlab/xss_demo.php` and use `isSecureMode()`.
- For new wallet actions, use prepared statements and update both `wallets` and `transactions` tables.
- For new admin features, check for column existence before querying, and handle missing columns gracefully in the UI.

## License

MIT

For more details, see the code in each module directory. All features are implemented with security best practices and clear comments for learning.
