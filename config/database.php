<?php
// Set CORS headers so Flutter Web can access the API without origin errors
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

class Database {
    private $host = "127.0.0.1";
    private $db_name = "pharma_lab_ecosystem";
    private $username = "root"; // Default XAMPP/MySQL username
    private $password = "";     // Default XAMPP/MySQL password (leave blank if default)
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Database Connection Error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>