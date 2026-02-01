# 👥 Operator Accounts Guide

## 🔐 Three Separate Accounts with Different Functions

---

## 1️⃣ **ADMIN ACCOUNT** (Full System Access)

### Login Credentials
```
Username: admin
Password: Admin@123
```

### Access Permissions ✅
- ✅ **Dashboard** - View all statistics and charts
- ✅ **Terminal IN** - Record bus arrivals
- ✅ **Terminal OUT** - Record bus departures
- ✅ **Reports** - Access all 5 report types (Hourly, Daily, Weekly, Monthly, Yearly)
- ✅ **Administration** - Full control:
  - User Management (Add/Edit/Delete users)
  - Route Management (Add/Edit/Delete routes)
  - Bus Registration (Add/Edit/Delete buses)
  - Audit Logs (View all system activities)

### Functions
- Complete system control
- Manage all operators and their accounts
- Configure routes and buses
- View comprehensive reports
- Monitor system activities through audit logs
- Emergency override on all operations

---

## 2️⃣ **TERMINAL IN OPERATOR** (Arrival Recording Only)

### Login Credentials
```
Username: terminal_in
Password: TerminalIn@123
```

### Access Permissions ✅
- ✅ **Dashboard** - View statistics (READ ONLY)
- ✅ **Terminal IN** - Record bus arrivals (FULL ACCESS)
- ❌ **Terminal OUT** - No access
- ❌ **Reports** - No access
- ❌ **Administration** - No access

### Functions
- Record bus arrivals only
- Select route from dropdown
- Choose registered bus or enter manually
- Add operator name and remarks
- View current terminal count
- Cannot access departures or administration

### Workflow
1. Login with terminal_in account
2. Click "Terminal IN" in navigation
3. Select route
4. Select/enter bus number
5. Add remarks (optional)
6. Click "Record Arrival"
7. System automatically timestamps entry

---

## 3️⃣ **TERMINAL OUT OPERATOR** (Departure Recording Only)

### Login Credentials
```
Username: terminal_out
Password: TerminalOut@123
```

### Access Permissions ✅
- ✅ **Dashboard** - View statistics (READ ONLY)
- ❌ **Terminal IN** - No access
- ✅ **Terminal OUT** - Record bus departures (FULL ACCESS)
- ❌ **Reports** - No access
- ❌ **Administration** - No access

### Functions
- Record bus departures only
- View list of buses currently in terminal
- See dwell time for each bus
- Record departure with one click
- Cannot access arrivals or administration

### Workflow
1. Login with terminal_out account
2. Click "Terminal OUT" in navigation
3. View list of buses in terminal
4. See how long each bus has been waiting
5. Click "Record Departure" for departing bus
6. Confirm action
7. System automatically timestamps departure

---

## 📊 Access Control Matrix

| Feature | Admin | Terminal IN | Terminal OUT |
|---------|-------|-------------|--------------|
| **Dashboard** | ✅ Full | ✅ View Only | ✅ View Only |
| **Record Arrivals** | ✅ Yes | ✅ Yes | ❌ No |
| **Record Departures** | ✅ Yes | ❌ No | ✅ Yes |
| **Hourly Report** | ✅ Yes | ❌ No | ❌ No |
| **Daily Report** | ✅ Yes | ❌ No | ❌ No |
| **Weekly Report** | ✅ Yes | ❌ No | ❌ No |
| **Monthly Report** | ✅ Yes | ❌ No | ❌ No |
| **Yearly Report** | ✅ Yes | ❌ No | ❌ No |
| **User Management** | ✅ Yes | ❌ No | ❌ No |
| **Route Management** | ✅ Yes | ❌ No | ❌ No |
| **Bus Registration** | ✅ Yes | ❌ No | ❌ No |
| **Audit Logs** | ✅ Yes | ❌ No | ❌ No |

---

## 🎯 Navigation Menu Differences

### Admin Sees:
```
🏠 Dashboard
🚪 Terminal IN
🚪 Terminal OUT
📊 Reports ▼
   ├── Hourly Report
   ├── Daily Report
   ├── Weekly Report
   ├── Monthly Report
   └── Yearly Report
⚙️ Administration ▼
   ├── User Management
   ├── Route Management
   ├── Bus Registration
   └── Audit Logs
👤 Admin ▼
   ├── My Profile
   ├── Change Password
   └── Logout
```

### Terminal IN Operator Sees:
```
🏠 Dashboard (View Only)
🚪 Terminal IN (Full Access)
👤 Terminal IN Operator ▼
   ├── My Profile
   ├── Change Password
   └── Logout
```

### Terminal OUT Operator Sees:
```
🏠 Dashboard (View Only)
🚪 Terminal OUT (Full Access)
👤 Terminal OUT Operator ▼
   ├── My Profile
   ├── Change Password
   └── Logout
```

---

## 🚀 Setup Instructions

### Option 1: Run Setup Script (Recommended)
1. Go to: `https://bus-tracking-app-fbg7hnazcrdjbbe3.southeastasia-01.azurewebsites.net/create_operator_accounts.php`
2. Script will create/update all 3 accounts
3. Note the passwords shown on screen
4. **DELETE the create_operator_accounts.php file after running**

### Option 2: Manual Database Insert
Run this SQL in your Azure MySQL database:

```sql
-- Terminal IN Operator
INSERT INTO users (username, password_hash, full_name, user_role, is_active)
VALUES ('terminal_in', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhqa', 'Terminal IN Operator', 'terminal_in_operator', 1);

-- Terminal OUT Operator  
INSERT INTO users (username, password_hash, full_name, user_role, is_active)
VALUES ('terminal_out', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhqa', 'Terminal OUT Operator', 'terminal_out_operator', 1);
```

---

## 🔒 Security Features

### Password Requirements
- Minimum 6 characters
- BCrypt hashing (cannot be reversed)
- Stored securely in database

### Session Management
- Auto-logout after 1 hour of inactivity
- Session hijacking protection
- Secure cookie settings (HTTPS only on Azure)

### Role-Based Access
- Server-side validation on every page
- Cannot bypass restrictions by changing URL
- All actions logged in audit_logs table

### Audit Logging
- Every login attempt recorded (success/failure)
- Every arrival/departure recorded with operator name
- Every administrative action logged with user ID and IP
- Admin can review all activities in Audit Logs

---

## 📝 Daily Operations Workflow

### Morning Shift
1. **Terminal IN Operator** logs in
2. Records buses arriving throughout the day
3. Notes any special remarks or issues

### Throughout Day
1. **Admin** monitors dashboard
2. Checks real-time statistics
3. Resolves any issues
4. Manages system as needed

### Evening Shift
1. **Terminal OUT Operator** logs in
2. Records buses departing
3. Monitors dwell times
4. Ensures smooth operations

### End of Day
1. **Admin** generates daily report
2. Reviews audit logs for any issues
3. Plans for next day based on data

---

## ⚠️ Important Notes

### DO:
- ✅ Use strong unique passwords
- ✅ Change passwords after first login
- ✅ Log out when leaving workstation
- ✅ Report any suspicious activities
- ✅ Keep login credentials confidential

### DON'T:
- ❌ Share account passwords
- ❌ Use the same password for multiple accounts
- ❌ Leave workstation logged in unattended
- ❌ Try to access restricted functions
- ❌ Modify database directly

---

## 🆘 Troubleshooting

### "Invalid username or password"
- Check spelling and capitalization
- Verify CAPS LOCK is off
- Contact admin to reset password

### "Access Denied"
- You're trying to access a restricted page
- Use only the functions available in your navigation menu
- Contact admin if you need different permissions

### Forgot Password
- Contact the system administrator
- Admin can reset your password from User Management page
- You'll receive a temporary password to change on first login

---

## 📞 Support

### Admin Contact
- **Email:** admin@mmck.lk
- **Role:** System Administrator
- **Can Help With:** Password resets, access issues, system problems

### System URLs
- **Login:** https://bus-tracking-app-fbg7hnazcrdjbbe3.southeastasia-01.azurewebsites.net/login.php
- **Dashboard:** https://bus-tracking-app-fbg7hnazcrdjbbe3.southeastasia-01.azurewebsites.net/dashboard.php

---

*Last Updated: February 1, 2026*  
*Version: 1.0.0*
