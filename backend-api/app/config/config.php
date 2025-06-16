<?php
// Set the default timezone for the entire application
// This will ensure all date and time functions use the correct timezone.
date_default_timezone_set('Asia/Manila');

// Database Parameters
define('DB_TYPE', 'mysql'); // Change this to your database type (e.g., mysql, pgsql, etc.)
Define('DB_HOST', 'localhost');
define('DB_USER', 'ms1user');  // Change this to your database username
define('DB_PASS', 'mainsystem#67');      // Change this to your database password            
define('DB_NAME', 'etc_frontend_dev'); // Change this to your database name
// Application Root
define('APPROOT', dirname(dirname(__FILE__)));
// URL Root
define('URLROOT', 'https://ithelp.ecosyscorp.ph/etc-backend/public/');
// Site Name
define('SITENAME', 'My Ecosys Account');

// --- OAuth Server Configuration ---
// The base URL for the central authentication server
define('OAUTH_SERVER_URL', 'https://ithelp.ecosyscorp.ph/etc-backend');

// API Endpoints on the OAuth server
define('OAUTH_REGISTER_ENDPOINT', '/api/register');
define('OAUTH_TOKEN_ENDPOINT', '/token'); 

// Your client ID for the OAuth server
define('OAUTH_CLIENT_ID', 'testclient');
define('OAUTH_CLIENT_SECRET', 'testsecret'); 

// Define the default scope for token requests in one place.
define('OAUTH_DEFAULT_SCOPE', 'profile users:read users:create users:update users:delete clients:create');

// --- Login Credentials for API-to-API communication (if required by OAuth server) ---
// These credentials might be used by this backend to authenticate itself with the OAuth server.
define('OAUTH_API_USERNAME', 'tomeng');
define('OAUTH_API_PASSWORD', 'tomeng');
