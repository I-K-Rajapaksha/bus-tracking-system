# 🌐 Azure Deployment Guide
## Deploy Bus Tracking System to Microsoft Azure

This guide will walk you through deploying your Bus Tracking System to Microsoft Azure using your student account.

---

## 📋 Prerequisites

- ✅ Microsoft Azure Student Account (activated)
- ✅ GitHub repository: https://github.com/I-K-Rajapaksha/bus-tracking-system
- ✅ Azure credits available ($100 for students)
- ✅ Web browser

---

## 🎯 Deployment Architecture

Your application will use:
- **Azure App Service** (Web App) - For PHP application hosting
- **Azure Database for MySQL** - For database
- **GitHub Integration** - For automated deployment

**Estimated Monthly Cost:** ~$10-15 (covered by student credits)

---

## 📝 Step-by-Step Deployment

### Part 1: Create Azure Database for MySQL

#### 1.1 Login to Azure Portal
```
1. Go to: https://portal.azure.com
2. Sign in with your student account
3. Verify you have Azure for Students subscription active
```

#### 1.2 Create MySQL Database
```
1. Click "+ Create a resource"
2. Search for "Azure Database for MySQL"
3. Select "Azure Database for MySQL flexible server"
4. Click "Create"
```

#### 1.3 Configure Database Server
Fill in the following details:

**Basics Tab:**
- **Subscription:** Azure for Students
- **Resource Group:** Create new → `bus-tracking-rg`
- **Server Name:** `bus-tracking-mysql-server` (must be globally unique)
- **Region:** Choose nearest region (e.g., Southeast Asia, East US)
- **MySQL Version:** 8.0 (recommended)
- **Compute + Storage:** 
  - Select "Burstable" tier (B1ms)
  - Storage: 20 GB (sufficient for this application)

**Administrator Account:**
- **Admin Username:** `bustrackadmin`
- **Password:** Create a strong password (save it securely!)
- **Confirm Password:** Re-enter password

**Networking Tab:**
- **Connectivity Method:** Public access
- ✅ Check "Allow public access from any Azure service within Azure"
- Click "Add current client IP address" (to access from your computer)

**Review + Create:**
- Review all settings
- Click "Create"
- Wait 5-10 minutes for deployment

#### 1.4 Configure Firewall Rules
```
1. Once deployed, go to your MySQL server
2. Click "Networking" in left menu
3. Under "Firewall rules":
   - Add rule name: "AllowAll"
   - Start IP: 0.0.0.0
   - End IP: 255.255.255.255
   ⚠️ Note: For production, restrict to specific IPs only
4. Click "Save"
```

#### 1.5 Create Database
```
1. Click "Connect" in left menu
2. Note down the connection details:
   - Server name: bus-tracking-mysql-server.mysql.database.azure.com
   - Admin username: bustrackadmin
   - Port: 3306

3. Open MySQL Workbench or command line
4. Connect using:
   Host: bus-tracking-mysql-server.mysql.database.azure.com
   Username: bustrackadmin
   Password: [your password]
   Port: 3306

5. Run this command:
   CREATE DATABASE terminal_tracking_system;

6. Import database:
   - Use MySQL Workbench: Server → Data Import
   - Select "Import from Self-Contained File"
   - Choose: database_setup.sql
   - Target schema: terminal_tracking_system
   - Click "Start Import"
```

**Alternative: Import via Command Line**
```bash
# Replace with your actual values
mysql -h bus-tracking-mysql-server.mysql.database.azure.com -u bustrackadmin -p terminal_tracking_system < database_setup.sql
```

---

### Part 2: Create Azure App Service (Web App)

#### 2.1 Create Web App
```
1. In Azure Portal, click "+ Create a resource"
2. Search for "Web App"
3. Click "Create"
```

#### 2.2 Configure Web App
**Basics Tab:**
- **Subscription:** Azure for Students
- **Resource Group:** Select `bus-tracking-rg` (same as database)
- **Name:** `bus-tracking-system` (becomes: bus-tracking-system.azurewebsites.net)
- **Publish:** Code
- **Runtime Stack:** PHP 8.2
- **Operating System:** Linux
- **Region:** Same as MySQL database
- **Pricing Plan:** 
  - Select "Free F1" for testing
  - Or "Basic B1" for production ($10/month)

**Deployment Tab:**
- **GitHub Actions settings:**
  - ✅ Enable "Continuous deployment"
  - Connect your GitHub account
  - Select Organization: I-K-Rajapaksha
  - Repository: bus-tracking-system
  - Branch: main
  - Click "Preview file" to see GitHub Actions workflow

**Review + Create:**
- Click "Create"
- Wait 3-5 minutes for deployment

---

### Part 3: Configure Application Settings

#### 3.1 Add Database Configuration
```
1. Go to your Web App resource
2. Click "Configuration" in left menu
3. Under "Application settings", click "+ New application setting"
```

**Add these settings one by one:**

| Name | Value | Example |
|------|-------|---------|
| `DB_HOST` | Your MySQL server name | `bus-tracking-mysql-server.mysql.database.azure.com` |
| `DB_NAME` | `terminal_tracking_system` | `terminal_tracking_system` |
| `DB_USER` | `bustrackadmin` | `bustrackadmin` |
| `DB_PASS` | Your MySQL password | `YourSecurePassword123!` |
| `SITE_URL` | Your Azure URL | `https://bus-tracking-system.azurewebsites.net` |

**Important:** Click "Save" at the top after adding all settings!

#### 3.2 Configure PHP Settings
```
1. Still in "Configuration"
2. Go to "General settings" tab
3. Set the following:
   - PHP Version: 8.2
   - Always On: On (if using Basic plan)
   - ARR Affinity: Off
4. Click "Save"
```

---

### Part 4: Deploy Code from GitHub

#### 4.1 Automatic Deployment
If you enabled GitHub Actions during setup:
```
1. GitHub Actions will automatically deploy on every push
2. Check deployment status:
   - Go to your GitHub repository
   - Click "Actions" tab
   - View running/completed workflows
```

#### 4.2 Manual Deployment (Alternative)
If automatic deployment didn't work:

**Method 1: Deployment Center**
```
1. In Web App, click "Deployment Center"
2. Source: GitHub
3. Authorize Azure to access GitHub
4. Organization: I-K-Rajapaksha
5. Repository: bus-tracking-system
6. Branch: main
7. Click "Save"
```

**Method 2: Azure CLI** (if you have it installed)
```bash
# Login to Azure
az login

# Deploy from GitHub
az webapp deployment source config --name bus-tracking-system --resource-group bus-tracking-rg --repo-url https://github.com/I-K-Rajapaksha/bus-tracking-system --branch main --manual-integration
```

**Method 3: FTP Upload**
```
1. In Web App, go to "Deployment Center"
2. Click "FTP credentials"
3. Note the FTPS hostname and username
4. Use FileZilla or any FTP client:
   - Host: [FTPS hostname]
   - Username: [shown in portal]
   - Password: [shown in portal or reset]
5. Upload all files to /site/wwwroot/
```

---

### Part 5: Configure Application for Azure

#### 5.1 Update config.php for Azure
Create a special configuration file for Azure:

**Option 1: Use Environment Variables (Recommended)**

Edit `includes/config.php` to read from Azure App Settings:
```php
<?php
// Database Configuration - Read from Azure App Settings
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'terminal_tracking_system');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Site Configuration
define('SITE_NAME', 'Bus Terminal Management System');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/Terminal%20POS');

// Session Configuration
define('SESSION_TIMEOUT', 3600);
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

// Error Reporting (set to 0 for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Colombo');

// User Roles
define('ROLE_SUPER_ADMIN', 1);
define('ROLE_TERMINAL_IN', 2);
define('ROLE_TERMINAL_OUT', 3);
define('ROLE_REPORT_VIEWER', 4);
?>
```

**Option 2: Create Azure-specific config**
Create a new file `includes/config.azure.php` and update your code to use it when on Azure.

#### 5.2 Update the Repository
```bash
# Commit the changes
git add includes/config.php
git commit -m "Configure for Azure deployment"
git push origin main

# GitHub Actions will automatically redeploy
```

---

### Part 6: Verify Deployment

#### 6.1 Access Your Application
```
1. Go to your Web App in Azure Portal
2. Click "Browse" at the top
3. Your app will open: https://bus-tracking-system.azurewebsites.net
```

#### 6.2 Test Application
```
1. Login page should appear
2. Default credentials:
   - Username: admin
   - Password: Admin@123
3. Test all features:
   - Dashboard
   - Terminal IN
   - Terminal OUT
   - Reports
   - Route Management
   - Bus Management
```

#### 6.3 Troubleshooting

**If you see a blank page or errors:**

1. **Enable Application Logging:**
   ```
   - Web App → Monitoring → App Service logs
   - Application Logging: On
   - Level: Error
   - Save
   ```

2. **View Logs:**
   ```
   - Go to "Log stream" in left menu
   - Watch for errors in real-time
   ```

3. **Check Database Connection:**
   ```
   - Verify firewall rules allow Azure services
   - Test connection from Azure Cloud Shell:
     mysql -h bus-tracking-mysql-server.mysql.database.azure.com -u bustrackadmin -p
   ```

4. **Common Issues:**
   - **500 Error:** Check config.php values match Azure settings
   - **Database Error:** Verify DB_HOST includes .mysql.database.azure.com
   - **404 Error:** Check files deployed correctly to /site/wwwroot/
   - **Blank Page:** Enable error display in config.php temporarily

---

## 🔒 Security Best Practices

### 1. Restrict Database Access
```
1. In MySQL server → Networking
2. Remove "AllowAll" rule
3. Add specific IP ranges:
   - Your office IP
   - Azure App Service outbound IPs (found in Web App → Properties)
```

### 2. Use SSL for Database
```
1. In config.php, add SSL parameters:
   define('DB_SSL', true);

2. Update Database class to use SSL connection
```

### 3. Enable HTTPS Only
```
1. Web App → TLS/SSL settings
2. HTTPS Only: On
3. Minimum TLS Version: 1.2
```

### 4. Change Default Passwords
```
1. Login to your deployed application
2. Change admin password immediately
3. Update other user passwords
```

### 5. Enable Azure AD Authentication (Optional)
For enterprise security, integrate with Azure Active Directory.

---

## 💰 Cost Management

### Monitor Your Spending
```
1. Azure Portal → Cost Management + Billing
2. Set up budget alerts:
   - Click "Budgets"
   - Create budget for $100 (your student credit)
   - Set alert at 80% usage
```

### Free Tier Options
- **App Service:** F1 Free tier (limited resources, good for testing)
- **MySQL:** B1ms Burstable tier (~$15/month - use credits)
- **Alternative:** Consider Azure for Students free services

### Optimize Costs
- Use Free F1 App Service for development
- Scale to Basic B1 ($10/month) only when needed
- Stop MySQL server when not in use (deallocate)
- Use Azure Cost Calculator: https://azure.microsoft.com/pricing/calculator/

---

## 📊 Monitoring & Maintenance

### Application Insights (Optional)
```
1. Create Application Insights resource
2. Connect to your Web App
3. Monitor:
   - Response times
   - Failed requests
   - User traffic
   - Exceptions
```

### Database Backups
```
1. MySQL → Backup and restore
2. Configure automated backups:
   - Retention: 7 days
   - Backup window: Off-peak hours
3. Test restore periodically
```

### Performance Monitoring
```
1. Web App → Diagnose and solve problems
2. Monitor:
   - CPU usage
   - Memory usage
   - Response times
3. Scale up if needed
```

---

## 🔄 Continuous Deployment

### GitHub Actions Workflow
Your deployment is automated via GitHub Actions:

```yaml
# .github/workflows/main_bus-tracking-system.yml
# This file was created automatically by Azure
# It deploys your code whenever you push to main branch
```

### Manual Deployment
To manually trigger deployment:
```bash
# Make any code change
git add .
git commit -m "Update application"
git push origin main

# Watch deployment in GitHub Actions tab
```

---

## 🌐 Custom Domain (Optional)

If you want your own domain instead of .azurewebsites.net:

```
1. Purchase domain from any registrar
2. Web App → Custom domains
3. Add custom domain
4. Configure DNS records:
   - CNAME: www.yourdomain.com → bus-tracking-system.azurewebsites.net
   - A record: @ → [App Service IP]
5. Verify domain ownership
6. Enable managed SSL certificate (free)
```

---

## 📞 Support Resources

### Azure Documentation
- Web Apps: https://docs.microsoft.com/azure/app-service/
- MySQL: https://docs.microsoft.com/azure/mysql/
- Student Portal: https://portal.azure.com

### Student Support
- Azure for Students: https://azure.microsoft.com/free/students/
- Student Support: https://aka.ms/azureforeducation
- Community Forum: https://learn.microsoft.com/answers/

### Common Commands
```bash
# Azure CLI - Install from: https://docs.microsoft.com/cli/azure/install-azure-cli

# Login
az login

# List resources
az resource list --resource-group bus-tracking-rg

# View Web App logs
az webapp log tail --name bus-tracking-system --resource-group bus-tracking-rg

# Restart Web App
az webapp restart --name bus-tracking-system --resource-group bus-tracking-rg

# View MySQL server details
az mysql flexible-server show --name bus-tracking-mysql-server --resource-group bus-tracking-rg
```

---

## ✅ Deployment Checklist

- [ ] MySQL database created and configured
- [ ] Database firewall rules set
- [ ] Database imported from database_setup.sql
- [ ] Web App created and configured
- [ ] PHP version set to 8.2
- [ ] Application settings configured (DB credentials)
- [ ] GitHub connected for continuous deployment
- [ ] config.php updated to use environment variables
- [ ] Application accessible via Azure URL
- [ ] Login works with default credentials
- [ ] All modules tested (Dashboard, Terminal IN/OUT, Reports)
- [ ] Default admin password changed
- [ ] HTTPS enforced
- [ ] Database firewall restricted (remove AllowAll)
- [ ] Budget alerts configured
- [ ] Backups enabled

---

## 🎉 Congratulations!

Your Bus Tracking System is now live on Microsoft Azure!

**Your Application URL:**
`https://bus-tracking-system.azurewebsites.net`

**Next Steps:**
1. Share the URL with stakeholders
2. Train users on the system
3. Monitor performance and usage
4. Plan for scaling if needed
5. Keep your GitHub repository updated

---

**Version:** 1.0  
**Last Updated:** February 1, 2026  
**Azure Deployment Guide** for Bus Tracking System

For questions or issues, refer to Azure documentation or contact support through the Azure Portal.
