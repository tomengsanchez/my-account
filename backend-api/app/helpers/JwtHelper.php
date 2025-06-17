<?php
namespace helpers;

/**
 * A helper class for handling JSON Web Tokens (JWT).
 * This class provides static methods to decode and validate tokens.
 */
class JwtHelper {

    /**
     * Decodes a JWT and returns its claims after basic validation.
     * It now also checks against a token revocation list.
     *
     * @param string $token The JWT string.
     * @return object The claims (payload) of the token as an object.
     * @throws \Exception If the token is invalid, malformed, expired, or revoked.
     */
    public static function getClaims(string $token): object {
        // ** DEBUGGING: Add a die() statement to force a server cache refresh. **
        // If you see this message when you call /profile, the file has been updated on the server.
        // If you still see the profile data, the server is running an old, cached version of this file.
        // die('DEBUG: JwtHelper file has been updated. Cache should be clear.');

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

        // 4. Check if the token has been revoked.
        $userModel = new \models\User(); 
        if ($userModel->isTokenRevoked($claims->jti)) {
            throw new \Exception('Token has been revoked.');
        }
        
        // 5. If all checks pass, return the claims.
        return $claims;
    }
}
