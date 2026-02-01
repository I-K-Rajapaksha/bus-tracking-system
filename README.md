# Bus Arrival and Departure Tracking System
## Makumbura Multimodal Center

**Version:** 1.0.0  
**Date:** January 31, 2026

---

## Table of Contents

1. [System Overview](#system-overview)
2. [System Requirements](#system-requirements)
3. [Installation Guide](#installation-guide)
4. [Configuration](#configuration)
5. [First Login](#first-login)
6. [User Guide](#user-guide)
7. [Module Documentation](#module-documentation)
8. [Troubleshooting](#troubleshooting)
9. [Support](#support)

---

## System Overview

The Bus Arrival and Departure Tracking System is a comprehensive web-based application designed to digitize and automate the recording of bus movements at Makumbura Multimodal Center. The system provides real-time tracking, automated record-keeping, and comprehensive reporting capabilities.

### Key Features

- **Terminal IN Module**: Record bus arrivals with registered or manual entry
- **Terminal OUT Module**: Record bus departures with automatic dwell time calculation
- **User Management**: Role-based access control with multiple user types
- **Master Data Management**: Manage routes and registered buses
- **Comprehensive Reporting**: Hourly, daily, weekly, monthly, and yearly reports
- **Dashboard**: Real-time overview of terminal operations
- **Audit Trail**: Complete logging of all user actions

---

## System Requirements

### Server Requirements

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 7.4 or higher (8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Disk Space**: Minimum 500MB
- **RAM**: Minimum 2GB

### PHP Extensions Required

- PDO
- PDO_MySQL
- mbstring
- json
- session

### Client Requirements

- **Browser**: Chrome 90+, Firefox 88+, Edge 90+, Safari 14+
- **JavaScript**: Enabled
- **Screen Resolution**: Minimum 1024x768 (responsive design supports mobile)

---

## Installation Guide

### Step 1: Install XAMPP or WAMP

1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP to `C:\xampp\` (Windows) or `/opt/lampp/` (Linux)
3. Start Apache and MySQL services from XAMPP Control Panel

### Step 2: Copy Project Files

1. Copy the entire `Terminal POS` folder to your web server document root:
   - XAMPP: `C:\xampp\htdocs\`
   - WAMP: `C:\wamp64\www\`

### Step 3: Create Database

1. Open your web browser and navigate to: `http://localhost/phpmyadmin`
2. Click on "Import" tab
3. Click "Choose File" and select `database_setup.sql` from the project folder
4. Click "Go" to execute the SQL script
5. The database `terminal_tracking_system` will be created with sample data

**Alternative Method (Command Line):**

```bash
mysql -u root -p < "d:\Terminal POS\database_setup.sql"
```

### Step 4: Configure Database Connection

1. Open the file: `includes\config.php`
2. Update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // Your MySQL username
define('DB_PASS', '');             // Your MySQL password
define('DB_NAME', 'terminal_tracking_system');
```

3. Update the site URL if needed:

```php
define('SITE_URL', 'http://localhost/Terminal%20POS');
```

### Step 5: Set File Permissions (Linux Only)

```bash
chmod -R 755 /path/to/Terminal\ POS
chmod -R 777 /path/to/Terminal\ POS/uploads  # If you add file upload functionality
```

### Step 6: Verify Installation

1. Open browser and navigate to: `http://localhost/Terminal%20POS`
2. You should see the login page
3. If you see errors, check the troubleshooting section

---

## Configuration

### Database Configuration

Edit `includes/config.php`:

```php
// Database Configuration
define('DB_HOST', 'localhost');    // Database host
define('DB_USER', 'root');         // Database username
define('DB_PASS', '');             // Database password
define('DB_NAME', 'terminal_tracking_system');
```

### Session Configuration

```php
define('SESSION_TIMEOUT', 3600);   // Session timeout in seconds (1 hour)
```

### Timezone Configuration

```php
define('TIMEZONE', 'Asia/Colombo'); // Set your timezone
```

### Security Configuration

For production, update `includes/config.php`:

```php
// Disable error display
error_reporting(0);
ini_set('display_errors', 0);
```

---

## First Login

### Default Credentials

- **Username:** `admin`
- **Password:** `Admin@123`

### Important Security Steps

1. **Change Default Password Immediately**
   - Login with default credentials
   - Click on your name in the top right
   - Select "Change Password"
   - Set a strong password

2. **Create User Accounts**
   - Go to Administration → User Management
   - Create accounts for each operator with appropriate roles
   - Deactivate or delete default admin if needed

---

## User Guide

### User Roles

#### 1. Super Administrator
- Full system access
- User management
- Route and bus registration
- All reports
- System configuration

#### 2. Terminal IN Operator
- Record bus arrivals only
- View current terminal status

#### 3. Terminal OUT Operator
- Record bus departures only
- View current terminal status

#### 4. Report Viewer
- View all reports
- Export reports
- No data entry access

### Recording Bus Arrivals (Terminal IN)

1. Navigate to **Terminal IN** from the menu
2. Select the **Route** from dropdown
3. Choose **Entry Method**:
   - **Registered Bus**: Select bus from list
   - **Manual Entry**: Type bus number manually
4. Add **Remarks** if needed (optional)
5. Click **"Record Arrival"**
6. Confirmation message will appear

### Recording Bus Departures (Terminal OUT)

1. Navigate to **Terminal OUT** from the menu
2. Optionally filter by route
3. View list of buses currently in terminal
4. Click **"Depart"** button for the departing bus
5. Confirm the departure
6. System automatically calculates dwell time

### Viewing Reports

#### Daily Report
1. Go to Reports → Daily Report
2. Select date using date picker
3. View summary statistics and detailed movements
4. Export to CSV or print using buttons provided

#### Other Reports
- **Hourly Report**: Bus movements by hour
- **Weekly Report**: 7-day comparison
- **Monthly Report**: Monthly statistics
- **Yearly Report**: Annual overview
- **Summary Report**: Custom date range

---

## Module Documentation

### Terminal IN Module

**Location:** `modules/terminal_in/index.php`

**Features:**
- Route selection
- Registered bus selection
- Manual entry for unregistered buses
- Duplicate prevention
- Real-time validation

**Data Captured:**
- Bus number
- Route
- Arrival timestamp
- Entry method
- Operator name (for manual entries)
- Remarks

### Terminal OUT Module

**Location:** `modules/terminal_out/index.php`

**Features:**
- View buses in terminal
- Filter by route
- Automatic dwell time calculation
- Real-time updates
- Auto-refresh every 30 seconds

**Data Captured:**
- Departure timestamp
- Dwell time (minutes)
- Associated arrival record

### User Administration

**Location:** `modules/admin/users.php`

**Features:**
- Create new users
- Edit user details
- Assign roles
- Activate/deactivate users
- View user activity

### Route Management

**Location:** `modules/master_data/routes.php`

**Features:**
- Add new routes
- Edit route details
- Activate/deactivate routes
- View registered buses per route

### Bus Registration

**Location:** `modules/master_data/buses.php`

**Features:**
- Register buses
- Assign to routes
- Add operator information
- Manage bus status

---

## Troubleshooting

### Cannot Login

**Problem:** Error message "Invalid username or password"

**Solution:**
1. Verify default credentials: `admin` / `Admin@123`
2. Check database connection in `includes/config.php`
3. Verify database was imported correctly
4. Check if users table has data:
   ```sql
   SELECT * FROM users WHERE username = 'admin';
   ```

### Database Connection Error

**Problem:** "Database Connection Failed"

**Solution:**
1. Check MySQL service is running
2. Verify credentials in `includes/config.php`
3. Ensure database exists:
   ```sql
   SHOW DATABASES LIKE 'terminal_tracking_system';
   ```
4. Check PHP PDO extension is installed:
   ```php
   php -m | grep PDO
   ```

### Blank Page or Errors

**Problem:** White screen or PHP errors

**Solution:**
1. Enable error reporting temporarily in `includes/config.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
2. Check Apache error logs
3. Verify PHP version: `php -v`
4. Ensure all required PHP extensions are installed

### Session Timeout Issues

**Problem:** Logged out too quickly

**Solution:**
1. Increase session timeout in `includes/config.php`:
   ```php
   define('SESSION_TIMEOUT', 7200); // 2 hours
   ```
2. Update PHP session settings in `php.ini`:
   ```ini
   session.gc_maxlifetime = 7200
   ```

### CSV Export Not Working

**Problem:** Export button doesn't download file

**Solution:**
1. Check browser pop-up blocker
2. Verify PHP output buffering settings
3. Check write permissions on temporary directory
4. Try different browser

---

## Database Backup

### Manual Backup (phpMyAdmin)

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select `terminal_tracking_system` database
3. Click "Export" tab
4. Choose "Quick" export method
5. Click "Go" to download SQL file

### Command Line Backup

```bash
mysqldump -u root -p terminal_tracking_system > backup_$(date +%Y%m%d).sql
```

### Restore from Backup

```bash
mysql -u root -p terminal_tracking_system < backup_20260131.sql
```

---

## Maintenance

### Daily Tasks
- Monitor system for errors
- Verify data accuracy
- Check disk space

### Weekly Tasks
- Review audit logs
- Backup database
- Update user accounts if needed

### Monthly Tasks
- Review and archive old data (optional)
- Generate monthly reports
- Update routes/buses if needed

---

## System Architecture

```
Terminal POS/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── includes/
│   ├── config.php           # Configuration
│   ├── database.php         # Database class
│   ├── functions.php        # Helper functions
│   ├── header.php           # Header template
│   └── footer.php           # Footer template
├── modules/
│   ├── terminal_in/         # Arrival recording
│   ├── terminal_out/        # Departure recording
│   ├── admin/               # User management
│   ├── master_data/         # Routes & buses
│   └── reports/             # All reports
├── api/
│   └── terminal_count.php   # API endpoints
├── database_setup.sql       # Database schema
├── index.php                # Entry point
├── login.php                # Login page
├── logout.php               # Logout handler
├── dashboard.php            # Main dashboard
└── README.md                # This file
```

---

## Security Best Practices

1. **Change default password immediately**
2. **Use strong passwords** (minimum 8 characters, mixed case, numbers, symbols)
3. **Regular backups** (daily recommended)
4. **Limit user access** (assign minimal required roles)
5. **Update PHP and MySQL** regularly
6. **Enable HTTPS** in production
7. **Disable error display** in production
8. **Review audit logs** regularly

---

## Support

For technical support or questions:

- **Email:** support@mmck.lk
- **Phone:** +94 XX XXX XXXX
- **Website:** https://www.mmck.lk

---

## Change Log

### Version 1.0.0 (January 31, 2026)
- Initial release
- Terminal IN module
- Terminal OUT module
- User management
- Route and bus registration
- Daily reporting
- Dashboard with real-time statistics

---

## License

Copyright © 2026 Makumbura Multimodal Center. All rights reserved.

---

## Credits

**Developed by:** [Your Company Name]  
**Project Manager:** [Name]  
**Lead Developer:** [Name]  
**Database Design:** [Name]

---

**End of Documentation**
