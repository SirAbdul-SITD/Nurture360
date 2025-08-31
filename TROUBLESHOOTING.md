# Troubleshooting Guide - Rinda School Management System

## 403 Forbidden Error

If you're getting a "403 Forbidden" error, follow these steps:

### 1. Check File Permissions
```bash
# Make sure files are readable by web server
chmod 644 *.php
chmod 644 assets/css/*.css
chmod 644 assets/js/*.js
chmod 755 assets/
chmod 755 auth/
chmod 755 components/
chmod 755 dashboard/
chmod 755 pages/
```

### 2. Check XAMPP Configuration
- Ensure Apache is running in XAMPP Control Panel
- Check Apache error logs: `xampp/apache/logs/error.log`
- Verify Apache configuration allows `.htaccess` files

### 3. Test Basic Access
Try accessing these files directly:
- `http://localhost/rinda/test.php` - Should show PHP info
- `http://localhost/rinda/index.php` - Should redirect to login
- `http://localhost/rinda/auth/login.php` - Should show login page

### 4. Common Issues & Solutions

#### Issue: 403 Forbidden on all files
**Solution:** Check if Apache has `AllowOverride All` in configuration

#### Issue: CSS/JS files not loading
**Solution:** Check file permissions and ensure assets directory is accessible

#### Issue: Database connection error
**Solution:** 
- Verify MySQL is running in XAMPP
- Check database credentials in `config/config.php`
- Ensure database `rinda_school` exists

### 5. XAMPP Configuration

#### Apache Configuration
In `xampp/apache/conf/httpd.conf`, ensure:
```apache
<Directory "C:/xampp/htdocs">
    Options Indexes FollowSymLinks Includes ExecCGI
    AllowOverride All
    Require all granted
</Directory>
```

#### PHP Configuration
In `xampp/php/php.ini`, ensure:
```ini
display_errors = On
error_reporting = E_ALL
session.save_handler = files
```

### 6. Database Setup
```sql
-- Create database
CREATE DATABASE rinda_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u root -p rinda_school < database/schema.sql

-- Import seed data
mysql -u root -p rinda_school < database/seed_data.sql
```

### 7. File Structure Check
Ensure your directory structure looks like this:
```
rinda/
├── auth/
│   ├── login.php
│   └── logout.php
├── components/
│   ├── header.php
│   ├── footer.php
│   └── sidebar.php
├── config/
│   └── config.php
├── dashboard/
│   └── index.php
├── pages/
│   └── teachers.php
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── database/
│   ├── schema.sql
│   └── seed_data.sql
├── .htaccess
├── index.php
└── test.php
```

### 8. Quick Fix Commands
```bash
# Reset permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Make uploads directory writable
chmod 777 uploads/

# Check Apache status
sudo systemctl status apache2  # Linux
# or check XAMPP Control Panel on Windows
```

### 9. Alternative Access Methods
If `.htaccess` continues to cause issues:

1. **Temporarily rename `.htaccess`** to `.htaccess.bak`
2. **Access files directly** without URL rewriting
3. **Use absolute paths** in your PHP includes

### 10. Contact Support
If issues persist:
- Check XAMPP error logs
- Verify PHP version (requires 8.0+)
- Ensure all required PHP extensions are enabled
- Check if mod_rewrite is enabled in Apache

## Default Login Credentials
- **Email:** admin@rinda.edu
- **Password:** Admin123!

## System Requirements
- PHP 8.0 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Apache with mod_rewrite enabled
- PHP extensions: PDO, PDO_MySQL, mbstring, json 