# Evently - Event Management System

Evently is a comprehensive web application for managing events, registrations, and attendees. Built with PHP and modern web technologies, it provides a user-friendly interface for creating, managing, and participating in events.

## Features

### User Management
- User registration and authentication
- JWT-based session management
- Role-based access control (Admin/Regular users)

### Event Management
- Create and manage events
- Edit event details (date, time, capacity, etc.)
- Delete event
- View event attendees
- Download attendee lists as CSV

### Event Registration
- Register for events (anyone with the event link until max capacity)
- View registration status
- Capacity management
- Registration deadline

### Admin Features
- View and manage contact messages
- System-wide event oversight

### Dashboard Features
- Responsive design
- Advanced filtering and search
- Pagination
- Sortable columns
- Status tracking

## Technology Stack

- **Frontend**:
  - HTML5
  - CSS3
  - JavaScript (ES6+)
  - Bootstrap 5
  - DataTables
  - Font Awesome

- **Backend**:
  - PHP 8.2
  - MySQL
  - JWT Authentication

## Local Setup Instructions

### Prerequisites
- XAMPP (or similar local server stack)
- PHP 8.2
- MySQL 5.7 or higher
- Web browser (Chrome/Firefox recommended)

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone https://github.com/Amadeus-911/Event-zero.git
   ```

2. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named 'event_management'
   - Import the database schema from `database/schema.sql`
   - (Optional) seed sample data using `database/seeders.php`

3. **Configure Database Connection**
   - Navigate to `api/config/Database.php`
   - Update the database credentials:
   ```php
   private $host = "localhost";
   private $db_name = "event_management";
   private $username = "root";
   private $password = "";
   ```

4. **Configure Application**
   - Navigate to `assets/js/config.js`
   - Update the base URL according to your local setup:
   ```javascript
   const CONFIG = {
       [ENV.DEVELOPMENT]: {
           BASE_URL: 'http://localhost/evently',
       }
   };
   ```

5. **Initialize Database with Sample Data (Optional)**
   ```bash
   cd database
   php seeders.php
   ```

6. **Access the Application**
   - Open your browser
   - Navigate to `your_base_url`

### Default Admin Account
```
Email: admin@evently.com
Password: admin123
```

## Project Structure
```
evently/
├── api/
│   ├── config/
│   ├── endpoints/
│   ├── middleware/
│   └── models/
├── assets/
│   ├── css/
│   ├── js/
│   └── icons/
├── database/
│   ├── schema.sql
│   └── seeders.php
└── views/
    ├── admin/
    └── various HTML files
```

## Security Considerations

1. **JWT Token**
   - Tokens expire after 24 hours
   - Stored in localStorage
   - Required for authenticated endpoints

2. **Password Security**
   - Passwords are hashed using PHP's password_hash()

3. **Input Validation**
   - Server-side validation for all inputs
   - SQL injection prevention
   - XSS protection

## Development Guidelines

1. **API Endpoints**
   - All endpoints return JSON responses
   - Standard response format:
   ```json
   {
       "success": boolean,
       "message": string,
       "data": object|null
   }
   ```

2. **Error Handling**
   - Appropriate HTTP status codes
   - Detailed error messages in development
   - Sanitized messages in production

3. **Code Organization**
   - Separate concerns (MVC pattern)
   - Modular JavaScript
   - Reusable components



## License

MIT

## Contact

iftikkharimrukhan@gmail.com

---
