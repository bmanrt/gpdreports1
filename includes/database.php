<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'gpdreports1';
    private $username = 'gpdroot';
    private $password = 'lolamarsh1@A';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            throw new Exception("Connection error: " . $e->getMessage());
        }

        return $this->conn;
    }
}
