<?php
// Migration for creating the revoked_tokens table
// Filename: 2025_06_16_0001_create_revoked_tokens_table.php

return new class {
    private ?PDO $pdo = null;

    /**
     * The constructor no longer requires a PDO object, preventing the fatal error.
     */
    public function __construct() {
        // The PDO connection will be injected via the setPdo method.
    }

    /**
     * Injects the database connection from the migration runner.
     * @param PDO $pdo An active PDO database connection.
     */
    public function setPdo(PDO $pdo): void {
        $this->pdo = $pdo;
    }

    /**
     * The 'up' method contains the SQL to create the table.
     */
    public function up() {
        if ($this->pdo === null) {
            throw new Exception("Database connection has not been set for migration.");
        }

        $sql = "
        CREATE TABLE IF NOT EXISTS revoked_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            jti VARCHAR(255) NOT NULL UNIQUE,
            expiry_time INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ";

        try {
            $this->pdo->exec($sql);
            echo "Migration 'create_revoked_tokens_table' successful." . PHP_EOL;
        } catch (PDOException $e) {
            echo "Error migrating 'create_revoked_tokens_table': " . $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * The 'down' method contains the SQL to drop the table.
     */
    public function down() {
        if ($this->pdo === null) {
            throw new Exception("Database connection has not been set for migration.");
        }
        
        $sql = "DROP TABLE IF EXISTS revoked_tokens;";

        try {
            $this->pdo->exec($sql);
            echo "Rollback for 'create_revoked_tokens_table' successful." . PHP_EOL;
        } catch (PDOException $e) {
            echo "Error rolling back 'create_revoked_tokens_table': " . $e->getMessage() . PHP_EOL;
        }
    }
};
