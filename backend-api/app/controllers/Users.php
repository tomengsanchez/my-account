<?php
namespace controllers;

class Users {
    private $userModel;

    public function __construct(){
        $this->userModel = new \models\User();
    }

    public function index(){ /* ... */ }

    /**
     * Handle user login by acting as a proxy to the OAuth server.
     * It forwards the username/password and returns the entire response
     * from the OAuth server, including the access_token on success or an error on failure.
     * Corresponds to the endpoint /backend-api/users/login
     */
    public function login() {
        // Get the raw POST data from the frontend
        $data = json_decode(file_get_contents("php://input"));

        // Validate input
        if (empty($data->username) || empty($data->password)) {
            http_response_code(400); // Bad Request
            echo json_encode(['message' => 'Username and password are required']);
            return;
        }

        // Prepare the body for the x-www-form-urlencoded request to the OAuth server
        $token_params = http_build_query([
            'grant_type' => 'password',
            'client_id' => \OAUTH_CLIENT_ID,
            'client_secret' => \OAUTH_CLIENT_SECRET,
            'username' => $data->username,
            'password' => $data->password,
            'scope' => 'basic profile users:create'
        ]);
        
        // ** FIX: The '/api/token' endpoint was returning a generic page.
        // Switched to '/oauth/token', a common standard for OAuth2 servers. **
        $token_url = \OAUTH_SERVER_URL . '/token';

        // Initialize cURL session to contact the OAuth server
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $token_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $token_params,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ],
            // TEMPORARY WORKAROUND: For servers with self-signed/invalid SSL certs.
            // This should be REMOVED in a production environment. Use a valid certificate.
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);

        // Execute the request and get the response and status code
        $response_body = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            // This handles network-level errors (e.g., cannot connect to server)
            http_response_code(500);
            error_log("Login Proxy cURL Error: " . $curl_error);
            echo json_encode(['error' => 'proxy_error', 'message' => 'Could not connect to the authentication server.']);
            return;
        }

        // --- Proxy the Response ---
        // Set the same HTTP status code that we received from the OAuth server.
        http_response_code($http_status);
        
        // Echo the exact response body (JSON) from the OAuth server back to the frontend.
        // This will contain the access_token on success or a detailed error on failure.
        echo $response_body;
    }
    
    /**
     * Acts as a secure proxy to the OAuth server's /token endpoint.
     */
    public function token() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->username) || !isset($data->password)) {
            http_response_code(400);
            echo json_encode(['message' => 'Username and password are required.']);
            return;
        }

        $token_params = http_build_query([
            'grant_type' => 'password',
            'client_id' => \OAUTH_CLIENT_ID,
            'client_secret' => \OAUTH_CLIENT_SECRET,
            'username' => $data->username,
            'password' => $data->password,
            'scope' => 'basic profile users:create'
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => \OAUTH_SERVER_URL . '/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $token_params,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/x-www-form-urlencoded"
            ],
        ]);
        
        $response_body = curl_exec($curl);
        error_log("Token Proxy cURL Response: " . $response_body);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);
        
        if ($curl_error) {
            http_response_code(500);
            error_log("Token Proxy cURL Error: " . $curl_error);
            echo json_encode(['error' => 'proxy_error', 'message' => 'Could not connect to the authentication server.']);
            return;
        }
        
        http_response_code($http_status);
        echo $response_body;
    }

    /**
     * Handles user registration.
     */
    public function register(){
        $data = json_decode(file_get_contents("php://input"));

        $required_fields = ['username', 'email', 'first_name', 'last_name', 'password'];
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (empty($data->$field)) {
                $missing_fields[] = $field;
            }
        }
        if (!empty($missing_fields)) {
            http_response_code(400);
            echo json_encode(['message' => 'Missing required fields.', 'missing_fields' => $missing_fields]);
            return;
        }

        if ($this->userModel->findUserByEmail($data->email)) {
            http_response_code(409);
            echo json_encode(['message' => 'An account with this email already exists.']);
            return;
        }
        if ($this->userModel->findUserByUsername($data->username)) {
            http_response_code(409);
            echo json_encode(['message' => 'This username is already taken.']);
            return;
        }

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
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $oauth_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'username: ' . \OAUTH_API_USERNAME,
                'password: ' . \OAUTH_API_PASSWORD,
            ]
        ]);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to connect to authentication server.', 'error' => $error]);
            return;
        }

        $oauth_response = json_decode($response);
        
        if ($http_status !== 201 && $http_status !== 200) {
            http_response_code($http_status);
            echo json_encode(['message' => 'Registration with OAuth server failed', 'details' => $oauth_response]);
            return;
        }

        $data->oauth_user_id = $oauth_response->user->id ?? $data->username;

        if ($this->userModel->register($data)) {
            http_response_code(201);
            echo json_encode(['message' => 'Registration successful. Please log in.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'User registration completed but local profile creation failed.']);
        }
    }
}
