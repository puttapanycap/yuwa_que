@echo off
echo === Yuwaprasart Queue System Installation ===
echo.

REM Check if composer is installed
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ Composer is not installed. Please install Composer first.
    echo Visit: https://getcomposer.org/download/
    pause
    exit /b 1
)

echo ✅ Composer found

REM Check PHP version
for /f "tokens=*" %%i in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%i
echo PHP Version: %PHP_VERSION%

echo.
echo 🔍 Checking system requirements...
php scripts/check-system-requirements.php

echo.
echo 📦 Installing core dependencies...

REM Install core dependencies (ignore platform requirements for optional extensions)
composer install --ignore-platform-req=ext-zip --no-interaction

if %errorlevel% neq 0 (
    echo ❌ Failed to install core dependencies
    pause
    exit /b 1
)

echo ✅ Core dependencies installed successfully

echo.
echo 📦 Installing optional packages...

REM Install optional packages
php scripts/install-optional-packages.php

echo.
echo 🔧 Setting up environment...

REM Copy .env file if it doesn't exist
if not exist .env (
    copy .env.example .env
    echo ✅ .env file created from example
) else (
    echo ✅ .env file already exists
)

REM Create necessary directories
if not exist uploads mkdir uploads
if not exist logs mkdir logs
if not exist cache mkdir cache
if not exist backups mkdir backups
echo ✅ Directories created

REM Generate autoloader
composer dump-autoload --optimize
echo ✅ Autoloader optimized

echo.
echo 🎉 Installation completed!
echo.
echo Next steps:
echo 1. Configure your .env file with database credentials
echo 2. Import database schema: mysql -u username -p database_name ^< database/schema.sql
echo 3. Configure web server to point to the project root
echo 4. Access the system via web browser
echo.
echo For ZIP extension installation in Laragon:
echo 1. Open C:\laragon\bin\php\php-8.3.12\php.ini
echo 2. Find ;extension=zip and remove the semicolon
echo 3. Restart Laragon
echo.
pause
