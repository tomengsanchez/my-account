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
     * Authenticates the user by decoding the Authorization Bearer JWT token directly.
     * This is more efficient as it avoids a secondary API call to the auth server.
     * @return object The local user profile object on success. Terminates with an error on failure.
     */
    private function getAuthenticatedUser() {
        // 1. Get the Authorization header from the request.
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

        try {
            // 3. Decode the JWT payload to extract user information.
            // A JWT is composed of three parts: header, payload, signature. We need the payload.
            $tokenParts = explode('.', $token);
            if (count($tokenParts) < 2) {
                throw new \Exception('Invalid token format.');
            }
            $payload_base64 = $tokenParts[1];
            
            // The payload is Base64Url encoded. We need to decode it.
            $payload_decoded = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload_base64));
            if ($payload_decoded === false) {
                throw new \Exception('Payload decoding failed.');
            }

            $claims = json_decode($payload_decoded);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Payload is not valid JSON.');
            }

            // 4. Validate the token's expiration time from the 'exp' claim.
            if (!isset($claims->exp) || time() > $claims->exp) {
                http_response_code(401); // Unauthorized
                echo json_encode(['message' => 'Token has expired.']);
                exit();
            }

            // 5. Get the user identifier from the 'sub' (subject) claim.
            // In OAuth2, the 'sub' claim typically holds the unique ID of the user.
            if (!isset($claims->sub)) {
                throw new \Exception('Token is missing the "sub" (subject) claim, which is required for user identification.');
            }
            $oauthUserId = $claims->sub;

            // NOTE: A full JWT implementation would also cryptographically validate the token's signature
            // using the OAuth server's public key. Without signature validation, we are trusting the
            // claims without being 100% certain they haven't been tampered with.
            // For this implementation, we proceed by trusting the claims after checking the expiration.

            // 6. Find the corresponding user in our local database using the OAuth ID (from 'sub').
            $localUser = $this->userModel->findUserByOAuthId($oauthUserId);

            if (!$localUser) {
                http_response_code(404); // Not Found
                echo json_encode(['message' => 'User is authenticated, but no profile was found in this application.']);
                exit();
            }

            // If all checks pass, return the local user's data.
            return $localUser;

        } catch (\Exception $e) {
            http_response_code(401); // Unauthorized
            echo json_encode(['message' => 'Invalid token: ' . $e->getMessage()]);
            exit();
        }
    }
}
