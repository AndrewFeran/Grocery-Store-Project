<?php include 'navbar.php'; ?>
<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session for order tracking
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "mynewpassword";
$dbname = "grocery_store";

// Initialize variables
$success_message = null;
$error_message = null;

try {
    // Create connection using PDO
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle order cancellation if requested
    if(isset($_POST['cancel_order']) && isset($_POST['order_id'])) {
        $order_id = $_POST['order_id'];
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // First, retrieve the ordered products to restore inventory
            $items_query = "SELECT p.ID, oi.Order_ID, COUNT(oi.ID) as quantity 
                            FROM OrderItem oi 
                            JOIN Product p ON oi.Product_ID = p.ID 
                            WHERE oi.Order_ID = ? 
                            GROUP BY p.ID";
            $items_stmt = $conn->prepare($items_query);
            $items_stmt->execute([$order_id]);
            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Restore product quantities to inventory
            $restore_sql = "UPDATE Product SET Quantity = Quantity + ? WHERE ID = ?";
            $restore_stmt = $conn->prepare($restore_sql);
            
            foreach($items as $item) {
                $restore_stmt->execute([$item['quantity'], $item['ID']]);
            }
            
            // Delete order items
            $delete_items_sql = "DELETE FROM OrderItem WHERE Order_ID = ?";
            $delete_items_stmt = $conn->prepare($delete_items_sql);
            $delete_items_stmt->execute([$order_id]);
            
            // Delete the order
            $delete_order_sql = "DELETE FROM `Order` WHERE ID = ?";
            $delete_order_stmt = $conn->prepare($delete_order_sql);
            $delete_order_stmt->execute([$order_id]);
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Order #" . $order_id . " has been cancelled successfully.";
        } catch(PDOException $e) {
            // Rollback transaction in case of error
            $conn->rollBack();
            $error_message = "Error cancelling order: " . $e->getMessage();
        }
    }
    
    // Prepare query to retrieve orders with JOIN to Customer table
    $sql = "SELECT o.ID as OrderID, o.OrderDate, 
                   c.ID as CustomerID, c.Name,
                   COUNT(oi.ID) as TotalItems,
                   SUM(p.Sell_Price) as TotalAmount
            FROM `Order` o
            JOIN Customer c ON o.Customer_ID = c.ID
            JOIN OrderItem oi ON o.ID = oi.Order_ID
            JOIN Product p ON oi.Product_ID = p.ID
            GROUP BY o.ID
            ORDER BY o.ID DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    // Set fetch mode to associative array
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "<div style='color:red; padding:20px; background-color:#ffeeee; border:1px solid #ff0000;'>";
    echo "<h2>Database Connection Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database credentials and connection.</p>";
    echo "</div>";
    exit();
}

// Function to get order details
function getOrderDetails($conn, $order_id) {
    $query = "SELECT p.ID, p.Name, p.Sell_Price, COUNT(oi.ID) as Quantity,
                    (COUNT(oi.ID) * p.Sell_Price) as Subtotal
              FROM OrderItem oi
              JOIN Product p ON oi.Product_ID = p.ID
              WHERE oi.Order_ID = ?
              GROUP BY p.ID, p.Name, p.Sell_Price
              ORDER BY p.Name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders</title>
    <style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
}
.container {
    max-width: 1200px;
    margin: 20px auto;
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
h1 {
    color: #333;
    text-align: center;
    margin-top: 0;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}
th {
    background-color: #1e5631; /* Darker green to match navbar */
    color: white;
}
tr:hover {
    background-color: #f5f5f5;
}
.search-container {
    margin-bottom: 20px;
}
.search-container input {
    padding: 8px;
    width: 300px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.search-container button {
    padding: 8px 16px;
    background-color: #1e5631; /* Darker green */
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.search-container button:hover {
    background-color: #2e7d41; /* Consistent hover state */
}
.view-details {
    background-color: #1e5631; /* Darker green */
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}
.view-details:hover {
    background-color: #2e7d41; /* Consistent hover state */
}
.cancel-order {
    background-color: #f44336; /* Red */
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}
.cancel-order:hover {
    background-color: #e53935; /* Slightly darker red */
}
/* Success message styling */
.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #c3e6cb;
}
/* Error message styling */
.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}
/* Modal styling */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}
.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    border-radius: 5px;
    border-top: 3px solid #1e5631; /* Added accent border */
}
.modal h2 {
    color: #1e5631; /* Darker green */
    margin-top: 0;
}
.modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}
.modal-close:hover,
.modal-close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}
.order-details-table {
    width: 100%;
    margin-top: 20px;
}
.order-total {
    font-weight: bold;
    text-align: right;
    margin-top: 20px;
    font-size: 18px;
}
/* Confirmation modal styling */
.confirm-modal {
    display: none;
    position: fixed;
    z-index: 2100;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}
.confirm-modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 400px;
    border-radius: 5px;
    text-align: center;
    border-top: 3px solid #f44336; /* Red accent for warning */
}
.confirm-modal-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}
.confirm-modal-buttons button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
    </style>
</head>
<body>
    <!-- Success/Error Message Display -->
    <?php if(isset($success_message)): ?>
    <div class="success-message container">
        <?php echo $success_message; ?>
    </div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
    <div class="error-message container">
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>
    
    <div class="container">
        <h1>Orders</h1>
        
        <div class="search-container">
            <form action="" method="GET">
                <input type="text" name="search" placeholder="Search by order number, customer name..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit">Search</button>
            </form>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($orders)) {
                    foreach($orders as $order) {
                        // Filter by search term if provided
                        $search_term = isset($_GET['search']) ? strtolower($_GET['search']) : '';
                        
                        if (!empty($search_term)) {
                            $order_id_match = strpos(strtolower($order['OrderID']), $search_term) !== false;
                            $customer_match = strpos(strtolower($order['Name']), $search_term) !== false;
                            
                            if (!$order_id_match && !$customer_match) {
                                continue; // Skip this order if it doesn't match search
                            }
                        }
                        
                        echo "<tr>";
                        echo "<td>#" . $order['OrderID'] . "</td>";
                        echo "<td>" . date('M d, Y', strtotime($order['OrderDate'])) . "</td>";
                        echo "<td>" . htmlspecialchars($order['Name']) . "</td>";
                        echo "<td>" . $order['TotalItems'] . "</td>";
                        echo "<td>$" . number_format($order['TotalAmount'], 2) . "</td>";
                        echo "<td>";
                        echo "<button class='view-details' onclick='showOrderDetails(" . $order['OrderID'] . ")'>View Details</button> ";
                        echo "<button class='cancel-order' onclick='confirmCancelOrder(" . $order['OrderID'] . ")'>Cancel Order</button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No orders found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; text-align: right;">
            <p>Total Orders: <?php echo isset($orders) ? count($orders) : 0; ?></p>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Order Details <span id="modal-order-id"></span></h2>
            <p><strong>Customer:</strong> <span id="modal-customer-name"></span></p>
            <p><strong>Date:</strong> <span id="modal-order-date"></span></p>
            
            <table class="order-details-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody id="order-details-body">
                    <!-- Order items will be loaded here dynamically -->
                </tbody>
            </table>
            
            <div class="order-total">
                Total: $<span id="modal-order-total">0.00</span>
            </div>
        </div>
    </div>
    
    <!-- Confirm Cancel Order Modal -->
    <div id="confirmCancelModal" class="confirm-modal">
        <div class="confirm-modal-content">
            <h2>Confirm Cancellation</h2>
            <p>Are you sure you want to cancel Order #<span id="cancel-order-id"></span>?</p>
            <p>This action cannot be undone.</p>
            
            <div class="confirm-modal-buttons">
                <button onclick="cancelOrder()" style="background-color: #f44336; color: white;">Cancel Order</button>
                <button onclick="closeConfirmModal()" style="background-color: #999; color: white;">Nevermind</button>
            </div>
            
            <!-- Hidden form for order cancellation -->
            <form id="cancel-order-form" method="POST" action="" style="display: none;">
                <input type="hidden" name="cancel_order" value="1">
                <input type="hidden" name="order_id" id="cancel-order-id-input" value="">
            </form>
        </div>
    </div>

    <script>
        // Show order details modal
        function showOrderDetails(orderId) {
            // Fetch order details via AJAX
            fetch('get_order_details.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    // Populate modal with order details
                    document.getElementById('modal-order-id').textContent = '#' + data.order_id;
                    document.getElementById('modal-customer-name').textContent = data.customer;
                    document.getElementById('modal-order-date').textContent = data.date;
                    
                    // Clear existing items
                    const detailsBody = document.getElementById('order-details-body');
                    detailsBody.innerHTML = '';
                    
                    // Add order items
                    let total = 0;
                    data.items.forEach(item => {
                        const row = document.createElement('tr');
                        const subtotal = parseFloat(item.Price) * parseInt(item.Quantity);
                        
                        row.innerHTML = `
                            <td>${item.Name}</td>
                            <td>${parseFloat(item.Price).toFixed(2)}</td>
                            <td>${item.Quantity}</td>
                            <td>${subtotal.toFixed(2)}</td>
                        `;
                        detailsBody.appendChild(row);
                        total += subtotal;
                    });
                    
                    // Update total
                    document.getElementById('modal-order-total').textContent = total.toFixed(2);
                    
                    // Show the modal
                    document.getElementById('orderDetailsModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error fetching order details:', error);
                    alert('Error loading order details. Please try again.');
                });
        }
        
        // Close the details modal
        document.querySelector('.modal-close').addEventListener('click', function() {
            document.getElementById('orderDetailsModal').style.display = 'none';
        });
        
        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('orderDetailsModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
            
            const confirmModal = document.getElementById('confirmCancelModal');
            if (event.target == confirmModal) {
                confirmModal.style.display = 'none';
            }
        });
        
        // Show confirm cancel modal
        function confirmCancelOrder(orderId) {
            document.getElementById('cancel-order-id').textContent = orderId;
            document.getElementById('cancel-order-id-input').value = orderId;
            document.getElementById('confirmCancelModal').style.display = 'block';
        }
        
        // Close confirm modal
        function closeConfirmModal() {
            document.getElementById('confirmCancelModal').style.display = 'none';
        }
        
        // Submit cancel order form
        function cancelOrder() {
            document.getElementById('cancel-order-form').submit();
        }
    </script>
</body>
</html>

<?php
// Close the connection
$conn = null;
?>