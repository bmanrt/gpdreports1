<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';

class Auth {
    private $db;
    private $security;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->security = new Security();
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }

    public function getUser($userId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user: " . $e->getMessage());
            return null;
        }
    }

    public function register($username, $password, $email, $region, $zone, $role = 'user') {
        try {
            // Validate input
            Security::validateRequired(['username', 'password', 'email', 'region', 'zone'], 
                compact('username', 'password', 'email', 'region', 'zone'));

            // Validate email
            if (!Security::validateEmail($email)) {
                throw new Exception('Invalid email format');
            }

            // Check if username or email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                throw new Exception('Username or email already exists');
            }

            // Hash password
            $hashedPassword = Security::hashPassword($password);

            // Insert user
            $stmt = $this->db->prepare(
                "INSERT INTO users (username, password, email, role, region, zone) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$username, $hashedPassword, $email, $role, $region, $zone]);

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function login($username, $password) {
        try {
            // Validate input
            Security::validateRequired(['username', 'password'], compact('username', 'password'));

            // Get user
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !Security::verifyPassword($password, $user['password'])) {
                throw new Exception('Invalid username or password');
            }

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_data'] = $user;

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function logout() {
        // Unset all session variables
        $_SESSION = array();

        // Destroy the session
        session_destroy();

        // Redirect to login page
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    public static function isAdmin() {
        return isset($_SESSION['user_data']['role']) && $_SESSION['user_data']['role'] === 'admin';
    }

    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
    }

    public function resetPasswordRequest($email) {
        try {
            // Validate email
            if (!Security::validateEmail($email)) {
                throw new Exception('Invalid email format');
            }

            // Check if email exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('Email not found');
            }

            // Generate reset token
            $token = Security::generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save token
            $stmt = $this->db->prepare(
                "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?"
            );
            $stmt->execute([$token, $expires, $email]);

            return $token;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function resetPassword($token, $newPassword) {
        try {
            // Validate token
            $stmt = $this->db->prepare(
                "SELECT id FROM users 
                 WHERE reset_token = ? AND reset_token_expires > NOW()"
            );
            $stmt->execute([$token]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('Invalid or expired token');
            }

            // Hash new password
            $hashedPassword = Security::hashPassword($newPassword);

            // Update password and clear token
            $stmt = $this->db->prepare(
                "UPDATE users 
                 SET password = ?, reset_token = NULL, reset_token_expires = NULL 
                 WHERE reset_token = ?"
            );
            $stmt->execute([$hashedPassword, $token]);

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
?>
