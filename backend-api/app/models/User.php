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
        // ** FIX: Select all relevant user fields. **
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
