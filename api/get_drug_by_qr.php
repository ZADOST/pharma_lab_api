<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$qr_hash = isset($_GET['qr_hash']) ? $_GET['qr_hash'] : die(json_encode(["message" => "QR Hash required."]));

try {
    $query = "SELECT * FROM drugs WHERE qr_code_hash = :qr_hash LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':qr_hash', $qr_hash);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Drug not found in inventory."]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database Error: " . $e->getMessage()]);
}
?>