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

// We check if they are logging in via phone number OR Facebook OAuth
if(!empty($data->phone) || !empty($data->oauth_id)) {
    
    if (!empty($data->phone)) {
        $query = "SELECT patient_id, full_name, phone FROM patients WHERE phone = :identifier LIMIT 1";
        $identifier = htmlspecialchars(strip_tags($data->phone));
    } else {
        $query = "SELECT patient_id, full_name, oauth_provider FROM patients WHERE oauth_id = :identifier LIMIT 1";
        $identifier = htmlspecialchars(strip_tags($data->oauth_id));
    }

    $stmt = $db->prepare($query);
    $stmt->bindParam(':identifier', $identifier);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        http_response_code(200);
        // In a production app, you would generate and return a JWT here
        echo json_encode(array(
            "message" => "Login successful.",
            "patient_id" => $row['patient_id'],
            "full_name" => $row['full_name']
        ));
    } else {
        // If they don't exist, you would route them to a registration flow
        http_response_code(404);
        echo json_encode(array("message" => "Patient record not found. Please register."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete login credentials."));
}
?>