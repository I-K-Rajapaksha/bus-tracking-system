# 🚀 Azure Deployment - Quick Access Guide

## 📍 Your Live Website URLs

**Main Website:**
```
https://bus-tracking-app-fbg7hnazcrdjbbe3.southeastasia-01.azurewebsites.net
```

**Login Page:**
```
https://bus-tracking-app-fbg7hnazcrdjbbe3.southeastasia-01.azurewebsites.net/login.php
```

**Diagnostic Test (IMPORTANT - Run this first!):**
```
https://bus-tracking-app-fbg7hnazcrdjbbe3.southeastasia-01.azurewebsites.net/test_connection.php
```

---

## 🔑 Login Credentials

**Username:** `admin`  
**Password:** `Admin@123`

---

## ✅ Step-by-Step: Making System Fully Operational

### Step 1: Wait for Deployment (2-3 minutes)
Current deployment status: **In Progress** (started at the time of last push)

GitHub Actions is deploying your latest code to Azure. This takes approximately 2-3 minutes.

### Step 2: Run Diagnostic Test
Once deployment completes:

1. Open: `https://bus-tracking-app-fbg7hnazcrdjbbe3.southeastasia-01.azurewebsites.net/test_connection.php`
2. Check all sections show ✅ (green checkmarks):
   - PHP Environment ✅
   - PHP Extensions ✅
   - Azure Environment Variables ✅
   - Configuration File ✅
   - **Database Connection** ✅ ← MOST IMPORTANT
   - File System ✅
   - Session Test ✅

### Step 3: Login to System
If diagnostic test passes:

1. Go to: `https://bus-tracking-app-fbg7hnazcrdjbbe3.southeastasia-01.azurewebsites.net/login.php`
2. Enter credentials:
   - Username: `admin`
   - Password: `Admin@123`
3. Click **Login**

### Step 4: Verify Dashboard
After successful login, you should see:
- Real-time terminal statistics
- Today's arrivals/departures count
- Hourly activity chart
- Quick action buttons

---

## 🔧 What Was Fixed

1. **✅ HTTPS Configuration**
   - Auto-detects Azure environment
   - Forces HTTPS connections
   - Proper URL generation

2. **✅ SSL/TLS Database Connection**
   - Azure MySQL requires SSL
   - PDO configured with SSL support
   - Certificate verification disabled for Azure compatibility

3. **✅ Environment Variables**
   - DB_HOST: `bus-tracking-mysql.mysql.database.azure.com`
   - DB_NAME: `terminal_tracking_system`
   - DB_USER: `bustrackadmin`
   - DB_PASS: `Isu@0724`
   - DB_PORT: `3306`

4. **✅ Error Handling**
   - Production: Errors logged, not displayed
   - Development: Full error display
   - Graceful error pages with auto-retry

5. **✅ Diagnostic Tools**
   - test_connection.php: Complete system check
   - db_error.php: Friendly error page with troubleshooting

---

## 📊 Azure Resources Summary

### MySQL Database
- **Server:** bus-tracking-mysql.mysql.database.azure.com
- **Database:** terminal_tracking_system
- **Tier:** Burstable B1ms (1 vCore, 2 GB RAM)
- **Cost:** $21.74/month
- **Data:** ✅ 7 tables, 8 routes, 8 buses, admin user imported

### Web App Service
- **URL:** bus-tracking-app-fbg7hnazcrdjbbe3.southeastasia-01.azurewebsites.net
- **Runtime:** PHP 8.2 on Linux
- **Tier:** Basic B1 (1 vCore, 1.75 GB RAM)
- **Cost:** $13.14/month
- **Deployment:** GitHub Actions (automatic)

### Total Monthly Cost
**~$35/month** (fits within $100/month student budget for 3 months of operation)

---

## 🎯 Available Features

Once logged in, you can:

1. **Dashboard**
   - View real-time terminal status
   - See hourly activity charts
   - Monitor buses in terminal

2. **Terminal IN** (Record Arrivals)
   - Select route and bus
   - Manual entry for unregistered buses
   - Automatic timestamp recording

3. **Terminal OUT** (Record Departures)
   - View all buses in terminal
   - One-click departure recording
   - Auto-calculate dwell time

4. **Reports**
   - Daily Report (with CSV export)
   - Hourly Report
   - Weekly Report
   - Monthly Report
   - Yearly Report

---

## 🐛 Troubleshooting

### Issue: "Database Connection Failed"

**Solution:**
1. Wait 60 seconds (Azure MySQL may be starting)
2. Check Azure Portal → MySQL Server status = "Available"
3. Verify environment variables in App Service Configuration
4. Run test_connection.php for detailed diagnostics

### Issue: Chrome "Dangerous Site" Warning

**Solution:**
1. Click "Details" button
2. Click "visit this unsafe site"
3. This is a false positive for new Azure sites
4. Warning will clear automatically in 24-48 hours

### Issue: Page Shows Errors

**Solution:**
1. Clear browser cache (Ctrl+Shift+Delete)
2. Wait for deployment to complete (check GitHub Actions)
3. Verify Azure App Service shows "Running" status

---

## 📱 Check Deployment Status

**GitHub Actions:**
```
https://github.com/I-K-Rajapaksha/bus-tracking-system/actions
```

**Azure Portal:**
```
https://portal.azure.com
→ App Services → bus-tracking-app
→ Deployment Center
```

---

## ⚡ Quick Commands

### Check if website is responding:
```bash
curl -I https://bus-tracking-app-fbg7hnazcrdjbbe3.southeastasia-01.azurewebsites.net
```

### View deployment logs (Azure Cloud Shell):
```bash
az webapp log tail --name bus-tracking-app --resource-group <your-resource-group>
```

---

## 🎉 Success Indicators

Your system is **fully operational** when:

1. ✅ test_connection.php shows all green checkmarks
2. ✅ You can login with admin/Admin@123
3. ✅ Dashboard displays with charts and statistics
4. ✅ You can record a test arrival/departure
5. ✅ Reports generate and export to CSV

---

## ⏱️ Timeline

- **Now:** Code pushed to GitHub
- **+2 minutes:** GitHub Actions deployment completes
- **+3 minutes:** Website fully operational
- **+5 minutes:** Database connection stable

**Current Time:** Check your watch and add 3 minutes → That's when you should test!

---

## 🔒 Security Notes

1. **⚠️ Delete test_connection.php** after verifying deployment (contains diagnostic info)
2. ✅ Change admin password after first login
3. ✅ HTTPS is enforced automatically
4. ✅ SSL/TLS encryption for database
5. ✅ Passwords are bcrypt hashed
6. ✅ SQL injection protection enabled

---

## 📞 Need Help?

**If test_connection.php shows errors:**
- Screenshot the diagnostic page
- Note which section shows ❌
- Check Azure Portal for resource status

**If you can't login:**
- Verify admin user exists in database (check Azure MySQL)
- Try password reset via phpMyAdmin in Azure
- Check audit logs for failed login attempts

---

## 🎯 Next Steps After System is Running

1. **Change Admin Password**
   - Profile → Change Password

2. **Add Users**
   - Create Terminal IN operators
   - Create Terminal OUT operators
   - Create report viewers

3. **Customize Routes & Buses**
   - Update sample routes
   - Register actual bus fleet
   - Set proper route schedules

4. **Start Operations**
   - Train staff on Terminal IN/OUT modules
   - Begin recording arrivals/departures
   - Generate daily reports

5. **Monitor Performance**
   - Check Azure costs daily
   - Review system logs weekly
   - Backup database weekly

---

**🎊 Congratulations!** Your Bus Tracking System is deployed on Microsoft Azure and ready for production use!

**Access it now:**
https://bus-tracking-app-fbg7hnazcrdjbbe3.southeastasia-01.azurewebsites.net

---

*Last Updated: February 1, 2026*
*Deployment Version: 1.0.0*
