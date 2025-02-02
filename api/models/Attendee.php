<?php
class Attendee {
    private $conn;
    private $table_name = "attendees";

    public $attendee_id;
    public $full_name;
    public $email;
    public $phone;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function registerForEvent($event_id) {
        try {
            $this->conn->beginTransaction();

            // First check if attendee already exists with this email
            $query = "SELECT attendee_id FROM " . $this->table_name . " WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $this->email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Attendee exists, get their ID
                $this->attendee_id = $stmt->fetch(PDO::FETCH_ASSOC)['attendee_id'];
            } else {
                $query = "INSERT INTO " . $this->table_name . "
                        SET
                            full_name = :full_name,
                            email = :email,
                            phone = :phone";

                $stmt = $this->conn->prepare($query);

    
                $this->full_name = htmlspecialchars(strip_tags($this->full_name));
                $this->email = htmlspecialchars(strip_tags($this->email));
                $this->phone = htmlspecialchars(strip_tags($this->phone));

                $stmt->bindParam(":full_name", $this->full_name);
                $stmt->bindParam(":email", $this->email);
                $stmt->bindParam(":phone", $this->phone);

                if(!$stmt->execute()) {
                    throw new Exception("Error creating attendee");
                }

                $this->attendee_id = $this->conn->lastInsertId();
            }

            $query = "INSERT INTO event_registrations
                    SET
                        event_id = :event_id,
                        attendee_id = :attendee_id,
                        status = 'pending'";

            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":event_id", $event_id);
            $stmt->bindParam(":attendee_id", $this->attendee_id);

            if(!$stmt->execute()) {
                throw new Exception("Error creating registration");
            }

            $this->conn->commit();
            return true;

        } catch(Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Check if attendee is already registered for an event
    public function isRegisteredForEvent($event_id) {
        try {
            $query = "SELECT registration_id 
                     FROM event_registrations 
                     WHERE event_id = :event_id 
                     AND attendee_id = :attendee_id
                     AND status != 'cancelled'";

            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":event_id", $event_id);
            $stmt->bindParam(":attendee_id", $this->attendee_id);
            
            $stmt->execute();
            
            return $stmt->rowCount() > 0;

        } catch(PDOException $e) {
            return false;
        }
    }
}
?>