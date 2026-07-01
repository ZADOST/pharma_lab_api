<?php
// pharma_api/api/get_patient_history.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// In a real application, the patient_id would come from a secure JWT token.
// For this architecture build, we accept it as a GET parameter.
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : die(json_encode(["message" => "Patient ID required."]));

$response = array("lab_tests" => array(), "medications" => array());

try {
    // 1. Fetch Lab Tests
    $lab_query = "SELECT test_id, test_name, status, result_data, completed_at 
                  FROM lab_tests 
                  WHERE patient_id = :patient_id 
                  ORDER BY created_at DESC";
    $lab_stmt = $db->prepare($lab_query);
    $lab_stmt->bindParam(':patient_id', $patient_id);
    $lab_stmt->execute();

    while ($row = $lab_stmt->fetch(PDO::FETCH_ASSOC)) {
        array_push($response["lab_tests"], $row);
    }

    // 2. Fetch Medication Purchase History
    $med_query = "SELECT s.sale_id, s.quantity, s.sale_date, d.brand_name, d.description 
                  FROM sales s
                  JOIN drugs d ON s.drug_id = d.drug_id
                  WHERE s.patient_id = :patient_id 
                  ORDER BY s.sale_date DESC";
    $med_stmt = $db->prepare($med_query);
    $med_stmt->bindParam(':patient_id', $patient_id);
    $med_stmt->execute();

    while ($row = $med_stmt->fetch(PDO::FETCH_ASSOC)) {
        array_push($response["medications"], $row);
    }

    http_response_code(200);
    echo json_encode($response);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Database Error: " . $e->getMessage()));
}
?>