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

if (!empty($data->drug_id) && !empty($data->quantity) && !empty($data->total_price)) {
    try {
        // Begin a transaction to ensure both tables update safely
        $db->beginTransaction();

        // 1. Insert the sale record
        $sale_query = "INSERT INTO sales (drug_id, patient_id, quantity, total_price) 
                       VALUES (:drug_id, :patient_id, :quantity, :total_price)";
        $sale_stmt = $db->prepare($sale_query);
        
        $patient_id = !empty($data->patient_id) ? htmlspecialchars(strip_tags($data->patient_id)) : null;
        $drug_id = htmlspecialchars(strip_tags($data->drug_id));
        $quantity = htmlspecialchars(strip_tags($data->quantity));
        $total_price = htmlspecialchars(strip_tags($data->total_price));

        $sale_stmt->bindParam(':drug_id', $drug_id);
        $sale_stmt->bindParam(':patient_id', $patient_id);
        $sale_stmt->bindParam(':quantity', $quantity);
        $sale_stmt->bindParam(':total_price', $total_price);
        $sale_stmt->execute();

        // 2. Reduce the total stock in the drugs table
        $update_query = "UPDATE drugs SET total_stock = total_stock - :quantity WHERE drug_id = :drug_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':quantity', $quantity);
        $update_stmt->bindParam(':drug_id', $drug_id);
        $update_stmt->execute();

        // Commit the transaction
        $db->commit();

        http_response_code(201);
        echo json_encode(["message" => "Sale recorded and inventory updated successfully."]);

    } catch(PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["message" => "Transaction failed: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete sale data."]);
}
?>