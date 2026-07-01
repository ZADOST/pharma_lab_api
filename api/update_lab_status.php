<?php
// pharma_api/api/update_lab_status.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->test_id) && !empty($data->status)) {
    
    // If the status is COMPLETED, we also update the completed_at timestamp
    if ($data->status === 'COMPLETED') {
        $query = "UPDATE lab_tests 
                  SET status = :status, completed_at = CURRENT_TIMESTAMP, result_data = :result_data 
                  WHERE test_id = :test_id";
    } else {
        $query = "UPDATE lab_tests 
                  SET status = :status 
                  WHERE test_id = :test_id";
    }

    $stmt = $db->prepare($query);

    $test_id = htmlspecialchars(strip_tags($data->test_id));
    $status = htmlspecialchars(strip_tags($data->status));
    $result_data = isset($data->result_data) ? htmlspecialchars(strip_tags($data->result_data)) : null;

    $stmt->bindParam(':test_id', $test_id);
    $stmt->bindParam(':status', $status);
    
    if ($data->status === 'COMPLETED') {
        $stmt->bindParam(':result_data', $result_data);
    }

    try {
        if($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array("message" => "Lab test status updated successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update status."));
        }
    } catch(PDOException $e) {
        http_response_code(400);
        echo json_encode(array("message" => "Database Error: " . $e->getMessage()));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data provided."));
}
?>