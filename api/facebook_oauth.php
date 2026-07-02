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

if(!empty($data->oauth_id) && !empty($data->full_name)) {
    
    $oauth_id = htmlspecialchars(strip_tags($data->oauth_id));
    $full_name = htmlspecialchars(strip_tags($data->full_name));
    $email = !empty($data->email) ? htmlspecialchars(strip_tags($data->email)) : null;

    // 1. Check if this Facebook user already exists in our system
    $check_query = "SELECT patient_id, full_name FROM patients WHERE oauth_id = :oauth_id LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":oauth_id", $oauth_id);
    $check_stmt->execute();

    if($check_stmt->rowCount() > 0) {
        // User exists: Proceed with Login
        $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode(array(
            "message" => "Login successful.",
            "patient_id" => $row['patient_id'],
            "full_name" => $row['full_name']
        ));
    } else {
        // 2. User does not exist: Automatically register them
        $insert_query = "INSERT INTO patients (full_name, email, oauth_provider, oauth_id) 
                         VALUES (:full_name, :email, 'facebook', :oauth_id)";
        $insert_stmt = $db->prepare($insert_query);
        
        $insert_stmt->bindParam(":full_name", $full_name);
        $insert_stmt->bindParam(":email", $email);
        $insert_stmt->bindParam(":oauth_id", $oauth_id);

        try {
            if($insert_stmt->execute()) {
                http_response_code(201);
                echo json_encode(array(
                    "message" => "Account created and logged in.",
                    "patient_id" => $db->lastInsertId(),
                    "full_name" => $full_name
                ));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to create account."));
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Database Error: " . $e->getMessage()));
        }
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete OAuth data."));
}
?>