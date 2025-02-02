<?php
$projectRoot = dirname(__DIR__);

// Include the Database class
require_once $projectRoot . '/api/config/Database.php';

class DatabaseSeeder {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function run() {
        try {
            $this->conn->beginTransaction();

            // Seed users
            $users = $this->seedUsers();
            echo "Users seeded successfully!\n";

            // Seed events
            $events = $this->seedEvents($users);
            echo "Events seeded successfully!\n";

            // Seed attendees and registrations
            $this->seedAttendeesAndRegistrations($events);
            echo "Attendees and registrations seeded successfully!\n";

            $this->conn->commit();
            echo "All data seeded successfully!\n";

        } catch (Exception $e) {
            $this->conn->rollBack();
            echo "Error seeding data: " . $e->getMessage() . "\n";
        }
    }

    private function seedUsers() {
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@evently.com',
                'password' => 'admin123',
                'full_name' => 'Admin User',
                'is_admin' => true
            ]
        ];
    
        $firstNames = ['Rakib', 'Riya', 'Arif', 'Taslima', 'Hasan', 'Nusrat', 'Shihab', 'Mim', 'Fahim'];
        $lastNames = ['Ahmed', 'Hossain', 'Khan', 'Chowdhury', 'Rahman', 'Islam', 'Sarker', 'Miah', 'Bhuiyan'];        
    
        for ($i = 0; $i < 9; $i++) {
            $firstName = $firstNames[$i];
            $lastName = $lastNames[$i];
            $users[] = [
                'username' => strtolower($firstName . '_' . $lastName),
                'email' => strtolower($firstName . '.' . $lastName . '@example.com'),
                'password' => 'password123',
                'full_name' => $firstName . ' ' . $lastName,
                'is_admin' => false
            ];
        }

        $query = "INSERT INTO users (username, email, password, full_name, is_admin) 
                  VALUES (:username, :email, :password, :full_name, :is_admin)";
        $stmt = $this->conn->prepare($query);

        $seededUsers = [];
        foreach ($users as $user) {
            $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
            $stmt->bindParam(':username', $user['username']);
            $stmt->bindParam(':email', $user['email']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':full_name', $user['full_name']);
            $stmt->bindParam(':is_admin', $user['is_admin'], PDO::PARAM_BOOL);
            $stmt->execute();
            
            $user['id'] = $this->conn->lastInsertId();
            $seededUsers[] = $user;
        }

        return $seededUsers;
    }

    private function seedEvents($users) {
        $eventTypes = ['Conference', 'Workshop', 'Seminar', 'Meetup', 'Training'];
        $topics = ['Technology', 'Business', 'Marketing', 'Design', 'Development', 'Leadership'];
        $venues = [
            'Tech Hub Convention Center',
            'Business Innovation Center',
            'City Conference Hall',
            'Downtown Meeting Space',
            'Innovation Campus',
            'Digital Learning Center'
        ];

        $events = [];
        
        for ($i = 0; $i < 30; $i++) {
            $eventDate = date('Y-m-d', strtotime('+' . rand(1, 365) . ' days'));
            $deadlineDate = date('Y-m-d', strtotime($eventDate . ' -' . rand(7, 30) . ' days'));
            
            $eventType = $eventTypes[array_rand($eventTypes)];
            $topic = $topics[array_rand($topics)];
            
            $events[] = [
                'title' => $topic . ' ' . $eventType . ' ' . date('Y', strtotime($eventDate)),
                'description' => 'Join us for this exciting ' . strtolower($eventType) . ' about ' . $topic . '. Learn from industry experts and network with professionals.',
                'event_date' => $eventDate,
                'event_time' => sprintf('%02d:00:00', rand(8, 18)), // Random time between 8 AM and 6 PM
                'venue' => $venues[array_rand($venues)],
                'capacity' => rand(50, 500),
                'registration_deadline' => $deadlineDate
            ];
        }

        $query = "INSERT INTO events (user_id, title, description, event_date, event_time, venue, capacity, registration_deadline) 
                  VALUES (:user_id, :title, :description, :event_date, :event_time, :venue, :capacity, :registration_deadline)";
        $stmt = $this->conn->prepare($query);

        $seededEvents = [];
        foreach ($events as $index => $event) {
            $user_id = $users[$index % count($users)]['id'];
            
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':title', $event['title']);
            $stmt->bindParam(':description', $event['description']);
            $stmt->bindParam(':event_date', $event['event_date']);
            $stmt->bindParam(':event_time', $event['event_time']);
            $stmt->bindParam(':venue', $event['venue']);
            $stmt->bindParam(':capacity', $event['capacity']);
            $stmt->bindParam(':registration_deadline', $event['registration_deadline']);
            $stmt->execute();
            
            $event['id'] = $this->conn->lastInsertId();
            $event['user_id'] = $user_id;
            $seededEvents[] = $event;
        }

        return $seededEvents;
    }

    private function seedAttendeesAndRegistrations($events) {
        $firstNames = ['Shakib', 'Naim', 'Imran', 'Rasel', 'Tamim', 'Ehsan', 'Jubayer', 'Sabbir', 'Nafis', 'Shahin',
            'Afia', 'Tanjila', 'Sultana', 'Mahjabin', 'Anika', 'Farzana', 'Lamia', 'Sharmin', 'Dilruba', 'Nasrin'];

        $lastNames = ['Rahman', 'Islam', 'Hossain', 'Ahmed', 'Chowdhury', 'Khan', 'Miah', 'Sarker', 'Bhuiyan', 'Uddin',
            'Hasan', 'Ali', 'Faruque', 'Kabir', 'Shah', 'Mahmud', 'Haque', 'Rashid', 'Siddique', 'Azad'];


        $domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'];

        $attendees = [];
        for ($i = 0; $i < 100; $i++) {
        $firstName = $firstNames[array_rand($firstNames)];
        $lastName = $lastNames[array_rand($lastNames)];

        // Generate a unique email
        $emailPrefix = strtolower($firstName . '.' . $lastName . rand(1, 999));
        $domain = $domains[array_rand($domains)];

        $attendees[] = [
        'full_name' => $firstName . ' ' . $lastName,
        'email' => $emailPrefix . '@' . $domain,
        'phone' => sprintf('%010d', rand(1000000000, 9999999999)) // 10-digit phone number
        ];
        }


        $attendeeQuery = "INSERT INTO attendees (full_name, email, phone) 
                         VALUES (:full_name, :email, :phone)";
        $attendeeStmt = $this->conn->prepare($attendeeQuery);

        $seededAttendees = [];
        foreach ($attendees as $attendee) {
            $attendeeStmt->bindParam(':full_name', $attendee['full_name']);
            $attendeeStmt->bindParam(':email', $attendee['email']);
            $attendeeStmt->bindParam(':phone', $attendee['phone']);
            $attendeeStmt->execute();
            
            $attendee['id'] = $this->conn->lastInsertId();
            $seededAttendees[] = $attendee;
        }

        $registrationQuery = "INSERT INTO event_registrations (event_id, attendee_id, status) 
                            VALUES (:event_id, :attendee_id, :status)";
        $registrationStmt = $this->conn->prepare($registrationQuery);

        $statuses = ['pending', 'confirmed', 'cancelled'];
        
        foreach ($seededAttendees as $attendee) {
            $numRegistrations = rand(1, 3);
            $randomEvents = array_rand($events, $numRegistrations);
            if (!is_array($randomEvents)) {
                $randomEvents = [$randomEvents];
            }
            
            foreach ($randomEvents as $eventIndex) {
                $event_id = $events[$eventIndex]['id'];
                $status = $statuses[array_rand($statuses)];
                
                $registrationStmt->bindParam(':event_id', $event_id);
                $registrationStmt->bindParam(':attendee_id', $attendee['id']);
                $registrationStmt->bindParam(':status', $status);
                $registrationStmt->execute();
            }
        }
    }
}

$seeder = new DatabaseSeeder();
$seeder->run();
?>