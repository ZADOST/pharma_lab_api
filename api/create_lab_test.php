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

if(!empty($data->patient_id) && !empty($data->test_name)) {
    
    $query = "INSERT INTO lab_tests (patient_id, test_name, status) VALUES (:patient_id, :test_name, 'PENDING')";
    $stmt = $db->prepare($query);

    $patient_id = htmlspecialchars(strip_tags($data->patient_id));
    $test_name = htmlspecialchars(strip_tags($data->test_name));

    $stmt->bindParam(":patient_id", $patient_id);
    $stmt->bindParam(":test_name", $test_name);

    try {
        if($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Lab test successfully assigned."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to create test."));
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Database Error: " . $e->getMessage()));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data. Patient ID and Test Name are required."));
}
?>