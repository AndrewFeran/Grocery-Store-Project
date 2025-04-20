<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Required for AJAX request
header('Content-Type: application/json');

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

$order_id = intval($_GET['order_id']);

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "mynewpassword";
$dbname = "grocery_store";

try {
    // Create connection using PDO
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare query to retrieve order details
    $order_query = "SELECT o.ID as OrderID, o.OrderDate as OrderDate, 
                    c.First_Name, c.Last_Name
                  FROM `Order` o
                  JOIN Customer c ON o.Customer_ID = c.ID
                  WHERE o.ID = ?";
    
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->execute([$order_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    
    // Prepare query to retrieve order items
    $items_query = "SELECT p.ID as ProductID, p.Name, p.Sell_Price as Price, 
                      COUNT(oi.ID) as Quantity,
                      (COUNT(oi.ID) * p.Sell_Price) as Subtotal
                    FROM OrderItem oi
                    JOIN Product p ON oi.Product_ID = p.ID
                    WHERE oi.Order_ID = ?
                    GROUP BY p.ID, p.Name, p.Sell_Price
                    ORDER BY p.Name";
    
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->execute([$order_id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format customer name
    $customer_name = $order['First_Name'] . ' ' . $order['Last_Name'];
    
    // Prepare response data
    $response = [
        'order_id' => $order['OrderID'],
        'date' => date('M d, Y', strtotime($order['OrderDate'])),
        'customer' => $customer_name,
        'items' => $items
    ];
    
    echo json_encode($response);
    
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

// Close the connection
$conn = null;
?>