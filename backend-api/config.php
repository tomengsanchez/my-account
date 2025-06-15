<?php
// Database Params
define('DB_TYPE', 'mysql'); // Change this to your database type (e.g., mysql, pgsql, etc.)
Define('DB_HOST', 'localhost');
define('DB_USER', 'ms1user');  // Change this to your database username
define('DB_PASS', 'mainsystem#67');      // Change this to your database password            
define('DB_NAME', 'etc_frontend_dev'); // Change this to your database name

// User Registration Fields
// This allows us to easily see what fields are expected for user creation.
define('USER_REGISTRATION_FIELDS', [
    'username',
    'first_name',
    'last_name',
    'email',
    'password'
]);