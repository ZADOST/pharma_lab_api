<?php
// Production CORS Headers - Restrict this later to your specific domain for security
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

class Database {
    // UPDATE THESE TO YOUR LIVE SERVER CREDENTIALS
    private $host = "localhost"; // Usually remains localhost on cPanel
    private $db_name = "pharma_lab_ecosystem"; 
    private $username = "root"; 
    private $password = "9889"; 
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            // In production, do not echo exact database errors to the screen
            http_response_code(500);
            echo json_encode(["message" => "Critical Database Connection Error"]);
            exit;
        }
        return $this->conn;
    }
}
?>