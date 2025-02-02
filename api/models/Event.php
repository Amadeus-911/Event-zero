<?php
class Event {
    private $conn;
    private $table_name = "events";

    public $event_id;
    public $user_id;
    public $title;
    public $description;
    public $event_date;
    public $event_time;
    public $venue;
    public $capacity;
    public $registration_deadline;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserEvents($user_id, $page = 1, $limit = 10, $search = '', $orderColumn = 'event_date', $orderDir = 'ASC') {
        try {
            $offset = ($page - 1) * $limit;
    
            // Base conditions
            $whereClause = "WHERE e.user_id = :user_id";
            $params = [":user_id" => $user_id];
    
            // Add search condition if provided
            if ($search) {
                $whereClause .= " AND (e.title LIKE :search OR e.description LIKE :search OR e.venue LIKE :search)";
                $params[":search"] = "%$search%";
            }
    
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM " . $this->table_name . " e " . $whereClause;
            $countStmt = $this->conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
            // Main query
            $query = "SELECT e.*, 
                            u.username as organizer,
                            COUNT(er.registration_id) as registered_attendees
                     FROM " . $this->table_name . " e
                     LEFT JOIN users u ON e.user_id = u.user_id
                     LEFT JOIN event_registrations er ON e.event_id = er.event_id
                     " . $whereClause . "
                     GROUP BY e.event_id
                     ORDER BY e." . $orderColumn . " " . $orderDir . "
                     LIMIT :offset, :limit";
    
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
            $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total_records' => $total,
                'total_pages' => ceil($total / $limit)
            ];
    
        } catch(PDOException $e) {
            return false;
        }
    }
    public function getAllEvents($page = 1, $limit = 10, $search = '', $orderColumn = 'event_date', $orderDir = 'ASC', $dateFilter = '', $statusFilter = '') {
        try {
            $offset = ($page - 1) * $limit;
    
            // Base query parts
            $whereConditions = [];
            $params = [];
    
            // Add search condition if provided
            if ($search) {
                $whereConditions[] = "(e.title LIKE :search OR e.description LIKE :search OR e.venue LIKE :search)";
                $params[":search"] = "%$search%";
            }
    
            // Add date filter if provided
            if ($dateFilter) {
                $whereConditions[] = "e.event_date = :date_filter";
                $params[":date_filter"] = $dateFilter;
            }
    
            // Add status filter if provided
            if ($statusFilter) {
                $now = date('Y-m-d');
                switch ($statusFilter) {
                    case 'past':
                        $whereConditions[] = "e.event_date < :current_date";
                        $params[":current_date"] = $now;
                        break;
                    case 'full':
                        $whereConditions[] = "COALESCE(registered_count.count, 0) >= e.capacity";
                        break;
                    case 'available':
                        $whereConditions[] = "e.event_date >= :current_date AND (COALESCE(registered_count.count, 0) < e.capacity)";
                        $params[":current_date"] = $now;
                        break;
                }
            }
    
            // Combine where conditions
            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
            // Get total count
            $countQuery = "SELECT COUNT(DISTINCT e.event_id) as total 
                          FROM " . $this->table_name . " e 
                          LEFT JOIN (
                              SELECT event_id, COUNT(*) as count 
                              FROM event_registrations 
                              GROUP BY event_id
                          ) registered_count ON e.event_id = registered_count.event_id 
                          " . $whereClause;
    
            $countStmt = $this->conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
            // Main query
            $query = "SELECT e.*, 
                            u.username as organizer,
                            COALESCE(registered_count.count, 0) as registered_attendees
                     FROM " . $this->table_name . " e
                     LEFT JOIN users u ON e.user_id = u.user_id
                     LEFT JOIN (
                         SELECT event_id, COUNT(*) as count 
                         FROM event_registrations 
                         GROUP BY event_id
                     ) registered_count ON e.event_id = registered_count.event_id
                     " . $whereClause . "
                     GROUP BY e.event_id
                     ORDER BY e." . $orderColumn . " " . $orderDir . "
                     LIMIT :offset, :limit";
    
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
            $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total_records' => $total,
                'total_pages' => ceil($total / $limit)
            ];
    
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // public function getAllEvents($page = 1, $limit = 10, $search = '', $orderColumn = 'event_date', $orderDir = 'ASC') {
    //     try {
    //         $offset = ($page - 1) * $limit;
    
    //         // Base query parts
    //         $whereClause = "";
    //         $params = [];
    
    //         // Add search condition if provided
    //         if ($search) {
    //             $whereClause = "WHERE e.title LIKE :search OR e.description LIKE :search OR e.venue LIKE :search";
    //             $params[":search"] = "%$search%";
    //         }
    
    //         // Get total count
    //         $countQuery = "SELECT COUNT(*) as total FROM " . $this->table_name . " e " . $whereClause;
    //         $countStmt = $this->conn->prepare($countQuery);
    //         foreach ($params as $key => $value) {
    //             $countStmt->bindValue($key, $value);
    //         }
    //         $countStmt->execute();
    //         $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    //         // Main query
    //         $query = "SELECT e.*, 
    //                         u.username as organizer,
    //                         COUNT(er.registration_id) as registered_attendees
    //                  FROM " . $this->table_name . " e
    //                  LEFT JOIN users u ON e.user_id = u.user_id
    //                  LEFT JOIN event_registrations er ON e.event_id = er.event_id
    //                  " . $whereClause . "
    //                  GROUP BY e.event_id
    //                  ORDER BY e." . $orderColumn . " " . $orderDir . "
    //                  LIMIT :offset, :limit";
    
    //         $stmt = $this->conn->prepare($query);
    //         foreach ($params as $key => $value) {
    //             $stmt->bindValue($key, $value);
    //         }
    //         $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    //         $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    //         $stmt->execute();
            
    //         return [
    //             'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    //             'total_records' => $total,
    //             'total_pages' => ceil($total / $limit)
    //         ];
    
    //     } catch(PDOException $e) {
    //         return false;
    //     }
    // }

    public function getEventById($event_id) {
        try {
            $query = "SELECT e.*, 
                            u.username as organizer,
                            u.email as organizer_email,
                            COUNT(er.registration_id) as registered_attendees
                     FROM " . $this->table_name . " e
                     LEFT JOIN users u ON e.user_id = u.user_id
                     LEFT JOIN event_registrations er ON e.event_id = er.event_id
                     WHERE e.event_id = :event_id
                     GROUP BY e.event_id";
    
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":event_id", $event_id);
            $stmt->execute();
    
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return false;
    
        } catch(PDOException $e) {
            return false;
        }
    }

    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                    SET
                        user_id = :user_id,
                        title = :title,
                        description = :description,
                        event_date = :event_date,
                        event_time = :event_time,
                        venue = :venue,
                        capacity = :capacity,
                        registration_deadline = :registration_deadline";
    
            $stmt = $this->conn->prepare($query);
    

            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->venue = htmlspecialchars(strip_tags($this->venue));
            
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":event_date", $this->event_date);
            $stmt->bindParam(":event_time", $this->event_time);
            $stmt->bindParam(":venue", $this->venue);
            $stmt->bindParam(":capacity", $this->capacity);
            $stmt->bindParam(":registration_deadline", $this->registration_deadline);
    
            if($stmt->execute()) {
                return true;
            }
            return false;
    
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    public function updateEvent($event_id, $description, $event_date, $event_time, $registration_deadline, $capacity) {
        try {
            $query = "UPDATE " . $this->table_name . "
                    SET
                        description = :description,
                        event_date = :event_date,
                        event_time = :event_time,
                        registration_deadline = :registration_deadline,
                        capacity = :capacity
                    WHERE event_id = :event_id";
    
            $stmt = $this->conn->prepare($query);

            $description = htmlspecialchars(strip_tags($description));

            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":event_date", $event_date);
            $stmt->bindParam(":event_time", $event_time);
            $stmt->bindParam(":registration_deadline", $registration_deadline);
            $stmt->bindParam(":capacity", $capacity);
            $stmt->bindParam(":event_id", $event_id);
    
            return $stmt->execute();
    
        } catch(PDOException $e) {
            return false;
        }
    }

    public function deleteEvent($event_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE event_id = :event_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":event_id", $event_id);
            
            return $stmt->execute();
            
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>