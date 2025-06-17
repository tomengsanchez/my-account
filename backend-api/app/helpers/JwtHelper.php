<?php
namespace helpers;

// Import the User model to use it as a type hint
use models\User;

/**
 * A helper class for handling JSON Web Tokens (JWT).
 * This class provides static methods to decode and validate tokens.
 */
class JwtHelper {

    /**
     * Decodes a JWT and returns its claims after basic validation.
     * It now also checks against a token revocation list via a dependency.
     *
     * @param string $token The JWT string.
     * @param User $userModel An instance of the User model to check for revocation.
     * @return object The claims (payload) of the token as an object.
     * @throws \Exception If the token is invalid, malformed, expired, or revoked.
     */
    public static function getClaims(string $token, User $userModel): object {
        // 1. Decode the JWT payload.
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            throw new \Exception('Invalid token format.');
        }
        $payload_base64 = $tokenParts[1];
        
        $payload_decoded = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload_base64));
        if ($payload_decoded === false) {
            throw new \Exception('Payload decoding failed.');
        }

        $claims = json_decode($payload_decoded);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Payload is not valid JSON.');
        }

        // 2. Validate the token's expiration time from the 'exp' claim.
        if (!isset($claims->exp) || time() > $claims->exp) {
            throw new \Exception('Token has expired.');
        }

        // 3. Ensure the token contains the 'sub' (subject) and 'jti' (JWT ID) claims.
        if (!isset($claims->sub)) {
            throw new \Exception('Token is missing the "sub" (subject) claim.');
        }
        if (!isset($claims->jti)) {
            throw new \Exception('Token is missing the "jti" (JWT ID) claim.');
        }

        // 4. Check if the token has been revoked using the injected dependency.
        if ($userModel->isTokenRevoked($claims->jti)) {
            throw new \Exception('Token has been revoked.');
        }
        
        // 5. If all checks pass, return the claims.
        return $claims;
    }
}