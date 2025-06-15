<?php
// Include configuration and database class
require_once 'config.php';
require_once 'app/core/Database.php';

echo "Migration script started.\n";

// Initialize Database
$db = new core\Database();

// 1. Create migrations table if it doesn't exist
try {
    $db->query("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $db->execute();
    echo "Checked/created the 'migrations' table successfully.\n";
} catch (PDOException $e) {
    die("Error creating migrations table: " . $e->getMessage() . "\n");
}

// 2. Get all migrations that have already been run
$db->query("SELECT migration FROM migrations");
$ranMigrations = $db->resultSet();
$ranMigrationsFiles = array_map(function($m) { return $m->migration; }, $ranMigrations);
echo "Found " . count($ranMigrationsFiles) . " existing migrations in the database.\n";


// 3. Get all migration files from the /migrations directory
$migrationFilesPath = __DIR__ . '/migrations/';
$allFiles = glob($migrationFilesPath . '*.php');
echo "Found " . count($allFiles) . " migration files on disk.\n";

// 4. Determine which migrations to run
$migrationsToRun = [];
foreach ($allFiles as $file) {
    $filename = basename($file);
    if (!in_array($filename, $ranMigrationsFiles)) {
        $migrationsToRun[] = $file;
    }
}

if (empty($migrationsToRun)) {
    die("No new migrations to run. Database is up to date.\n");
}

echo "Will run the following " . count($migrationsToRun) . " new migration(s):\n";
foreach($migrationsToRun as $file) {
    echo " - " . basename($file) . "\n";
}
echo "----------------------------------------\n";


// 5. Run the migrations
foreach ($migrationsToRun as $file) {
    $filename = basename($file);
    echo "Running migration: {$filename}...\n";
    
    try {
        // Each migration file is expected to return a function
        $migrationFunction = require $file;
        // We pass the database connection to the migration function
        $migrationFunction($db);

        // If successful, record it in the migrations table
        $db->query("INSERT INTO migrations (migration) VALUES (:migration)");
        $db->bind(':migration', $filename);
        $db->execute();

        echo "SUCCESS: {$filename} migrated and recorded successfully.\n";
    } catch (Exception $e) {
        die("\nERROR running migration {$filename}: " . $e->getMessage() . "\n");
    }
}

echo "----------------------------------------\n";
echo "Migration script finished successfully.\n";