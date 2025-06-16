<?php
namespace controllers;

/**
 * Handles fetching the authenticated user's profile.
 * This is a protected resource and requires a valid Authorization Bearer token.
 */
class Profile {
    private $userModel;

    public function __construct(){
        // Instantiate the User model for use in the controller.
        $this->userModel = new \models\User();
    }

    /**
     * Default method to fetch the logged-in user's profile.
     * Corresponds to the endpoint /backend-api/profile
     * Authentication is handled internally by the getAuthenticatedUser method.
     */
    public function index(){
        // First, authenticate the request and get the local user profile.
        $localUser = $this->getAuthenticatedUser();
        
        // If authentication was successful, $localUser contains the full user object
        // from our local database. We can now return it to the client.
        http_response_code(200);
        echo json_encode($localUser);
    }
    
    /**
     * Authenticates the user based on the Authorization Bearer token.
     * It validates the token with the OAuth server and fetches the corresponding local user.
     * @return object The local user profile object on success. Terminates with an error on failure.
     */
    private function getAuthenticatedUser() {
        // 1. Get the Authorization header from the request.
        // Using apache_request_headers() provides compatibility across different server setups.
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader) {
            http_response_code(401); // Unauthorized
            echo json_encode(['message' => 'Authorization header missing.']);
            exit();
        }

        // 2. Extract the Bearer token from the header string.
        if (sscanf($authHeader, 'Bearer %s', $token) !== 1) {
             http_response_code(401); // Unauthorized
             echo json_encode(['message' => 'Bearer token is malformed.']);
             exit();
        }

        // 3. Verify the token with the OAuth server's user info endpoint.
        // It's a standard OAuth practice for the auth server to provide an endpoint
        // (e.g., '/api/user' or '/me') that returns user info for a valid token.
        // We will assume the endpoint is '/user'.
        $userInfoUrl = \OAUTH_SERVER_URL . '/user';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $userInfoUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token
            ],
            // TEMPORARY WORKAROUND: For servers with self-signed/invalid SSL certs.
            // This should be REMOVED in a production environment. Use a valid certificate.
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log('Token validation cURL error: ' . $error);
            http_response_code(500);
            echo json_encode(['message' => 'Error contacting the authentication server.']);
            exit();
        }

        // 4. Handle the response from the OAuth server.
        // If the status is not 200, the token is invalid or expired.
        if ($http_status !== 200) {
            http_response_code(401); // Unauthorized
            echo json_encode(['message' => 'Token is invalid or expired.', 'auth_server_response' => json_decode($response)]);
            exit();
        }

        $oauthUser = json_decode($response);
        
        // The OAuth server must return a unique identifier for the user.
        // Based on your 'register' method, this identifier is named 'id'.
        if (!isset($oauthUser->id)) {
            http_response_code(500);
            error_log('OAuth response is missing user ID: ' . $response);
            echo json_encode(['message' => 'Could not identify user from token response.']);
            exit();
        }

        // 5. Find the corresponding user in our local database using the OAuth ID.
        $localUser = $this->userModel->findUserByOAuthId($oauthUser->id);

        if (!$localUser) {
            // This case means the user is authenticated correctly with the auth server,
            // but for some reason, they don't have a profile in this specific application's database.
            http_response_code(404); // Not Found
            echo json_encode(['message' => 'User is authenticated, but no profile was found in this application.']);
            exit();
        }

        // If all checks pass, return the local user's data.
        return $localUser;
    }
}
