<?php
namespace models; // This model is in the 'models' namespace

/**
 * The User model handles all database interactions for the 'users' table.
 */
class User {
    private $db;

    public function __construct() {
        // Instantiate the Database class using its full namespace
        $this->db = new \core\Database;
    }

    public function findUserByEmail($email) {
        $this->db->query('SELECT * FROM users WHERE email = :email');
        $this->db->bind(':email', $email);
        $row = $this->db->single();
        return ($this->db->rowCount() > 0) ? $row : false;
    }

    public function findUserByUsername($username) {
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        $row = $this->db->single();
        return ($this->db->rowCount() > 0) ? $row : false;
    }

    public function findUserById($id) {
        $this->db->query('SELECT id, username, email, first_name, last_name, age, address, contact_number, created_at FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        return ($this->db->rowCount() > 0) ? $row : false;
    }

    public function findUserByOAuthId($oauth_id) {
        $this->db->query('SELECT * FROM users WHERE oauth_user_id = :oauth_id');
        $this->db->bind(':oauth_id', $oauth_id);
        $row = $this->db->single();
        return ($this->db->rowCount() > 0) ? $row : false;
    }
    
    /**
     * Checks if a given JWT ID (jti) exists in the revoked_tokens table.
     * @param string $jti The JWT ID from the token claims.
     * @return bool True if the token is revoked, false otherwise.
     */
    public function isTokenRevoked(string $jti): bool {
        // ** DEBUGGING: Add logging to see what is happening. **
        error_log("Checking revocation status for JTI: " . $jti);
        
        $this->db->query('SELECT COUNT(*) as count FROM revoked_tokens WHERE jti = :jti');
        $this->db->bind(':jti', $jti);
        
        $result = $this->db->single();
        error_log("Revocation check query result: " . print_r($result, true));

        $isRevoked = (int)$result->count > 0;
        error_log("Is token revoked? " . ($isRevoked ? 'Yes' : 'No'));
        
        return $isRevoked;
    }

    /**
     * Adds a token's JTI to the revocation list.
     * @param string $jti The JWT ID to revoke.
     * @param int $expiryTime The timestamp when the token expires.
     * @return bool True on success, false on failure.
     */
    public function revokeToken(string $jti, int $expiryTime): bool {
        // ** DEBUGGING: Add logging to see if the insert is successful. **
        error_log("Attempting to revoke token by inserting JTI: " . $jti);
        
        $this->db->query('INSERT INTO revoked_tokens (jti, expiry_time) VALUES (:jti, :expiry_time)');
        $this->db->bind(':jti', $jti);
        $this->db->bind(':expiry_time', $expiryTime);
        
        $success = $this->db->execute();
        if ($success) {
            error_log("Successfully inserted JTI into revoked_tokens table.");
        } else {
            error_log("Database execution failed for revoking JTI: " . $jti);
        }
        
        return $success;
    }

    public function register($data) {
        // Generate a unique oauth_user_id if not provided
        $oauth_user_id = $data->oauth_user_id ?? uniqid('user_', true);
        
        // Prepare the SQL query with all fields
        $this->db->query('INSERT INTO users (
            username, 
            email, 
            first_name, 
            last_name, 
            oauth_user_id,
            age,
            address,
            contact_number
        ) VALUES (
            :username, 
            :email, 
            :first_name, 
            :last_name, 
            :oauth_user_id,
            :age,
            :address,
            :contact_number
        )');
        
        // Bind all the values
        $this->db->bind(':username', trim($data->username));
        $this->db->bind(':email', trim($data->email));
        $this->db->bind(':first_name', trim($data->first_name));
        $this->db->bind(':last_name', trim($data->last_name));
        $this->db->bind(':oauth_user_id', $oauth_user_id);
        $this->db->bind(':age', !empty($data->age) ? intval($data->age) : null);
        $this->db->bind(':address', !empty($data->address) ? trim($data->address) : null);
        $this->db->bind(':contact_number', !empty($data->contact_number) ? trim($data->contact_number) : null);

        if ($this->db->execute()) {
            // Fetch the complete user record to return in the API response
            $lastId = $this->db->lastInsertId();
            return $this->findUserById($lastId);
        } else {
            error_log('Registration failed: ' . $this->db->error());
            return false;
        }
    }

    public function updateUsername($id, $username) {
        $this->db->query('UPDATE users SET username = :username WHERE id = :id');
        $this->db->bind(':id', $id);
        $this->db->bind(':username', $username);

        return $this->db->execute();
    }
}
