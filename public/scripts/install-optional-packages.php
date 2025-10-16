<?php
/**
 * Optional Package Installer
 * Installs optional packages based on available system extensions
 */

echo "=== Installing Optional Packages ===\n\n";

$optionalPackages = [
    'phpoffice/phpspreadsheet' => [
        'requires' => ['zip'],
        'description' => 'Excel file processing',
        'version' => '^1.29'
    ],
    'aws/aws-sdk-php' => [
        'requires' => ['curl', 'openssl'],
        'description' => 'AWS cloud services integration',
        'version' => '^3.281'
    ],
    'microsoft/azure-storage-blob' => [
        'requires' => ['curl', 'openssl'],
        'description' => 'Azure blob storage integration',
        'version' => '^1.5'
    ],
    'predis/predis' => [
        'requires' => [],
        'description' => 'Redis client for caching',
        'version' => '^2.2'
    ]
];

function checkExtensions($extensions) {
    foreach ($extensions as $ext) {
        if (!extension_loaded($ext)) {
            return false;
        }
    }
    return true;
}

function runComposerCommand($command) {
    echo "Running: {$command}\n";
    $output = [];
    $returnCode = 0;
    exec($command . ' 2>&1', $output, $returnCode);
    
    foreach ($output as $line) {
        echo "  {$line}\n";
    }
    
    return $returnCode === 0;
}

$installed = [];
$skipped = [];

foreach ($optionalPackages as $package => $info) {
    echo "Checking {$package}...\n";
    
    if (checkExtensions($info['requires'])) {
        echo "  ✅ Requirements met, installing...\n";
        $command = "composer require {$package}:{$info['version']} --no-interaction";
        
        if (runComposerCommand($command)) {
            $installed[] = $package;
            echo "  ✅ {$package} installed successfully\n";
        } else {
            $skipped[] = $package . " (installation failed)";
            echo "  ❌ {$package} installation failed\n";
        }
    } else {
        $missing = array_filter($info['requires'], function($ext) {
            return !extension_loaded($ext);
        });
        $skipped[] = $package . " (missing: " . implode(', ', $missing) . ")";
        echo "  ⚠️  Skipped - missing extensions: " . implode(', ', $missing) . "\n";
    }
    
    echo "\n";
}

echo "=== Installation Summary ===\n";
echo "Installed packages: " . count($installed) . "\n";
foreach ($installed as $package) {
    echo "  ✅ {$package}\n";
}

echo "\nSkipped packages: " . count($skipped) . "\n";
foreach ($skipped as $package) {
    echo "  ⚠️  {$package}\n";
}

echo "\n=== Next Steps ===\n";
echo "1. Run 'composer dump-autoload' to refresh autoloader\n";
echo "2. Check system requirements: php scripts/check-system-requirements.php\n";
echo "3. Configure your .env file\n";
echo "4. Run database migrations\n";

echo "\n=== Installation Complete ===\n";
?>
