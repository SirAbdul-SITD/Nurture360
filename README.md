# Rinda School Management System

A comprehensive, professional School Management System built with PHP 8+, MySQL, and modern web technologies. Features a complete SuperAdmin dashboard with all essential school management functionalities.

## 🚀 Features

### Core System
- **Secure Authentication System** with bcrypt password hashing
- **Role-based Access Control** (SuperAdmin, Teachers, Students, Supervisors)
- **Session Management** with security features
- **Responsive Design** - Mobile-first approach with mobile app-like navigation

### SuperAdmin Dashboard
- **System Settings** - App configuration, branding, SMTP settings
- **User Management** - Teachers, Students, Supervisors (CRUD operations)
- **Academic Management** - Classes, Subjects, Timetable management
- **LMS Features** - Learning resources, virtual classes, tests, assignments
- **Communication Tools** - Messaging, notifications, announcements
- **Reports & Analytics** - Performance metrics, attendance tracking
- **Security & Access Control** - User permissions, system logs

### Technical Features
- **Modern PHP 8+** with PDO database abstraction
- **Secure MySQL Schema** with proper relationships and indexes
- **Component-based Architecture** - Reusable header, footer, sidebar
- **Professional SaaS-like UI** with clean, modern design
- **Dark/Light Theme Support** with customizable color schemes
- **Mobile Responsive** - Sidebar collapses to bottom navigation on mobile
- **CSRF Protection** and input sanitization
- **File Upload Support** with security validation

## 🛠️ Technology Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Icons**: Font Awesome 6.4.0
- **Charts**: Chart.js
- **Server**: Apache/Nginx (XAMPP/WAMP compatible)

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Apache/Nginx web server
- PHP Extensions: PDO, PDO_MySQL, mbstring, json
- Modern web browser with JavaScript enabled

## 🚀 Installation

### 1. Clone/Download the Project
```bash
git clone https://github.com/yourusername/rinda-school-system.git
cd rinda-school-system
```

### 2. Database Setup
1. Create a new MySQL database:
```sql
CREATE DATABASE rinda_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u your_username -p rinda_school < database/schema.sql
```

3. Import seed data (optional):
```bash
mysql -u your_username -p rinda_school < database/seed_data.sql
```

### 3. Configuration
1. Copy and edit the configuration file:
```bash
cp config/config.php.example config/config.php
```

2. Update database credentials in `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'rinda_school');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

3. Update application settings:
```php
define('APP_URL', 'http://your-domain.com/rinda');
define('SMTP_HOST', 'your_smtp_host');
define('SMTP_USERNAME', 'your_smtp_username');
define('SMTP_PASSWORD', 'your_smtp_password');
```

### 4. Web Server Configuration
1. Point your web server document root to the project directory
2. Ensure `.htaccess` is enabled (Apache) or configure URL rewriting (Nginx)
3. Set proper file permissions:
```bash
chmod 755 -R /path/to/rinda
chmod 777 -R /path/to/rinda/uploads
```

### 5. Access the System
- **URL**: `http://your-domain.com/auth/login.php`
- **Default SuperAdmin Credentials**:
  - Email: `admin@rinda.edu`
  - Password: `Admin123!`

## 📁 Project Structure

```
rinda/
├── auth/                   # Authentication files
│   ├── login.php          # Login page
│   └── logout.php         # Logout script
├── components/             # Reusable components
│   ├── header.php         # Main header
│   ├── footer.php         # Main footer
│   ├── sidebar.php        # Navigation sidebar
│   └── navbar.php         # Mobile navigation
├── config/                 # Configuration files
│   └── config.php         # Main configuration
├── dashboard/              # Dashboard pages
│   └── index.php          # Main dashboard
├── pages/                  # Content pages
│   ├── teachers.php       # Teachers management
│   ├── students.php       # Students management
│   ├── classes.php        # Classes management
│   ├── settings.php       # System settings
│   └── ...                # Other pages
├── assets/                 # Static assets
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── img/               # Images and icons
├── database/               # Database files
│   ├── schema.sql         # Database schema
│   └── seed_data.sql      # Sample data
├── uploads/                # File uploads directory
└── README.md               # This file
```

## 🔐 Security Features

- **Password Hashing**: bcrypt with cost factor 10
- **CSRF Protection**: Token-based request validation
- **Input Sanitization**: XSS prevention
- **Session Security**: Secure session handling
- **SQL Injection Prevention**: Prepared statements with PDO
- **File Upload Security**: Type and size validation
- **Access Control**: Role-based permissions

## 🎨 Customization

### Themes
The system supports custom themes with configurable colors:
- Primary, secondary, and accent colors
- Light/dark mode toggle
- Customizable logo and branding

### Styling
- CSS custom properties for easy theming
- Responsive breakpoints for mobile optimization
- Component-based CSS architecture

## 📱 Mobile Features

- **Responsive Design**: Mobile-first approach
- **Touch-friendly Interface**: Optimized for touch devices
- **Mobile Navigation**: Bottom navigation bar on small screens
- **Collapsible Sidebar**: Sidebar collapses on mobile devices

## 🔧 Development

### Adding New Pages
1. Create a new PHP file in the `pages/` directory
2. Include the header and sidebar components
3. Add your content
4. Include the footer component

### Adding New Components
1. Create a new PHP file in the `components/` directory
2. Include it in your pages using `include` or `require`
3. Add corresponding CSS and JavaScript as needed

### Database Modifications
1. Update the schema in `database/schema.sql`
2. Add migration scripts if needed
3. Update the configuration if new constants are required

## 📊 Database Schema

The system includes comprehensive database tables for:
- **Users & Authentication**: users, user_permissions, system_logs
- **Academic Management**: classes, subjects, timetable, assignments
- **LMS Features**: learning_resources, virtual_classes, tests, grades
- **Communication**: messages, notifications, announcements
- **System**: system_settings, themes, attendance

## 🚀 Deployment

### Production Checklist
- [ ] Update database credentials
- [ ] Configure SMTP settings
- [ ] Set proper file permissions
- [ ] Enable HTTPS
- [ ] Configure backup system
- [ ] Set up monitoring
- [ ] Update application URL
- [ ] Configure error logging

### Performance Optimization
- Enable PHP OPcache
- Configure MySQL query cache
- Use CDN for static assets
- Enable Gzip compression
- Implement caching strategies

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify database credentials in `config/config.php`
   - Ensure MySQL service is running
   - Check database permissions

2. **File Upload Issues**
   - Verify upload directory permissions
   - Check PHP upload settings in `php.ini`
   - Ensure file size limits are appropriate

3. **Session Issues**
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies and cache

4. **CSS/JS Not Loading**
   - Check file paths in components
   - Verify web server configuration
   - Check browser console for errors

### Debug Mode
Enable debug mode by setting in `config/config.php`:
```php
define('DEBUG_MODE', true);
```

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Email: support@rinda.edu
- Documentation: [Wiki](https://github.com/yourusername/rinda-school-system/wiki)

## 🔄 Updates

### Version 1.0.0
- Initial release
- Complete SuperAdmin dashboard
- All core features implemented
- Mobile-responsive design
- Security features implemented

### Planned Features
- API endpoints for mobile apps
- Advanced reporting and analytics
- Integration with external LMS platforms
- Multi-language support
- Advanced notification system

## 🙏 Acknowledgments

- Font Awesome for icons
- Chart.js for data visualization
- Modern CSS techniques and best practices
- PHP community for best practices and security guidelines

---

**Built with ❤️ for modern education management**

*Rinda School Management System - Empowering educators with powerful tools* 