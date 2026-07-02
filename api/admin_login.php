<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->username) && !empty($data->password)) {
    
    $query = "SELECT staff_id, username, password_hash, role FROM staff WHERE username = :username LIMIT 1";
    $stmt = $db->prepare($query);
    
    $username = htmlspecialchars(strip_tags($data->username));
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Securely verify the entered password against the hashed password in the DB
        if(password_verify($data->password, $row['password_hash'])) {
            http_response_code(200);
            echo json_encode(array(
                "message" => "Admin login successful.",
                "staff_id" => $row['staff_id'],
                "username" => $row['username'],
                "role" => $row['role']
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "Incorrect password."));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Staff account not found."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete credentials."));
}
?>