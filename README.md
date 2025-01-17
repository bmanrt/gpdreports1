# GPD Reports - Admin Dashboard

A modern admin dashboard for managing GPD reports with role-based access control, built using PHP, MySQL, JavaScript, and Tailwind CSS.

## Project Structure
```
gpdreports/
├── config/
│   ├── config.php
│   └── database.php
├── assets/
│   ├── css/
│   └── js/
├── includes/
│   ├── auth.php
│   ├── functions.php
│   └── security.php
├── layouts/
│   ├── header.php
│   └── footer.php
├── pages/
│   ├── dashboard.php
│   ├── login.php
│   ├── register.php
│   ├── reports/
│   └── users/
└── api/
    ├── reports.php
    └── users.php
```

## Requirements
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache web server
- Composer (for dependency management)

## Features
1. Authentication System
   - User Registration
   - Login System
   - Password Reset
   - Role-based Access Control

2. Dashboard Features
   - Overview Statistics
   - Report Management
   - User Management
   - Region/Zone Filtering
   - Search Functionality

3. Security Features
   - Password Hashing
   - SQL Injection Prevention
   - XSS Protection
   - CSRF Protection
   - Session Management
   - Input Validation

## Installation
1. Clone the repository to your local machine
2. Import the database schema from `database/schema.sql`
3. Configure database connection in `config/database.php`
4. Install dependencies using Composer
5. Start your local server

## Database Structure
1. Users Table
   - id (Primary Key)
   - username
   - password
   - email
   - role
   - region
   - zone
   - created_at

2. Reports Table
   - id (Primary Key)
   - user_id (Foreign Key)
   - title
   - content
   - region
   - zone
   - status
   - created_at
   - updated_at
