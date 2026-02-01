# 📋 Installation Checklist
## Bus Arrival and Departure Tracking System

Use this checklist to ensure proper installation and setup.

---

## ✅ Pre-Installation

- [ ] XAMPP/WAMP installed and running
- [ ] Apache service started
- [ ] MySQL service started
- [ ] PHP version 7.4 or higher
- [ ] Web browser available (Chrome/Firefox/Edge)

---

## ✅ Installation Steps

### 1. Database Setup
- [ ] Open phpMyAdmin (http://localhost/phpmyadmin)
- [ ] Click "Import" tab
- [ ] Select file: `database_setup.sql`
- [ ] Click "Go" button
- [ ] Verify database `terminal_tracking_system` is created
- [ ] Verify 7 tables are created
- [ ] Verify sample data is inserted (8 routes, 8 buses, 1 admin user)

### 2. File Configuration
- [ ] Copy `Terminal POS` folder to `htdocs/` directory
- [ ] Open `includes/config.php`
- [ ] Update `DB_USER` if needed (default: root)
- [ ] Update `DB_PASS` if needed (default: empty)
- [ ] Update `DB_HOST` if needed (default: localhost)
- [ ] Save changes

### 3. Verify Installation
- [ ] Open browser
- [ ] Navigate to: `http://localhost/Terminal%20POS/check_requirements.php`
- [ ] Verify all checks pass (green checkmarks)
- [ ] If any errors, resolve them before proceeding

### 4. First Login
- [ ] Navigate to: `http://localhost/Terminal%20POS`
- [ ] Should redirect to login page
- [ ] Enter username: `admin`
- [ ] Enter password: `Admin@123`
- [ ] Click "Login" button
- [ ] Should see Dashboard

### 5. Security Setup
- [ ] Click on username (top right)
- [ ] Select "Change Password"
- [ ] Set a strong password (CRITICAL!)
- [ ] Confirm password change
- [ ] Test login with new password

---

## ✅ Functional Testing

### Test Terminal IN
- [ ] Click "Terminal IN" in menu
- [ ] Select Route: "138 - Colombo - Puttalam"
- [ ] Choose "Registered Bus"
- [ ] Select bus: "WP-CAA-1234"
- [ ] Click "Record Arrival"
- [ ] Verify success message appears
- [ ] Check dashboard shows 1 bus in terminal

### Test Terminal OUT
- [ ] Click "Terminal OUT" in menu
- [ ] See the bus you just recorded
- [ ] Note arrival time and duration
- [ ] Click "Depart" button
- [ ] Confirm departure
- [ ] Verify success message with dwell time
- [ ] Check dashboard shows 0 buses in terminal

### Test Manual Entry
- [ ] Go to Terminal IN
- [ ] Select any route
- [ ] Choose "Manual Entry"
- [ ] Enter bus number: "TEST-1234"
- [ ] Enter operator: "Test Operator"
- [ ] Click "Record Arrival"
- [ ] Verify success
- [ ] Go to Terminal OUT and depart it

### Test Reports
- [ ] Go to Reports → Daily Report
- [ ] Should see today's date selected
- [ ] Should see your test entries
- [ ] View summary statistics
- [ ] Click "Export to CSV"
- [ ] Verify CSV file downloads
- [ ] Click "Print Report"
- [ ] Verify print preview looks good

### Test Dashboard
- [ ] Navigate to Dashboard
- [ ] Verify statistics cards show correct numbers
- [ ] Check "Recent Arrivals" list
- [ ] Check "Buses in Terminal" list (should be empty or show current buses)
- [ ] Verify hourly chart displays (if you have data)

---

## ✅ User Management (Optional - via phpMyAdmin)

### Create Terminal IN Operator
- [ ] Open phpMyAdmin
- [ ] Select `terminal_tracking_system` database
- [ ] Click on `users` table
- [ ] Click "Insert" tab
- [ ] Fill in:
  - username: `terminal_in_user`
  - password_hash: Use online bcrypt generator for a password
  - full_name: `Terminal IN Operator`
  - user_role: `terminal_in_operator`
  - is_active: `1`
- [ ] Click "Go"
- [ ] Test login with new user
- [ ] Verify only Terminal IN menu appears

### Create Terminal OUT Operator
- [ ] Repeat above with:
  - username: `terminal_out_user`
  - user_role: `terminal_out_operator`
  - full_name: `Terminal OUT Operator`
- [ ] Test login
- [ ] Verify only Terminal OUT menu appears

### Create Report Viewer
- [ ] Repeat above with:
  - username: `report_viewer`
  - user_role: `report_viewer`
  - full_name: `Report Viewer`
- [ ] Test login
- [ ] Verify only Reports menu appears

**Note:** Password hash can be generated at: https://bcrypt-generator.com/

---

## ✅ Production Deployment (When Ready)

### Security Hardening
- [ ] Change all default passwords
- [ ] Update `.htaccess` security settings
- [ ] Enable HTTPS if available
- [ ] Disable error display in `config.php`:
  ```php
  error_reporting(0);
  ini_set('display_errors', 0);
  ```
- [ ] Set strong database password
- [ ] Restrict database user permissions

### Backup Setup
- [ ] Create backup directory
- [ ] Set up automated database backups
- [ ] Test backup restoration
- [ ] Document backup procedure

### Training
- [ ] Train Terminal IN operators
- [ ] Train Terminal OUT operators
- [ ] Train report viewers
- [ ] Train administrators
- [ ] Provide user manuals

### Go-Live
- [ ] Schedule go-live date
- [ ] Import actual routes and buses
- [ ] Remove test data
- [ ] Monitor first day operations
- [ ] Collect user feedback

---

## ✅ Post-Installation

### Daily Operations
- [ ] Monitor system for errors
- [ ] Verify data accuracy
- [ ] Check audit logs
- [ ] Backup database

### Weekly Maintenance
- [ ] Review reports
- [ ] Check user accounts
- [ ] Verify data integrity
- [ ] Update routes/buses if needed

### Monthly Review
- [ ] Analyze usage patterns
- [ ] Review security logs
- [ ] Archive old data (optional)
- [ ] Plan improvements

---

## 🆘 Troubleshooting Quick Reference

| Problem | Solution |
|---------|----------|
| Can't login | Check DB connection, verify user exists |
| Blank page | Enable error display, check PHP version |
| Database error | Verify credentials in config.php |
| Can't record arrival | Check if bus already in terminal |
| Reports show no data | Verify date selection, check DB data |
| Session timeout | Increase timeout in config.php |

---

## 📞 Support Contacts

- **Technical Issues:** Refer to README.md troubleshooting section
- **Documentation:** See README.md and QUICKSTART.md
- **Email:** support@mmck.lk
- **Phone:** +94 XX XXX XXXX

---

## ✅ Final Verification

Before marking installation complete, verify:

- [ ] All pre-installation requirements met
- [ ] Database successfully created and populated
- [ ] Configuration file updated correctly
- [ ] First login successful
- [ ] Admin password changed
- [ ] All functional tests passed
- [ ] Terminal IN works correctly
- [ ] Terminal OUT works correctly
- [ ] Reports generate successfully
- [ ] Dashboard displays properly
- [ ] Mobile responsive design works
- [ ] Multiple users can login
- [ ] Session timeout works
- [ ] Audit logging works
- [ ] CSV export works
- [ ] Print functionality works

---

## 🎉 Installation Complete!

Once all items are checked, your system is ready for production use.

**Next Steps:**
1. Import your actual routes
2. Register your actual buses
3. Create user accounts for all operators
4. Start tracking bus movements!

---

**Installation Date:** _______________  
**Installed By:** _______________  
**Verified By:** _______________  
**Approved By:** _______________

---

*For detailed documentation, see README.md*  
*For quick reference, see QUICKSTART.md*  
*For project details, see PROJECT_SUMMARY.md*
