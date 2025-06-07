# 🏥 Yuwaprasart Queue Management System

A comprehensive, modern queue management system designed for hospitals and healthcare facilities. Built with PHP, MySQL, and modern web technologies to provide efficient patient flow management.

## ✨ Features

### 🎯 Core Features
- **Multi-Service Queue Management** - Support for different service types and departments
- **Real-time Status Tracking** - Live queue status updates and monitoring
- **Staff Dashboard** - Comprehensive staff interface for queue management
- **Admin Panel** - Full administrative control and system configuration
- **Mobile-Responsive Design** - Works perfectly on all devices
- **Audio Call System** - Text-to-speech queue calling with multiple voice options
- **Multi-language Support** - Thai and English interface

### 📊 Advanced Features
- **Analytics Dashboard** - Real-time analytics and performance metrics
- **Advanced Reporting** - Customizable reports with multiple export formats
- **Auto-Reset System** - Scheduled automatic queue number resets
- **Notification Center** - Email and Telegram notifications
- **Service Flow Management** - Configure complex multi-step service processes
- **Role-Based Access Control** - Granular permissions and user management
- **Audit Logging** - Complete activity tracking and security logs
- **Backup & Restore** - Automated data backup and recovery

### 🔧 Technical Features
- **RESTful API** - Complete API for mobile app integration
- **Database Diagnostics** - Built-in database health monitoring
- **Environment Configuration** - Secure configuration management
- **Composer Package Management** - Modern PHP dependency management
- **Responsive UI** - Bootstrap-based responsive design

## 🚀 Quick Start

### Prerequisites
- **PHP 8.0+** with extensions: `mysqli`, `json`, `curl`, `mbstring`
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Web Server** (Apache/Nginx)
- **Composer** (for dependency management)

### Installation

#### Option 1: Automated Installation (Recommended)

**Linux/macOS:**
\`\`\`bash
# Clone the repository
git clone https://github.com/your-repo/yuwaprasart-queue-system.git
cd yuwaprasart-queue-system

# Make installation script executable
chmod +x install.sh

# Run installation
./install.sh
\`\`\`

**Windows:**
\`\`\`batch
# Clone the repository
git clone https://github.com/your-repo/yuwaprasart-queue-system.git
cd yuwaprasart-queue-system

# Run installation
install.bat
\`\`\`

#### Option 2: Manual Installation

1. **Check System Requirements**
   \`\`\`bash
   php scripts/check-system-requirements.php
   \`\`\`

2. **Install Dependencies**
   \`\`\`bash
   composer install --ignore-platform-req=ext-zip
   \`\`\`

3. **Install Optional Packages**
   \`\`\`bash
   php scripts/install-optional-packages.php
   \`\`\`

4. **Configure Environment**
   \`\`\`bash
   cp .env.example .env
   # Edit .env with your database credentials
   \`\`\`

5. **Import Database**
   \`\`\`bash
   mysql -u username -p database_name < database/schema.sql
   mysql -u username -p database_name < database/default_settings.sql
   \`\`\`

## ⚙️ Configuration

### Database Configuration

Edit `.env` file with your database credentials:

\`\`\`env
# Database Configuration
DB_HOST=localhost
DB_NAME=yuwaprasart_queue
DB_USER=your_username
DB_PASS=your_password

# Application Settings
APP_NAME="Yuwaprasart Queue System"
APP_URL=http://localhost/yuwaprasart-queue-system
APP_TIMEZONE=Asia/Bangkok

# Security
JWT_SECRET=your-secret-key-here
SESSION_LIFETIME=3600

# Telegram Bot (Optional)
TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_CHAT_ID=your-chat-id

# Email Configuration (Optional)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
\`\`\`

### Web Server Configuration

#### Apache (.htaccess)
\`\`\`apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1.php [L]
\`\`\`

#### Nginx
\`\`\`nginx
location /api/ {
    try_files $uri $uri.php $uri/ =404;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
\`\`\`

## 📱 Usage

### For Patients
1. **Get Queue Number** - Visit the main page and select service type
2. **Check Status** - Use the provided QR code or link to check queue status
3. **Real-time Updates** - Monitor your position and estimated waiting time

### For Staff
1. **Login** - Access staff dashboard at `/staff/`
2. **Manage Queues** - Call, skip, or complete queue numbers
3. **Monitor Service Points** - View real-time service point status

### For Administrators
1. **Admin Panel** - Access full admin interface at `/admin/`
2. **User Management** - Create and manage staff accounts
3. **System Configuration** - Configure service types, points, and flows
4. **Reports & Analytics** - Generate comprehensive reports

## 🔧 API Documentation

### Authentication
\`\`\`http
POST /api/mobile/auth.php
Content-Type: application/json

{
  "username": "staff_user",
  "password": "password"
}
\`\`\`

### Queue Management
\`\`\`http
# Generate new queue
POST /api/generate_queue.php
Content-Type: application/json

{
  "service_type_id": 1,
  "patient_name": "John Doe"
}

# Get queue status
GET /api/get_queues.php?service_type_id=1

# Update queue status
POST /api/queue_action.php
Content-Type: application/json

{
  "queue_id": 123,
  "action": "call",
  "service_point_id": 1
}
\`\`\`

## 🎨 Customization

### Themes and Styling
- Edit `admin/globals.css` for global styles
- Modify Bootstrap variables in `tailwind.config.ts`
- Customize colors and branding in admin settings

### Audio System
- Upload custom audio files in admin panel
- Configure TTS settings for different languages
- Set up audio call sequences

### Notifications
- Configure Telegram bot for instant notifications
- Set up email templates for automated messages
- Customize notification triggers and recipients

## 🔒 Security Features

### Authentication & Authorization
- **JWT Token-based Authentication** for API access
- **Role-based Access Control** with granular permissions
- **Session Management** with configurable timeouts
- **Password Hashing** using PHP's password_hash()

### Data Protection
- **SQL Injection Prevention** using prepared statements
- **XSS Protection** with input sanitization
- **CSRF Protection** for form submissions
- **Audit Logging** for all administrative actions

### System Security
- **Environment Variable Protection** for sensitive data
- **File Upload Restrictions** with type validation
- **Rate Limiting** for API endpoints
- **Secure Headers** implementation

## 📊 Monitoring & Maintenance

### Health Checks
\`\`\`bash
# Check system health
php admin/database_diagnostic.php

# Validate database structure
php api/validate_database_structure.php

# Test queue status functionality
php test_queue_status.php
\`\`\`

### Backup & Restore
\`\`\`bash
# Create backup
php api/backup_before_reset.php

# Restore from backup
# Use admin panel backup management interface
\`\`\`

### Log Management
- **Application Logs**: `logs/app.log`
- **Error Logs**: `logs/error.log`
- **Audit Logs**: Available in admin panel
- **Auto-Reset Logs**: `logs/auto_reset.log`

## 🚀 Performance Optimization

### Database Optimization
- **Indexed Columns** for fast queries
- **Query Optimization** with proper joins
- **Connection Pooling** for high traffic
- **Regular Maintenance** scripts included

### Caching
- **Browser Caching** for static assets
- **Database Query Caching** for repeated queries
- **Session Caching** for user data
- **Redis Support** for advanced caching (optional)

### Frontend Optimization
- **Minified CSS/JS** for faster loading
- **Responsive Images** for mobile optimization
- **Lazy Loading** for better performance
- **Progressive Web App** features

## 🔧 Troubleshooting

### Common Issues

#### Composer Installation Fails
\`\`\`bash
# Missing ZIP extension
composer install --ignore-platform-req=ext-zip

# Check requirements
php scripts/check-system-requirements.php
\`\`\`

#### Database Connection Issues
\`\`\`bash
# Test database connection
php admin/database_diagnostic.php

# Check credentials in .env file
# Verify MySQL service is running
\`\`\`

#### Permission Errors
\`\`\`bash
# Set proper permissions
chmod -R 755 .
chmod -R 777 logs/
chmod -R 777 uploads/
\`\`\`

#### Audio System Not Working
- Check browser audio permissions
- Verify TTS service configuration
- Test audio files in admin panel

### Getting Help
- **Documentation**: Check inline code documentation
- **Logs**: Review application and error logs
- **Diagnostics**: Use built-in diagnostic tools
- **Support**: Contact system administrator

## 🤝 Contributing

### Development Setup
\`\`\`bash
# Clone repository
git clone https://github.com/your-repo/yuwaprasart-queue-system.git

# Install dependencies
composer install

# Set up development environment
cp .env.example .env.dev

# Run tests
composer test
\`\`\`

### Code Standards
- **PSR-12** coding standards
- **PHPDoc** documentation for all functions
- **Unit Tests** for critical functionality
- **Security Review** for all changes

### Submitting Changes
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Bootstrap** for responsive UI framework
- **Chart.js** for analytics visualization
- **PHPMailer** for email functionality
- **mPDF** for PDF generation
- **Telegram Bot API** for notifications

## 📞 Support

For technical support or questions:
- **Email**: support@yuwaprasart.com
- **Documentation**: [Wiki](https://github.com/your-repo/yuwaprasart-queue-system/wiki)
- **Issues**: [GitHub Issues](https://github.com/your-repo/yuwaprasart-queue-system/issues)

---

**Made with ❤️ for Yuwaprasart Hospital**
