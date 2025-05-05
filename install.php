<?php
echo "<h1>Voting System Installation Checker</h1>";

// Check PHP version
echo "<h2>Checking PHP Version...</h2>";
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo "✅ PHP Version: " . PHP_VERSION;
} else {
    echo "❌ PHP Version must be 7.4 or higher. Current: " . PHP_VERSION;
}

// Check MySQL
echo "<h2>Checking MySQL Connection...</h2>";
try {
    $conn = new PDO("mysql:host=localhost", "root", "");
    echo "✅ MySQL Connection Successful";
} catch (PDOException $e) {
    echo "❌ MySQL Connection Failed: " . $e->getMessage();
}

// Check required directories
echo "<h2>Checking Directories...</h2>";
$directories = ['uploads', 'config'];
foreach ($directories as $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        echo "✅ Directory '$dir' exists and is writable<br>";
    } else {
        echo "❌ Directory '$dir' check failed<br>";
    }
}

// Check required PHP extensions
echo "<h2>Checking PHP Extensions...</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'session'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension loaded<br>";
    } else {
        echo "❌ $ext extension not loaded<br>";
    }
}
