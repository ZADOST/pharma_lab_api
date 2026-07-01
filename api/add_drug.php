<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get the raw POST data from Flutter
$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->brand_name) &&
    !empty($data->company_name) &&
    !empty($data->price) &&
    !empty($data->qr_code_hash)
) {
    $query = "INSERT INTO drugs (brand_name, company_name, description, price, qr_code_hash, total_stock) 
              VALUES (:brand_name, :company_name, :description, :price, :qr_code_hash, :total_stock)";
    
    $stmt = $db->prepare($query);

    // Sanitize data
    $brand_name = htmlspecialchars(strip_tags($data->brand_name));
    $company_name = htmlspecialchars(strip_tags($data->company_name));
    $description = htmlspecialchars(strip_tags($data->description ?? ''));
    $price = htmlspecialchars(strip_tags($data->price));
    $qr_code_hash = htmlspecialchars(strip_tags($data->qr_code_hash));
    $total_stock = htmlspecialchars(strip_tags($data->total_stock ?? 0));

    // Bind parameters
    $stmt->bindParam(":brand_name", $brand_name);
    $stmt->bindParam(":company_name", $company_name);
    $stmt->bindParam(":description", $description);
    $stmt->bindParam(":price", $price);
    $stmt->bindParam(":qr_code_hash", $qr_code_hash);
    $stmt->bindParam(":total_stock", $total_stock);

    try {
        if($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Drug was successfully added to inventory."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to add drug. Database execution failed."));
        }
    } catch(PDOException $e) {
        http_response_code(400);
        echo json_encode(array("message" => "Error: QR Code Hash must be distinct. " . $e->getMessage()));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to add drug. Data is incomplete."));
}
?>