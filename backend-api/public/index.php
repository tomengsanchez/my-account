<?php
// Set a local error log for easier debugging
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error_log.txt');

// Set the default content type for all responses
header("Content-Type: application/json");

// ** FIX: Correct path to the configuration file **
// The config file is located in the 'app/config' directory.
require_once '../app/config/config.php';

/**
 * Autoloader for all classes in the application.
 * This function automatically includes class files when they are first used.
 */
spl_autoload_register(function ($className) {
    // Convert namespace backslashes to directory forward slashes
    $classPath = str_replace('\\', '/', $className);
    $file = '../app/' . $classPath . '.php';

    if (file_exists($file)) {
        require_once $file;
    } else {
        error_log("Autoloader failed to find class file: " . $file);
    }
});

// Initialize the Core application router.
$app = new core\Core();
