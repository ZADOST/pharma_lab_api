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

if(!empty($data->full_name) && !empty($data->phone)) {
    
    // Ensure the KRI formatting is maintained
    $full_name = htmlspecialchars(strip_tags($data->full_name));
    $phone = htmlspecialchars(strip_tags($data->phone));
    $email = !empty($data->email) ? htmlspecialchars(strip_tags($data->email)) : null;

    // Check if phone number already exists to prevent duplicates
    $check_query = "SELECT patient_id FROM patients WHERE phone = :phone LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":phone", $phone);
    $check_stmt->execute();

    if($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(array("message" => "A patient with this phone number is already registered."));
        exit;
    }

    $query = "INSERT INTO patients (full_name, phone, email) VALUES (:full_name, :phone, :email)";
    $stmt = $db->prepare($query);

    $stmt->bindParam(":full_name", $full_name);
    $stmt->bindParam(":phone", $phone);
    $stmt->bindParam(":email", $email);

    try {
        if($stmt->execute()) {
            http_response_code(201);
            // Return the newly created ID so the pharmacist can use it immediately
            echo json_encode(array(
                "message" => "Patient registered successfully.",
                "patient_id" => $db->lastInsertId()
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to register patient."));
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Database Error: " . $e->getMessage()));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data. Name and phone are required."));
}
?>