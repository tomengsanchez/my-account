<?php
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
// The base URL for the central authentication server API
define('OAUTH_SERVER_URL', 'https://ithelp.ecosyscorp.ph/etc-backend/api');
define('OAUTH_REGISTER_ENDPOINT', '/register');
define('OAUTH_TOKEN_ENDPOINT', '/token'); // Token endpoint



// Your client ID for the OAuth server
define('OAUTH_CLIENT_ID', '963de905f648fe9637b898395b2de346ecd1b213');
define('OAUTH_CLIENT_SECRET', 'testpass'); // This secret is now stored securely on the backend.
// --- Login Credentials for API-to-API communication (if required by OAuth server) ---
// These credentials might be used by this backend to authenticate itself with the OAuth server.
// Based on your cURL example, these are sent as headers.
define('OAUTH_API_USERNAME', 'tomeng');
define('OAUTH_API_PASSWORD', 'tomeng');

