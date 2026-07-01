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
    // 1. First, find out the GRAND TOTAL of all pills sold this current month
    $total_query = "SELECT SUM(quantity) as grand_total 
                    FROM sales 
                    WHERE MONTH(sale_date) = MONTH(CURRENT_DATE()) 
                    AND YEAR(sale_date) = YEAR(CURRENT_DATE())";
    $total_stmt = $db->prepare($total_query);
    $total_stmt->execute();
    $total_row = $total_stmt->fetch(PDO::FETCH_ASSOC);
    
    $grand_total = $total_row['grand_total'] ? $total_row['grand_total'] : 0;

    if ($grand_total == 0) {
        http_response_code(200);
        echo json_encode(["message" => "No sales recorded this month.", "data" => []]);
        exit;
    }

    // 2. Calculate the percentage for each individual drug sold this month
    $query = "SELECT d.brand_name, SUM(s.quantity) as sold_amount, 
                     (SUM(s.quantity) / :grand_total) * 100 as percentage 
              FROM sales s 
              JOIN drugs d ON s.drug_id = d.drug_id 
              WHERE MONTH(s.sale_date) = MONTH(CURRENT_DATE()) 
              AND YEAR(s.sale_date) = YEAR(CURRENT_DATE()) 
              GROUP BY d.drug_id 
              ORDER BY percentage DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':grand_total', $grand_total);
    $stmt->execute();

    $results = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Ensure percentages are rounded to 1 decimal place for clean UI
        $row['percentage'] = round((float)$row['percentage'], 1);
        array_push($results, $row);
    }

    http_response_code(200);
    echo json_encode(["data" => $results]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database Error: " . $e->getMessage()]);
}
?>