<?php

// This file returns a function that will be executed by the migration script.
// The database connection is passed to this function.

return function($db) {
    echo " -> Creating 'users' table...\n";

    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        
        -- This links our local profile to the main account on the OAuth server
        oauth_user_id VARCHAR(255) NULL UNIQUE,
        
        -- Local copy of the basic user profile
        username VARCHAR(255) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        first_name VARCHAR(255) NULL,
        last_name VARCHAR(255) NULL,
        
        -- Application-specific fields that are not on the OAuth server
        age INT NULL,
        address TEXT NULL,
        contact_number VARCHAR(50) NULL,
        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";

    $db->query($sql);
    $db->execute();

    echo " -> 'users' table created.\n";
};