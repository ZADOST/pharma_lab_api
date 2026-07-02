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

if(!empty($data->patient_id) && !empty($data->full_name) && !empty($data->phone)) {
    
    $query = "UPDATE patients 
              SET full_name = :full_name, phone = :phone, email = :email 
              WHERE patient_id = :patient_id";
              
    $stmt = $db->prepare($query);

    // Sanitize inputs
    $patient_id = htmlspecialchars(strip_tags($data->patient_id));
    $full_name = htmlspecialchars(strip_tags($data->full_name));
    $phone = htmlspecialchars(strip_tags($data->phone));
    $email = !empty($data->email) ? htmlspecialchars(strip_tags($data->email)) : null;

    // Bind parameters
    $stmt->bindParam(':patient_id', $patient_id);
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':email', $email);

    try {
        if($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array("message" => "Profile updated successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update profile."));
        }
    } catch(PDOException $e) {
        // If the user tries to change their phone number to one that already exists
        if ($e->getCode() == 23000) { 
            http_response_code(400);
            echo json_encode(array("message" => "This phone number is already registered to another account."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Database Error: " . $e->getMessage()));
        }
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data. Patient ID, Name, and Phone are required."));
}
?>