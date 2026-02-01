# Bus Tracking System - Quick Start Guide
## Get Started in 5 Minutes

---

## 🚀 Quick Installation

### Step 1: Setup Database (2 minutes)

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click **Import** tab
3. Choose file: `database_setup.sql`
4. Click **Go**
5. Done! Database created with sample data

### Step 2: Configure (1 minute)

Open `includes/config.php` and update if needed:

```php
define('DB_USER', 'root');    // Your MySQL username
define('DB_PASS', '');        // Your MySQL password
```

### Step 3: Login (1 minute)

1. Navigate to: `http://localhost/Terminal%20POS`
2. Login with:
   - **Username:** admin
   - **Password:** Admin@123
3. **IMPORTANT:** Change password immediately!

---

## ✅ Quick Test

### Test Terminal IN (Arrival)
1. Go to **Terminal IN**
2. Select Route: "138 - Colombo - Puttalam"
3. Choose "Registered Bus"
4. Select a bus from dropdown
5. Click **"Record Arrival"**
6. Success! ✓

### Test Terminal OUT (Departure)
1. Go to **Terminal OUT**
2. You'll see the bus you just recorded
3. Click **"Depart"** button
4. Confirm departure
5. Dwell time calculated automatically! ✓

### View Reports
1. Go to **Reports → Daily Report**
2. Select today's date
3. See your test data
4. Try **Export to CSV** or **Print**

---

## 👥 Create Users

### For Terminal Operators

1. Go to **Administration → User Management** (coming soon)
2. Click **"Add New User"**
3. Fill in details:
   - Username: `terminal_in_user`
   - Password: Strong password
   - Role: **Terminal IN Operator**
4. Save

Repeat for Terminal OUT operators and report viewers.

---

## 📊 Sample Data Included

### Routes
- Route 138: Colombo - Puttalam
- Route 1: Colombo - Matara
- Route 2: Colombo - Galle
- And more...

### Registered Buses
- WP-CAA-1234 (Route 138)
- WP-CBB-2345 (Route 1)
- And more...

---

## 🔧 Common Issues

### Can't login?
- Check MySQL is running in XAMPP
- Verify database was imported
- Use credentials: admin / Admin@123

### Blank page?
- Check if Apache is running
- Verify folder is in `htdocs`
- Check PHP version (need 7.4+)

### Database error?
- Open `includes/config.php`
- Check DB credentials are correct
- Ensure database exists

---

## 📱 Mobile Access

The system is fully responsive! Access from:
- Desktop computers
- Tablets
- Smartphones

Just navigate to the server IP address from any device on the network.

---

## 🎯 Next Steps

1. ✅ Change default admin password
2. ✅ Create user accounts for operators
3. ✅ Add your actual routes
4. ✅ Register your buses
5. ✅ Start recording arrivals/departures!

---

## 📚 Full Documentation

See [README.md](README.md) for complete documentation including:
- Detailed installation
- Configuration options
- User management guide
- Troubleshooting
- Security best practices

---

## 🎓 Training Tips

### For Terminal IN Operators
- Select route first
- Registered buses load automatically
- Use manual entry for unlisted buses
- Add remarks for special cases

### For Terminal OUT Operators
- Use route filter for busy terminals
- Check dwell time before departing
- List auto-refreshes every 30 seconds
- Confirm before clicking depart

### For Managers
- Check dashboard for real-time status
- Review daily reports each evening
- Monitor busiest routes and times
- Export data for planning

---

## 📞 Need Help?

If you encounter issues:
1. Check README.md troubleshooting section
2. Review error messages carefully
3. Contact system administrator
4. Email: support@mmck.lk

---

**Happy Tracking! 🚌**

---

*Makumbura Multimodal Center*  
*Bus Arrival & Departure Tracking System v1.0*
