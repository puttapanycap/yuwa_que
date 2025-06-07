#!/bin/bash

echo "=== Yuwaprasart Queue System Installation ==="
echo ""

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    echo "Visit: https://getcomposer.org/download/"
    exit 1
fi

echo "✅ Composer found"

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "PHP Version: $PHP_VERSION"

# Check system requirements
echo ""
echo "🔍 Checking system requirements..."
php scripts/check-system-requirements.php

echo ""
echo "📦 Installing core dependencies..."

# Install core dependencies (ignore platform requirements for optional extensions)
composer install --ignore-platform-req=ext-zip --no-interaction

if [ $? -eq 0 ]; then
    echo "✅ Core dependencies installed successfully"
else
    echo "❌ Failed to install core dependencies"
    exit 1
fi

echo ""
echo "📦 Installing optional packages..."

# Install optional packages
php scripts/install-optional-packages.php

echo ""
echo "🔧 Setting up environment..."

# Copy .env file if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✅ .env file created from example"
else
    echo "✅ .env file already exists"
fi

# Create necessary directories
mkdir -p uploads logs cache backups
chmod 755 uploads logs cache backups
echo "✅ Directories created and permissions set"

# Generate autoloader
composer dump-autoload --optimize
echo "✅ Autoloader optimized"

echo ""
echo "🎉 Installation completed!"
echo ""
echo "Next steps:"
echo "1. Configure your .env file with database credentials"
echo "2. Import database schema: mysql -u username -p database_name < database/schema.sql"
echo "3. Configure web server to point to the project root"
echo "4. Access the system via web browser"
echo ""
echo "For ZIP extension installation:"
echo "- Windows (Laragon): Edit php.ini and uncomment 'extension=zip'"
echo "- Ubuntu: sudo apt-get install php-zip"
echo "- CentOS: sudo yum install php-zip"
echo ""
