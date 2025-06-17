<?php
namespace controllers;

// Import the helper to use it for logout
use helpers\JwtHelper;

class Users {
    private $userModel;

    public function __construct(){
        $this->userModel = new \models\User();
    }

    public function index(){ /* ... */ }

    /**
     * Handle user login by acting as a proxy to the OAuth server.
     */
    public function login() {
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->username) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(['message' => 'Username and password are required']);
            return;
        }

        $token_params = http_build_query([
            'grant_type' => 'password',
            'client_id' => \OAUTH_CLIENT_ID,
            'client_secret' => \OAUTH_CLIENT_SECRET,
            'username' => $data->username,
            'password' => $data->password,
            'scope' => \OAUTH_DEFAULT_SCOPE
        ]);
        
        $token_url = \OAUTH_SERVER_URL . \OAUTH_TOKEN_ENDPOINT;

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
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);

        $response_body = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            http_response_code(500);
            echo json_encode(['error' => 'proxy_error', 'message' => 'Could not connect to the authentication server.']);
            return;
        }

        http_response_code($http_status);
        echo $response_body;
    }
    
    /**
     * Handles user logout by revoking the provided access token.
     * This endpoint requires a valid Bearer token in the Authorization header.
     */
    public function logout() {
        // 1. Get the token from the Authorization header.
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$authHeader || sscanf($authHeader, 'Bearer %s', $token) !== 1) {
             http_response_code(401);
             echo json_encode(['message' => 'Authorization token not found or malformed.']);
             exit();
        }

        try {
            // 2. Decode the token to get its claims (jti and exp).
            // We use our helper, which also validates the token format and expiration.
            $claims = JwtHelper::getClaims($token);

            // 3. Add the token's JTI to the revocation list in the database.
            if ($this->userModel->revokeToken($claims->jti, $claims->exp)) {
                http_response_code(200);
                echo json_encode(['message' => 'Logout successful. Token has been revoked.']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to revoke token.']);
            }

        } catch (\Exception $e) {
            // This catches errors from the JwtHelper (e.g., if token is already expired or revoked).
            http_response_code(401);
            echo json_encode(['message' => 'Invalid token provided for logout: ' . $e->getMessage()]);
        }
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
            'scope' => \OAUTH_DEFAULT_SCOPE
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
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
            http_response_code(500);
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
        if (count(array_intersect($required_fields, array_keys((array)$data))) !== count($required_fields)) {
            http_response_code(400);
            echo json_encode(['message' => 'Missing required fields.']);
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
        $post_data = json_encode($data);
        
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
