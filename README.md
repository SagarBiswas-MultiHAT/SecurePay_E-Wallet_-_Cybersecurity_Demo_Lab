# SecurePay E-Wallet & Cybersecurity Demo Lab

## Photos

https://www.facebook.com/share/p/1LtAYUGgND/

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

### Prerequisites

- XAMPP (recommended) or PHP 7.4+ with MySQL
- Web browser (Chrome, Firefox, Safari, Edge)

### XAMPP Setup (Recommended)

1. **Download and Install XAMPP**

   - Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Install XAMPP on your system
   - Launch XAMPP Control Panel

2. **Start Services**

   - Start **Apache** server
   - Start **MySQL** database
   - Ensure both services show "Running" status

3. **Project Setup**

   - Copy the project folder to `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)
   - Rename the folder to `SecurePay` for easier access
   - Full path should be: `C:\xampp\htdocs\SecurePay\`

4. **Database Setup**

   - Open phpMyAdmin: `http://localhost/phpmyadmin/`
   - Create a new database named `securepay_db`
   - Import the SQL files in this order:
     - First: `users_table.sql`
     - Second: `wallets_transactions_tables.sql`
     - Optional: `make_sagar_admin.sql` (creates admin user)

5. **Configure Database Connection**

   - Edit `includes/db_connect.php`
   - Update database credentials:
     ```php
     $servername = "localhost";
     $username = "root";
     $password = "";  // Default XAMPP MySQL password is empty
     $dbname = "securepay_db";
     ```

6. **Access the Application**
   - Main app: `http://localhost/SecurePay/`
   - Admin panel: `http://localhost/SecurePay/admin/`
   - Cyber Lab: `http://localhost/SecurePay/cyberlab/`

### Alternative: PHP Built-in Server

If you prefer not to use XAMPP:

1. Ensure PHP 7.4+ and MySQL are installed
2. Update database credentials in `includes/db_connect.php`
3. Run from project directory:
   ```bash
   php -S localhost:8000
   ```
4. Access at: `http://localhost:8000/`

## Usage

### With XAMPP Setup

- Main app: `http://localhost/SecurePay/`
- Admin panel: `http://localhost/SecurePay/admin/index.php` (admin login required)
- User management: `http://localhost/SecurePay/admin/users.php`
- Transaction logs: `http://localhost/SecurePay/admin/transactions.php`
- Cyber Lab: `http://localhost/SecurePay/cyberlab/` (toggle secure/vulnerable mode)

### With PHP Built-in Server

- Main app: `http://localhost:8000/`
- Admin panel: `http://localhost:8000/admin/index.php`
- User management: `http://localhost:8000/admin/users.php`
- Transaction logs: `http://localhost:8000/admin/transactions.php`
- Cyber Lab: `http://localhost:8000/cyberlab/`

### Default Admin Credentials

If you imported `make_sagar_admin.sql`:

- **Username:** admin
- **Password:** admin123
- **Email:** admin@securepay.com

## Admin Panel

- `/admin/index.php`: Dashboard with user, wallet, and transaction stats
- `/admin/users.php`: Manage users (reset password, block/unblock, delete)
- `/admin/transactions.php`: View/filter all transactions (paginated, filterable)
- All admin features use prepared statements and check for column existence to avoid errors

## Cybersecurity Lab

- `/cyberlab/xss_demo.php`, `/cyberlab/sqli_demo.php`, `/cyberlab/csrf_demo.php`: Each demo toggles between vulnerable and secure modes using `secure_toggle.php` and `isSecureMode()`.
- Demos are self-contained, with clear UI and educational comments.

## Troubleshooting

### XAMPP Issues

- **Apache won't start**: Check if port 80 is occupied by another service (Skype, IIS). Change Apache port in XAMPP config or stop conflicting services.
- **MySQL won't start**: Port 3306 might be occupied. Check XAMPP control panel for error messages.
- **"Access forbidden" error**: Ensure project is in `htdocs` folder and folder permissions allow reading.
- **Database connection failed**: Verify MySQL is running and credentials in `includes/db_connect.php` are correct.

### General Issues

- **"Not Found" errors**: Check your URL and project folder location.
- **"Unknown column ... in field list" errors**: Update your table or code to match column names.
- **Undefined array key warnings**: The code will show "-" for missing fields.
- **Session issues**: Clear browser cookies and cache, ensure sessions are enabled in PHP configuration.

### Common URL Issues

- XAMPP users: Use `http://localhost/SecurePay/` (not `Web_Tech_Project`)
- PHP server users: Use `http://localhost:8000/`
- Ensure no trailing spaces in URLs

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
