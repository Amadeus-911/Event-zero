<?php
class User {

    private $conn;
    private $table_name = "users";

    public $user_id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $created_at;


    public function __construct($db) {
        $this->conn = $db;
    }

    private function validateInput() {
        $errors = [];

        if (strlen($this->full_name) < 4) {
            $errors[] = "Full name must be at least 4 characters long.";
        }

        if (strlen($this->username) < 3) {
            $errors[] = "Username must be at least 3 characters long.";
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->username)) {
            $errors[] = "Username can only contain letters, numbers, and underscores.";
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        if (!preg_match('/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+){8,}$/', $this->password)) {
            $errors[] = "Password must be at least 8 characters long and contain at least one number.";
        }

        return $errors;
    }

    public function create() {
        try {

            $validationErrors = $this->validateInput();
            if (!empty($validationErrors)) {
                return ['success' => false, 'message' => implode(' ', $validationErrors)];
            }

            if ($this->isUserExists()) {
                return ['success' => false, 'message' => 'Username or email already exists.'];
            }

            $query = "INSERT INTO " . $this->table_name . "
                    SET
                        username=:username,
                        email=:email,
                        password=:password,
                        full_name=:full_name";


            $stmt = $this->conn->prepare($query);
            $this->username = htmlspecialchars(strip_tags($this->username));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->full_name = htmlspecialchars(strip_tags($this->full_name));
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);

            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $this->password);
            $stmt->bindParam(":full_name", $this->full_name);

            if($stmt->execute()) {
                return ['success' => true, 'message' => 'Registration successful.'];
            }

            return ['success' => false, 'message' => 'Registration failed.'];

        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    private function isUserExists() {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . "
                WHERE username = :username OR email = :email";


        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);

        $stmt->execute();

        $count = $stmt->fetchColumn();

        return $count > 0;
    }

    public function login($identifier, $password) {
        try {
            $query = "SELECT user_id, username, email, password, full_name, is_admin
                    FROM " . $this->table_name . "
                    WHERE username = :identifier OR email = :identifier";


            $stmt = $this->conn->prepare($query);
            $identifier = htmlspecialchars(strip_tags($identifier));
            $stmt->bindParam(":identifier", $identifier);
            $stmt->execute();
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if(password_verify($password, $row['password'])) {
                    // Password is correct, set user properties
                    $this->user_id = $row['user_id'];
                    $this->username = $row['username'];
                    $this->email = $row['email'];
                    $this->full_name = $row['full_name'];
                    $this->is_admin = $row['is_admin'];
                    
                    return true;
                }
            }
            
            return false;
            
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
}
?>