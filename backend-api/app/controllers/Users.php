<?php
namespace controllers;

class Users {
    private $userModel;

    public function __construct(){
        $this->userModel = new \models\User();
    }

    public function index(){ /* ... */ }

    /**
     * Handle user login by authenticating with OAuth server
     * Expects JSON payload with username and password
     */
    public function login() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get the raw POST data
        $data = json_decode(file_get_contents("php://input"));

        // Validate input
        if (empty($data->username) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(['message' => 'Username and password are required']);
            return;
        }

        // Prepare token request data
        $token_url = \OAUTH_SERVER_URL . \OAUTH_TOKEN_ENDPOINT;
        
        $post_data = http_build_query([
            'grant_type' => 'password',
            'client_id' => \OAUTH_CLIENT_ID,
            'client_secret' => \OAUTH_CLIENT_SECRET,
            'username' => $data->username,
            'password' => $data->password,
            'scope' => ''
        ]);

        // Initialize cURL session
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $token_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false, // Only for testing, remove in production
            CURLOPT_SSL_VERIFYHOST => 0      // Only for testing, remove in production
        ]);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Log the response for debugging
        error_log('OAuth token response status: ' . $http_status);
        error_log('OAuth token response: ' . $response);

        if ($error) {
            error_log('cURL error: ' . $error);
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to connect to authentication server',
                'error' => $error
            ]);
            return;
        }

        $token_data = json_decode($response);

        if ($http_status !== 200) {
            $message = $token_data->message ?? 'Authentication failed';
            http_response_code(401);
            echo json_encode([
                'message' => $message,
                'details' => $token_data->error_description ?? null
            ]);
            return;
        }

        // Store token in session
        $_SESSION['access_token'] = $token_data->access_token;
        $_SESSION['token_type'] = $token_data->token_type;
        $_SESSION['expires_in'] = time() + $token_data->expires_in;
        
        // Get user info (if provided in token or make additional request if needed)
        $user_info = [
            'username' => $data->username
            // Add more user info if available in token or make additional request
        ];

        // Return success response with token info
        http_response_code(200);
        echo json_encode([
            'message' => 'Login successful',
            'token_type' => $token_data->token_type,
            'expires_in' => $token_data->expires_in,
            'user' => $user_info
        ]);
    }

    public function register(){
        // Get the raw POST data from the request
        $data = json_decode(file_get_contents("php://input"));

        // --- Input Validation ---
        // Check for required fields
        $required_fields = ['username', 'email', 'first_name', 'last_name', 'password'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($data->$field)) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'message' => 'Missing required fields for registration.',
                'missing_fields' => $missing_fields
            ]);
            return;
        }

        // --- Check for Existing User ---
        if ($this->userModel->findUserByEmail($data->email)) {
            http_response_code(409); // Conflict
            echo json_encode(['message' => 'An account with this email already exists.']);
            return;
        }

        if ($this->userModel->findUserByUsername($data->username)) {
            http_response_code(409); // Conflict
            echo json_encode(['message' => 'This username is already taken.']);
            return;
        }

        // --- Register user in OAuth server ---
        $oauth_register_data = [
            'name' => $data->first_name . ' ' . $data->last_name,
            'email' => $data->email,
            'username' => $data->username,
            'password' => $data->password,
            'password_confirmation' => $data->password,
            'first_name' => $data->first_name,
            'last_name' => $data->last_name
        ];

        // Prepare the registration data for OAuth server
        $oauth_url = \OAUTH_SERVER_URL . \OAUTH_REGISTER_ENDPOINT;
        
        $post_data = json_encode([
            'client_id' => \OAUTH_CLIENT_ID,
            'username' => $data->username,
            'password' => $data->password,
            'first_name' => $data->first_name,
            'last_name' => $data->last_name,
            'email' => $data->email,
            'age' => !empty($data->age) ? intval($data->age) : null,
            'address' => $data->address ?? null,
            'contact_number' => $data->contact_number ?? null
        ]);
        
        // Log the request details
        error_log('Attempting to register user with OAuth server at: ' . $oauth_url);
        error_log('Request data: ' . $post_data);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $oauth_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, // Reduced timeout to 30 seconds
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'username: ' . \OAUTH_API_USERNAME,
                'password: ' . \OAUTH_API_PASSWORD,
                'first_name: ' . $data->first_name,
                'last_name: ' . $data->last_name,
                'email: ' . $data->email
            ]
        ]);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        // Log the response for debugging
        error_log('OAuth server response status: ' . $http_status);
        error_log('OAuth server response: ' . $response);
        
        if ($error) {
            error_log('cURL error: ' . $error);
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to connect to authentication server.',
                'error' => $error
            ]);
            return;
        }

        $oauth_response = json_decode($response);
        
        if ($http_status !== 201 && $http_status !== 200) {
            $message = $oauth_response->message ?? 'Registration with OAuth server failed';
            if (isset($oauth_response->errors)) {
                $message = implode(' ', array_map(function($error) {
                    return is_array($error) ? implode(' ', $error) : $error;
                }, (array)$oauth_response->errors));
            }
            error_log('OAuth registration failed with status ' . $http_status . ': ' . $message);
            http_response_code($http_status);
            echo json_encode([
                'message' => $message,
                'status' => $http_status,
                'response' => $oauth_response
            ]);
            return;
        }

        // Add OAuth user ID to our data
        $data->oauth_user_id = $oauth_response->user->id ?? $data->username;

        // --- Store user in local database ---
        $newUser = $this->userModel->register($data);

        if ($newUser) {
            // Get an access token for the new user
            $token_params = [
                'grant_type' => 'password',
                'client_id' => \OAUTH_CLIENT_ID,
                'client_secret' => \OAUTH_CLIENT_SECRET,
                'username' => $data->username,
                'password' => $data->password,
                'scope' => ''
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => \OAUTH_SERVER_URL . \OAUTH_TOKEN_ENDPOINT,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($token_params),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json'
                ]
            ]);

            $token_response = curl_exec($ch);
            $token_http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Registration successful
            http_response_code(201); // Created
            echo json_encode([
                'message' => 'Registration successful',
                'user' => $newUser,
                'token' => $token_http_status === 200 ? json_decode($token_response) : null
            ]);
        } else {
            // Local registration failed, but OAuth registration succeeded
            http_response_code(500);
            echo json_encode(['message' => 'User registration completed but local profile creation failed.']);
        }
    }

    /**
     * Acts as a secure proxy to the OAuth server's /token endpoint.
     * The frontend sends username/password to this method, and this method adds the
     * secret client_id/client_secret before forwarding the request.
     */
    public function token() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->username) || !isset($data->password)) {
            http_response_code(400);
            echo json_encode(['message' => 'Username and password are required.']);
            return;
        }

        // Prepare the body for the x-www-form-urlencoded request
        $token_params = http_build_query([
            'grant_type' => 'password',
            'client_id' => \OAUTH_CLIENT_ID,
            'client_secret' => \OAUTH_CLIENT_SECRET,
            'username' => $data->username,
            'password' => $data->password
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            // ** FIX: Added backslash to access the global constant from within the namespace. **
            CURLOPT_URL => \OAUTH_SERVER_URL . \OAUTH_TOKEN_ENDPOINT,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $token_params,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/x-www-form-urlencoded"
            ],
        ]);
        
        $response_body = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);
        
        if ($curl_error) {
            // If the cURL request itself fails (e.g., network issue)
            http_response_code(500);
            error_log("Token Proxy cURL Error: " . $curl_error);
            echo json_encode(['error' => 'proxy_error', 'message' => 'Could not connect to the authentication server.']);
            return;
        }
        
        if (empty($response_body)) {
            // If the auth server returns an empty response
            http_response_code(502); // Bad Gateway
            error_log("Token Proxy Error: Empty response from OAuth server with status " . $http_status);
            echo json_encode(['error' => 'empty_response', 'message' => 'The authentication server returned an empty response.']);
            return;
        }

        // Directly forward the valid response (and status code) from the auth server to our frontend.
        http_response_code($http_status);
        echo $response_body;
    }
    
    public function session() { /* ... */ }

    public function update() { /* ... */ }
}
