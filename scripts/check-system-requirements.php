<?php
/**
 * System Requirements Checker
 * Checks if all required PHP extensions and dependencies are available
 */

echo "=== Yuwaprasart Queue System - System Requirements Check ===\n\n";

$requirements = [
    'php_version' => [
        'name' => 'PHP Version',
        'required' => '8.0.0',
        'current' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '8.0.0', '>=')
    ]
];

$extensions = [
    'pdo' => ['required' => true, 'description' => 'Database connectivity'],
    'json' => ['required' => true, 'description' => 'JSON processing'],
    'mbstring' => ['required' => true, 'description' => 'Multi-byte string handling'],
    'curl' => ['required' => true, 'description' => 'HTTP requests'],
    'gd' => ['required' => true, 'description' => 'Image processing'],
    'zip' => ['required' => false, 'description' => 'File compression (for Excel exports and backups)'],
    'imagick' => ['required' => false, 'description' => 'Advanced image processing'],
    'redis' => ['required' => false, 'description' => 'Caching and session storage'],
    'openssl' => ['required' => false, 'description' => 'Encryption and secure communications']
];

// Check PHP version
echo "PHP Version Check:\n";
echo "- Required: {$requirements['php_version']['required']}+\n";
echo "- Current: {$requirements['php_version']['current']}\n";
echo "- Status: " . ($requirements['php_version']['status'] ? "✅ OK" : "❌ FAIL") . "\n\n";

// Check extensions
echo "PHP Extensions Check:\n";
$requiredMissing = [];
$optionalMissing = [];

foreach ($extensions as $ext => $info) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? "✅ Loaded" : ($info['required'] ? "❌ Missing" : "⚠️  Optional");
    
    echo "- {$ext}: {$status} - {$info['description']}\n";
    
    if (!$loaded) {
        if ($info['required']) {
            $requiredMissing[] = $ext;
        } else {
            $optionalMissing[] = $ext;
        }
    }
}

echo "\n";

// Check writable directories
echo "Directory Permissions Check:\n";
$directories = ['uploads', 'logs', 'cache', 'backups'];
foreach ($directories as $dir) {
    $path = __DIR__ . "/../{$dir}";
    if (!file_exists($path)) {
        @mkdir($path, 0755, true);
    }
    $writable = is_writable($path);
    echo "- {$dir}/: " . ($writable ? "✅ Writable" : "❌ Not writable") . "\n";
}

echo "\n";

// Summary
if (empty($requiredMissing) && $requirements['php_version']['status']) {
    echo "🎉 System Requirements: PASSED\n";
    echo "Your system meets all required dependencies.\n";
    
    if (!empty($optionalMissing)) {
        echo "\n📝 Optional Extensions Missing:\n";
        foreach ($optionalMissing as $ext) {
            echo "- {$ext}: {$extensions[$ext]['description']}\n";
        }
        echo "\nThese extensions are optional but recommended for full functionality.\n";
    }
} else {
    echo "❌ System Requirements: FAILED\n";
    
    if (!$requirements['php_version']['status']) {
        echo "- PHP version {$requirements['php_version']['required']}+ is required\n";
    }
    
    if (!empty($requiredMissing)) {
        echo "- Missing required extensions: " . implode(', ', $requiredMissing) . "\n";
    }
    
    echo "\nPlease install missing requirements before proceeding.\n";
}

echo "\n=== Installation Instructions ===\n";

if (in_array('zip', $requiredMissing) || in_array('zip', $optionalMissing)) {
    echo "\nTo enable ZIP extension:\n";
    echo "1. Windows (Laragon/XAMPP): Uncomment 'extension=zip' in php.ini\n";
    echo "2. Ubuntu/Debian: sudo apt-get install php-zip\n";
    echo "3. CentOS/RHEL: sudo yum install php-zip\n";
    echo "4. macOS (Homebrew): brew install php@8.3 (usually included)\n";
}

echo "\nComposer Installation Options:\n";
echo "1. Install with all features: composer install\n";
echo "2. Install without ZIP: composer install --ignore-platform-req=ext-zip\n";
echo "3. Install core only: composer install --no-suggest\n";

echo "\n=== End of Check ===\n";
?>
