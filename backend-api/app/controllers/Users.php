<?php
namespace controllers;

class Users {
    private $userModel;

    public function __construct(){
        $this->userModel = new \models\User();
    }

    public function index(){ /* ... */ }

    public function register(){ /* ... */ }

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
