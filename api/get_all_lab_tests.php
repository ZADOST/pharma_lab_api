<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Fetch tests, ordering by newest first
    $query = "SELECT t.test_id, t.patient_id, p.full_name as patient_name, t.test_name, t.status, t.result_data 
              FROM lab_tests t
              LEFT JOIN patients p ON t.patient_id = p.patient_id
              ORDER BY t.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $results = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        array_push($results, $row);
    }

    http_response_code(200);
    echo json_encode(["data" => $results]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database Error: " . $e->getMessage()]);
}
?>