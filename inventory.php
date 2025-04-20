<?php include 'navbar.php'; ?>
<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
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
    
    // Handle restock request if form submitted
    if(isset($_POST['submit_restock']) && isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        if($quantity <= 0) {
            $error_message = "Please enter a valid quantity.";
        } else {
            // Start transaction
            $conn->beginTransaction();
            
            try {
                // Create new restock request
                $restock_sql = "INSERT INTO RestockRequest (Product_ID, Quantity, Status, RequestDate) 
                               VALUES (?, ?, 'Pending', NOW())";
                $restock_stmt = $conn->prepare($restock_sql);
                $restock_stmt->execute([$product_id, $quantity]);
                
                // Get the new restock request ID
                $restock_id = $conn->lastInsertId();
                
                // Get product name
                $name_sql = "SELECT Name FROM Product WHERE ID = ?";
                $name_stmt = $conn->prepare($name_sql);
                $name_stmt->execute([$product_id]);
                $product_name = $name_stmt->fetchColumn();
                
                // Commit transaction
                $conn->commit();
                
                $success_message = "Restock request #" . $restock_id . " for " . $quantity . " units of " . $product_name . " has been submitted successfully.";
            } catch(PDOException $e) {
                // Rollback transaction in case of error
                $conn->rollBack();
                $error_message = "Error processing restock request: " . $e->getMessage();
            }
        }
    }
    
    // Handle approving a restock request
    if(isset($_POST['approve_restock']) && isset($_POST['restock_id'])) {
        $restock_id = $_POST['restock_id'];
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Get restock request details
            $request_sql = "SELECT r.ID, r.Product_ID, r.Quantity, p.Buy_Price, p.Name as ProductName
                            FROM RestockRequest r
                            JOIN Product p ON r.Product_ID = p.ID
                            WHERE r.ID = ?";
            $request_stmt = $conn->prepare($request_sql);
            $request_stmt->execute([$restock_id]);
            $request = $request_stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$request) {
                throw new Exception("Restock request not found.");
            }
            
            // Calculate total cost
            $total_cost = $request['Quantity'] * $request['Buy_Price'];
            
            // Update product inventory
            $update_sql = "UPDATE Product SET Quantity = Quantity + ? WHERE ID = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([$request['Quantity'], $request['Product_ID']]);
            
            // Update store balance - reduce by total cost
            $update_balance_sql = "UPDATE Store SET Balance = Balance - ? WHERE ID = 1";
            $update_balance_stmt = $conn->prepare($update_balance_sql);
            $update_balance_stmt->execute([$total_cost]);
            
            // Update restock request status
            $update_request_sql = "UPDATE RestockRequest SET Status = 'Approved', CompletionDate = NOW() WHERE ID = ?";
            $update_request_stmt = $conn->prepare($update_request_sql);
            $update_request_stmt->execute([$restock_id]);
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Restock request #" . $restock_id . " for " . $request['Quantity'] . " units of " . 
                              $request['ProductName'] . " has been approved. Total cost: $" . number_format($total_cost, 2) . ".";
        } catch(Exception $e) {
            // Rollback transaction in case of error
            $conn->rollBack();
            $error_message = "Error approving restock request: " . $e->getMessage();
        }
    }
    
    // Handle rejecting a restock request
    if(isset($_POST['reject_restock']) && isset($_POST['restock_id'])) {
        $restock_id = $_POST['restock_id'];
        
        try {
            // Update restock request status
            $update_request_sql = "UPDATE RestockRequest SET Status = 'Rejected', CompletionDate = NOW() WHERE ID = ?";
            $update_request_stmt = $conn->prepare($update_request_sql);
            $update_request_stmt->execute([$restock_id]);
            
            $success_message = "Restock request #" . $restock_id . " has been rejected.";
        } catch(PDOException $e) {
            $error_message = "Error rejecting restock request: " . $e->getMessage();
        }
    }
    
    // Get store balance
    $balance_sql = "SELECT Balance FROM Store WHERE ID = 1";
    $balance_stmt = $conn->prepare($balance_sql);
    $balance_stmt->execute();
    $store_balance = $balance_stmt->fetchColumn();
    
    // Prepare query to retrieve inventory
    $sql = "SELECT p.ID, p.Name, p.Quantity, p.Buy_Price, p.Sell_Price, p.Category 
            FROM Product p
            ORDER BY p.Category, p.Name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    // Set fetch mode to associative array
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get inventory statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total_products,
                    SUM(Quantity) as total_units,
                    SUM(Quantity * Sell_Price) as inventory_value,
                    COUNT(CASE WHEN Quantity = 0 THEN 1 END) as out_of_stock,
                    COUNT(CASE WHEN Quantity > 0 AND Quantity < 5 THEN 1 END) as low_stock
                  FROM Product";
    
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->execute();
    $inventory_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get category statistics
    $category_sql = "SELECT 
                        Category,
                        COUNT(*) as product_count,
                        SUM(Quantity) as total_quantity,
                        SUM(Quantity * Sell_Price) as category_value
                     FROM Product
                     GROUP BY Category
                     ORDER BY category_value DESC";
    
    $category_stmt = $conn->prepare($category_sql);
    $category_stmt->execute();
    $category_stats = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all suppliers - Removing since there's no Supplier table in this structure
    /*
    $supplier_sql = "SELECT ID, Name, Category FROM Supplier ORDER BY Name";
    $supplier_stmt = $conn->prepare($supplier_sql);
    $supplier_stmt->execute();
    $suppliers = $supplier_stmt->fetchAll(PDO::FETCH_ASSOC);
    */
    
    // Get pending restock requests
    $pending_sql = "SELECT r.ID, r.Product_ID, r.Quantity, r.Status, r.RequestDate,
                          p.Name as ProductName, p.Buy_Price, p.Sell_Price
                   FROM RestockRequest r
                   JOIN Product p ON r.Product_ID = p.ID
                   WHERE r.Status = 'Pending'
                   ORDER BY r.RequestDate DESC";
    $pending_stmt = $conn->prepare($pending_sql);
    $pending_stmt->execute();
    $pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get completed restock requests (approved or rejected)
    $completed_sql = "SELECT r.ID, r.Product_ID, r.Quantity, r.Status, 
                             r.RequestDate, r.CompletionDate,
                             p.Name as ProductName, p.Buy_Price, p.Sell_Price
                      FROM RestockRequest r
                      JOIN Product p ON r.Product_ID = p.ID
                      WHERE r.Status IN ('Approved', 'Rejected')
                      ORDER BY r.CompletionDate DESC
                      LIMIT 50";
    $completed_stmt = $conn->prepare($completed_sql);
    $completed_stmt->execute();
    $completed_requests = $completed_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "<div style='color:red; padding:20px; background-color:#ffeeee; border:1px solid #ff0000;'>";
    echo "<h2>Database Connection Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database credentials and connection.</p>";
    echo "</div>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
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
h1, h2 {
    color: #333;
    margin-top: 0;
}
h1 {
    text-align: center;
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
.low-stock {
    background-color: #ffcccc;
}
.out-of-stock {
    background-color: #ffeeee;
    color: #cc0000;
    font-weight: bold;
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
.restock-btn {
    background-color: #1e5631; /* Darker green */
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    min-width: 80px;
}
.restock-btn:hover {
    background-color: #2e7d41; /* Consistent hover state */
}
.approve-btn {
    background-color: #28a745; /* Green */
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}
.approve-btn:hover {
    background-color: #218838;
}
.reject-btn {
    background-color: #dc3545; /* Red */
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}
.reject-btn:hover {
    background-color: #c82333;
}
.status-pending {
    background-color: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
}
.status-approved {
    background-color: #d4edda;
    color: #155724;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
}
.status-rejected {
    background-color: #f8d7da;
    color: #721c24;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
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
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 500px;
    border-radius: 5px;
    text-align: center;
    border-top: 3px solid #1e5631; /* Added accent border */
}
.modal h2 {
    color: #1e5631; /* Darker green */
    margin-top: 0;
}
.form-group {
    margin-bottom: 15px;
    text-align: left;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
.form-group input, .form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}
.form-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}
.form-buttons button {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.store-balance-banner {
    background-color: #eaf7fd; /* Light blue background */
    padding: 15px;
    border: 1px solid #0077b6; /* Thin dark blue border matching text color */
    margin: 20px auto; /* Space between navbar and content */
    display: flex;
    justify-content: center; /* Center-aligned text */
    align-items: center;
    max-width: 1200px; /* Match container width */
    border-radius: 5px;
    text-align: center;
}
.store-balance-banner .amount {
    font-weight: bold;
    font-size: 24px;
    color: #0077b6; /* Blue text color */
}
.dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background-color: #fff;
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    text-align: center;
}
.stat-card h3 {
    margin-top: 0;
    color: #1e5631;
}
.stat-card .value {
    font-size: 28px;
    font-weight: bold;
    margin: 10px 0;
}
.category-table {
    margin-top: 30px;
}
.filters {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}
select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.tab-container {
    margin-bottom: 20px;
}
.tab {
    overflow: hidden;
    border: 1px solid #ccc;
    background-color: #f1f1f1;
    border-radius: 5px 5px 0 0;
}
.tab button {
    background-color: inherit;
    float: left;
    border: none;
    outline: none;
    cursor: pointer;
    padding: 14px 16px;
    transition: 0.3s;
    font-size: 16px;
    font-weight: bold;
}
.tab button:hover {
    background-color: #ddd;
}
.tab button.active {
    background-color: #1e5631;
    color: white;
}
.tabcontent {
    display: none;
    padding: 20px;
    border: 1px solid #ccc;
    border-top: none;
    border-radius: 0 0 5px 5px;
    animation: fadeEffect 1s;
}
@keyframes fadeEffect {
    from {opacity: 0;}
    to {opacity: 1;}
}
.tabcontent.active {
    display: block;
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
    
    <!-- Store Balance Banner -->
    <div class="store-balance-banner">
        <span class="amount">Store Balance: $<?php echo number_format($store_balance, 2); ?></span>
    </div>
    
    <div class="container">
        <h1>Inventory Management</h1>
        
        <!-- Dashboard -->
        <div class="dashboard">
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="value"><?php echo $inventory_stats['total_products']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Units</h3>
                <div class="value"><?php echo $inventory_stats['total_units']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Inventory Value</h3>
                <div class="value">$<?php echo number_format($inventory_stats['inventory_value'], 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Out of Stock</h3>
                <div class="value"><?php echo $inventory_stats['out_of_stock']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Low Stock</h3>
                <div class="value"><?php echo $inventory_stats['low_stock']; ?></div>
            </div>
        </div>
        
        <!-- Tab Navigation -->
        <div class="tab-container">
            <div class="tab">
                <button class="tablinks active" onclick="openTab(event, 'InventoryTab')">Inventory</button>
                <button class="tablinks" onclick="openTab(event, 'CategoryTab')">Categories</button>
                <button class="tablinks" onclick="openTab(event, 'RestockTab')">Restock Requests</button>
                <button class="tablinks" onclick="openTab(event, 'HistoryTab')">Restock History</button>
            </div>
            
            <!-- Inventory Tab -->
            <div id="InventoryTab" class="tabcontent active">
                <div class="search-container">
                    <form action="" method="GET">
                        <input type="text" name="search" placeholder="Search by product name..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit">Search</button>
                    </form>
                </div>
                
                <div class="filters">
                    <form action="" method="GET">
                        <select name="category" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php
                            foreach($category_stats as $category) {
                                $selected = (isset($_GET['category']) && $_GET['category'] == $category['Category']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($category['Category']) . "' $selected>" . 
                                    htmlspecialchars($category['Category']) . "</option>";
                            }
                            ?>
                        </select>
                    </form>
                    <form action="" method="GET">
                        <select name="stock_status" onchange="this.form.submit()">
                            <option value="">All Stock Levels</option>
                            <option value="out" <?php echo (isset($_GET['stock_status']) && $_GET['stock_status'] == 'out') ? 'selected' : ''; ?>>Out of Stock</option>
                            <option value="low" <?php echo (isset($_GET['stock_status']) && $_GET['stock_status'] == 'low') ? 'selected' : ''; ?>>Low Stock (< 5)</option>
                            <option value="in" <?php echo (isset($_GET['stock_status']) && $_GET['stock_status'] == 'in') ? 'selected' : ''; ?>>In Stock</option>
                        </select>
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>In Stock</th>
                            <th>Buy Price</th>
                            <th>Sell Price</th>
                            <th>Margin</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search_filter = isset($_GET['search']) ? strtolower($_GET['search']) : '';
                        $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
                        $stock_filter = isset($_GET['stock_status']) ? $_GET['stock_status'] : '';
                        
                        foreach($inventory as $product) {
                            // Apply filters
                            if (!empty($search_filter) && strpos(strtolower($product['Name']), $search_filter) === false) {
                                continue;
                            }
                            
                            if (!empty($category_filter) && $product['Category'] != $category_filter) {
                                continue;
                            }
                            
                            if ($stock_filter == 'out' && $product['Quantity'] > 0) {
                                continue;
                            } else if ($stock_filter == 'low' && ($product['Quantity'] >= 5 || $product['Quantity'] == 0)) {
                                continue;
                            } else if ($stock_filter == 'in' && $product['Quantity'] == 0) {
                                continue;
                            }
                            
                            // Determine row class based on stock level
                            $row_class = '';
                            if ($product['Quantity'] == 0) {
                                $row_class = 'out-of-stock';
                            } else if ($product['Quantity'] < 5) {
                                $row_class = 'low-stock';
                            }
                            
                            // Calculate margin
                            $margin = $product['Sell_Price'] - $product['Buy_Price'];
                            $margin_percent = ($product['Buy_Price'] > 0) ? 
                                              ($margin / $product['Buy_Price']) * 100 : 0;
                            
                            echo "<tr class='$row_class'>";
                            echo "<td>" . htmlspecialchars($product['Name']) . "</td>";
                            echo "<td>" . htmlspecialchars($product['Category']) . "</td>";
                            echo "<td>" . $product['Quantity'] . "</td>";
                            echo "<td>$" . number_format($product['Buy_Price'], 2) . "</td>";
                            echo "<td>$" . number_format($product['Sell_Price'], 2) . "</td>";
                            echo "<td>" . number_format($margin_percent, 1) . "%</td>";
                            echo "<td>";
                            echo "<button class='restock-btn' onclick='openRestockModal(" . $product['ID'] . ", \"" . htmlspecialchars($product['Name']) . "\", " . $product['Buy_Price'] . ")'>Restock</button>";
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Category Tab -->
            <div id="CategoryTab" class="tabcontent">
                <h2>Category Analysis</h2>
                <table class="category-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Products</th>
                            <th>Total Units</th>
                            <th>Total Value</th>
                            <th>Avg. Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach($category_stats as $category) {
                            $avg_price = ($category['total_quantity'] > 0) ? 
                                         $category['category_value'] / $category['total_quantity'] : 0;
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($category['Category']) . "</td>";
                            echo "<td>" . $category['product_count'] . "</td>";
                            echo "<td>" . $category['total_quantity'] . "</td>";
                            echo "<td>$" . number_format($category['category_value'], 2) . "</td>";
                            echo "<td>$" . number_format($avg_price, 2) . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Restock Requests Tab -->
            <div id="RestockTab" class="tabcontent">
                <h2>Pending Restock Requests</h2>
                <?php if(empty($pending_requests)): ?>
                <p>No pending restock requests.</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Total Cost</th>
                            <th>Date Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach($pending_requests as $request) {
                            $total_cost = $request['Quantity'] * $request['Buy_Price'];
                            
                            echo "<tr>";
                            echo "<td>#" . $request['ID'] . "</td>";
                            echo "<td>" . htmlspecialchars($request['ProductName']) . "</td>";
                            echo "<td>" . $request['Quantity'] . "</td>";
                            echo "<td>$" . number_format($total_cost, 2) . "</td>";
                            echo "<td>" . date('M d, Y', strtotime($request['RequestDate'])) . "</td>";
                            echo "<td>";
                            
                            // Approval form
                            echo "<form method='POST' action='' style='display: inline;'>";
                            echo "<input type='hidden' name='approve_restock' value='1'>";
                            echo "<input type='hidden' name='restock_id' value='" . $request['ID'] . "'>";
                            echo "<button type='submit' class='approve-btn'>Approve</button>";
                            echo "</form> ";
                            
                            // Rejection form
                            echo "<form method='POST' action='' style='display: inline;'>";
                            echo "<input type='hidden' name='reject_restock' value='1'>";
                            echo "<input type='hidden' name='restock_id' value='" . $request['ID'] . "'>";
                            echo "<button type='submit' class='reject-btn'>Reject</button>";
                            echo "</form>";
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Restock History Tab -->
            <div id="HistoryTab" class="tabcontent">
                <h2>Restock History</h2>
                <?php if(empty($completed_requests)): ?>
                <p>No completed restock requests.</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Total Cost</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Completion Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach($completed_requests as $request) {
                            $total_cost = $request['Quantity'] * $request['Buy_Price'];
                            $status_class = ($request['Status'] == 'Approved') ? 'status-approved' : 'status-rejected';
                            
                            echo "<tr>";
                            echo "<td>#" . $request['ID'] . "</td>";
                            echo "<td>" . htmlspecialchars($request['ProductName']) . "</td>";
                            echo "<td>" . $request['Quantity'] . "</td>";
                            echo "<td>$" . number_format($total_cost, 2) . "</td>";
                            echo "<td><span class='$status_class'>" . $request['Status'] . "</span></td>";
                            echo "<td>" . date('M d, Y', strtotime($request['RequestDate'])) . "</td>";
                            echo "<td>" . date('M d, Y', strtotime($request['CompletionDate'])) . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Restock Modal -->
    <div id="restockModal" class="modal">
        <div class="modal-content">
            <h2>Request Restock</h2>
            <p>Enter restock details for <span id="product-name-display"></span></p>
            
            <form id="restock-form" method="POST" action="">
                <div class="form-group">
                    <label for="quantity">Quantity to Request:</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label for="buy_price">Buy Price Per Unit:</label>
                    <input type="number" id="buy_price" name="buy_price" min="0.01" step="0.01" readonly>
                </div>
                <div class="form-group">
                    <label for="total_cost">Estimated Total Cost:</label>
                    <input type="text" id="total_cost" readonly>
                </div>
                
                <input type="hidden" name="submit_restock" value="1">
                <input type="hidden" name="product_id" id="product_id" value="">
                
                <div class="form-buttons">
                    <button type="button" onclick="closeRestockModal()" style="background-color: #999; color: white;">Cancel</button>
                    <button type="submit" style="background-color: #1e5631; color: white;">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Function to open tab
        function openTab(evt, tabName) {
            // Hide all tabs
            var tabcontent = document.getElementsByClassName("tabcontent");
            for (var i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            // Remove active class from buttons
            var tablinks = document.getElementsByClassName("tablinks");
            for (var i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            
            // Show the selected tab
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        
        // Function to open restock modal
        function openRestockModal(productId, productName, buyPrice) {
            document.getElementById('product-name-display').textContent = productName;
            document.getElementById('product_id').value = productId;
            document.getElementById('buy_price').value = buyPrice.toFixed(2);
            document.getElementById('quantity').value = 1;
            
            // Calculate initial total cost
            calculateTotalCost();
            
            document.getElementById('restockModal').style.display = 'block';
        }
        
        // Function to close restock modal
        function closeRestockModal() {
            document.getElementById('restockModal').style.display = 'none';
        }
        
        // Function to calculate total cost
        function calculateTotalCost() {
            const quantity = document.getElementById('quantity').value;
            const buyPrice = document.getElementById('buy_price').value;
            const totalCost = quantity * buyPrice;
            
            document.getElementById('total_cost').value = ' + totalCost.toFixed(2);
        }
        
        // Attach event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Calculate total cost when values change
            document.getElementById('quantity').addEventListener('input', calculateTotalCost);
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target == document.getElementById('restockModal')) {
                    closeRestockModal();
                }
            });
        });
    </script>
</body>
</html>

<?php
// Close the connection
$conn = null;
?>