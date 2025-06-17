<?php
// A simple migration runner
// To run from the command line: php migrate.php

// Force PHP to display all errors to the console for debugging.
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Migration script started." . PHP_EOL;

// 1. Load application configuration
require_once __DIR__ . '/app/config/config.php';

// Define the absolute path to the migrations directory
define('MIGRATIONS_PATH', __DIR__ . '/migrations');

// 2. Connect to the database
try {
    $pdo = new PDO(
        \DB_TYPE . ':host=' . \DB_HOST . ';dbname=' . \DB_NAME,
        \DB_USER,
        \DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Database connection successful." . PHP_EOL;
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 3. Ensure the 'migrations' tracking table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Checked/created the 'migrations' table successfully." . PHP_EOL;
} catch (PDOException $e) {
    die("Could not create or check for migrations table: " . $e->getMessage());
}

// 4. Get all migrations that have already been run from the database
try {
    $stmt = $pdo->query("SELECT migration FROM migrations");
    $run_migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($run_migrations) . " existing migrations in the database." . PHP_EOL;
} catch (PDOException $e) {
    die("Could not fetch ran migrations: " . $e->getMessage());
}

// 5. Get all available migration files from the migrations directory on disk
if (!is_dir(MIGRATIONS_PATH)) {
    die("Migrations directory not found at " . MIGRATIONS_PATH);
}
$migration_files = array_diff(scandir(MIGRATIONS_PATH), ['.', '..']);
echo "Found " . count($migration_files) . " migration files on disk." . PHP_EOL;

// 6. Determine which new migrations need to be run
$migrations_to_run = array_diff($migration_files, $run_migrations);

if (empty($migrations_to_run)) {
    echo "Database is already up to date." . PHP_EOL;
    exit;
}

echo "Will run the following " . count($migrations_to_run) . " new migration(s):" . PHP_EOL;
foreach ($migrations_to_run as $migration) {
    echo " - " . $migration . PHP_EOL;
}

// 7. Run the new migrations
foreach ($migrations_to_run as $migrationFile) {
    echo "----------------------------------------" . PHP_EOL;
    echo "Running migration: {$migrationFile}..." . PHP_EOL;
    
    try {
        $filePath = MIGRATIONS_PATH . '/' . $migrationFile;
        echo "Including file: " . $filePath . PHP_EOL;
        
        // ** FIX: Correctly handle the migration object. **
        // 1. The 'require' statement returns the instantiated migration object.
        $migration = require $filePath;
        echo "File included successfully. Injecting database connection..." . PHP_EOL;
        
        // 2. Inject the PDO dependency into the object.
        $migration->setPdo($pdo);
        echo "Connection injected. Running 'up' method..." . PHP_EOL;
        
        // 3. Run the 'up' method on the instance.
        $migration->up();
        echo "'up' method completed. Recording migration..." . PHP_EOL;
        
        // 4. Record the successful migration in the database.
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migrationFile]);
        echo "Migration recorded in database." . PHP_EOL;

    } catch (Throwable $e) { // Catch Throwable to grab Fatal errors as well
        echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!" . PHP_EOL;
        echo "An error occurred during migration '{$migrationFile}':" . PHP_EOL;
        echo "Error: " . $e->getMessage() . PHP_EOL;
        echo "File: " . $e->getFile() . PHP_EOL;
        echo "Line: " . $e->getLine() . PHP_EOL;
        echo "Migration process stopped." . PHP_EOL;
        echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!" . PHP_EOL;
        exit;
    }
}

echo "----------------------------------------" . PHP_EOL;
echo "All new migrations have been run successfully." . PHP_EOL;
