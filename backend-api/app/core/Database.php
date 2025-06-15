<?php
namespace core; // This class is in the 'core' namespace

use PDO;
use PDOException;

/**
 * PDO Database Class
 * Connects to the database and provides methods for binding values,
 * executing queries, and fetching results.
 */
class Database {
    private $host = \DB_HOST;
    private $user = \DB_USER;
    private $pass = \DB_PASS;
    private $dbname = \DB_NAME;

    private $dbh; // Database Handler
    private $stmt; // Statement
    private $error;

    public function __construct(){
        // Set Data Source Name (DSN)
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        // Create a new PDO instance
        try{
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e){
            $this->error = $e->getMessage();
            error_log("Database Connection Error: " . $this->error);
            // In a real app, you might want to die() here with a user-friendly message
            echo json_encode(['message' => 'Database connection failed.']);
            exit();
        }
    }

    /**
     * Prepares a statement with the given SQL query.
     * @param string $sql The SQL query to prepare.
     */
    public function query($sql){
        $this->stmt = $this->dbh->prepare($sql);
    }

    /**
     * Binds a value to a corresponding named or question mark placeholder in the SQL statement.
     * @param string $param The parameter placeholder (e.g., :email).
     * @param mixed $value The value to bind to the parameter.
     * @param int|null $type The PDO data type for the parameter.
     */
    public function bind($param, $value, $type = null){
        if(is_null($type)){
            switch(true){
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Executes the prepared statement.
     * @return bool True on success, false on failure.
     */
    public function execute(){
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Query Execution Error: " . $this->error);
            return false;
        }
    }

    /**
     * Fetches all result set rows as an array of objects.
     * @return array
     */
    public function resultSet(){
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Fetches a single result set row as an object.
     * @return mixed
     */
    public function single(){
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Gets the number of rows affected by the last SQL statement.
     * @return int
     */
    public function rowCount(){
        return $this->stmt->rowCount();
    }

    /**
     * Returns the ID of the last inserted row.
     * @return string
     */
    public function lastInsertId(){
        return $this->dbh->lastInsertId();
    }
}
