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
     * Authenticates the user by decoding and validating the Authorization Bearer JWT.
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
            // 3. Decode the JWT and validate its signature.
            $tokenParts = explode('.', $token);
            if (count($tokenParts) !== 3) {
                throw new \Exception('Invalid token format.');
            }
            
            $header_base64 = $tokenParts[0];
            $payload_base64 = $tokenParts[1];
            $signature_base64 = $tokenParts[2];

            // Decode header and payload
            $header = json_decode($this->base64url_decode($header_base64));
            $payload = json_decode($this->base64url_decode($payload_base64));

            if (!$header || !$payload) {
                throw new \Exception('Invalid token encoding.');
            }

            // 4. Get the public key from the configuration and verify the signature.
            $publicKeyPem = OAUTH_PUBLIC_KEY;

            // SECURITY CHECK: Ensure the placeholder key in the config file has been replaced.
            if (strpos($publicKeyPem, '...') !== false) {
                throw new \Exception('The OAUTH_PUBLIC_KEY in app/config/config.php must be configured. The current key is only a placeholder.');
            }
            
            $dataToVerify = $header_base64 . '.' . $payload_base64;
            $signature = $this->base64url_decode($signature_base64);
            
            // The algorithm should be checked from the token header, defaulting to RS256
            $alg = $header->alg ?? 'RS256';
            if ($alg !== 'RS256') {
                throw new \Exception('Unsupported signing algorithm: ' . htmlspecialchars($alg));
            }

            $verificationResult = openssl_verify($dataToVerify, $signature, $publicKeyPem, 'sha256');

            if ($verificationResult !== 1) {
                 throw new \Exception('Signature verification failed.');
            }
            
            // 5. Validate the token's expiration time from the 'exp' claim.
            if (!isset($payload->exp) || time() > $payload->exp) {
                http_response_code(401); // Unauthorized
                echo json_encode(['message' => 'Token has expired.']);
                exit();
            }

            // 6. Check if the token has been revoked.
            if (!isset($payload->jti)) {
                throw new \Exception('Token is missing the "jti" (JWT ID) claim, which is required for revocation checks.');
            }
            if ($this->userModel->isTokenRevoked($payload->jti)) {
                http_response_code(401); // Unauthorized
                echo json_encode(['message' => 'Token has been revoked. Please log in again.']);
                exit();
            }

            // 7. Get the user identifier from the 'sub' (subject) claim.
            if (!isset($payload->sub)) {
                throw new \Exception('Token is missing the "sub" (subject) claim, which is required for user identification.');
            }
            $oauthUserId = $payload->sub;

            // 8. Find the corresponding user in our local database using the OAuth ID (from 'sub').
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

    /**
     * Decodes a Base64Url encoded string.
     * @param string $data The Base64Url encoded string.
     * @return string The decoded string.
     */
    private function base64url_decode(string $data): string {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
