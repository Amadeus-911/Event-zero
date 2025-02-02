<?php
class Contact {
    private $conn;
    private $table_name = "contact_messages";


    public $message_id;
    public $name;
    public $email;
    public $message;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                    SET
                        name=:name,
                        email=:email,
                        message=:message";

            $stmt = $this->conn->prepare($query);

            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->message = htmlspecialchars(strip_tags($this->message));


            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":message", $this->message);

            if($stmt->execute()) {
                return true;
            }
            return false;

        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    // Get all messages (for admin)
    public function getAllMessages() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    public function updateStatus($message_id, $status) {
        try {
            $query = "UPDATE " . $this->table_name . "
                    SET status = :status
                    WHERE message_id = :message_id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":message_id", $message_id);

            return $stmt->execute();
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
}
?>