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
$customer_info = null;

try {
    // Create connection using PDO
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Find customer by email if form submitted
    if(isset($_POST['find_customer']) && isset($_POST['customer_email'])) {
        $email = trim($_POST['customer_email']);
        if(!empty($email)) {
            // Prepare query to find customer by email
            $find_sql = "SELECT * FROM Customer WHERE Email = ?";
            $find_stmt = $conn->prepare($find_sql);
            $find_stmt->execute([$email]);
            $customer_info = $find_stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$customer_info) {
                // No customer found - show registration form
                $error_message = "No account found with that email. Please register as a new customer.";
            }
        } else {
            $error_message = "Please enter a valid email address.";
        }
    }
    
    // Register new customer if form submitted
    if(isset($_POST['register_customer'])) {
        $name = trim($_POST['customer_name'] ?? '');
        $email = trim($_POST['customer_email'] ?? '');
        $address = trim($_POST['customer_address'] ?? '');
        
        if(empty($name) || empty($email) || empty($address)) {
            $error_message = "All fields are required for registration.";
        } else {
            // Check if email already exists
            $check_sql = "SELECT COUNT(*) FROM Customer WHERE Email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$email]);
            $exists = (int)$check_stmt->fetchColumn() > 0;
            
            if($exists) {
                $error_message = "An account with this email already exists.";
            } else {
                // Insert new customer
                $register_sql = "INSERT INTO Customer (Name, Email, Address) VALUES (?, ?, ?)";
                $register_stmt = $conn->prepare($register_sql);
                $register_stmt->execute([$name, $email, $address]);
                
                // Get the new customer ID
                $customer_id = $conn->lastInsertId();
                
                // Get customer info
                $find_sql = "SELECT * FROM Customer WHERE ID = ?";
                $find_stmt = $conn->prepare($find_sql);
                $find_stmt->execute([$customer_id]);
                $customer_info = $find_stmt->fetch(PDO::FETCH_ASSOC);
                
                $success_message = "Account created successfully! You can now proceed with checkout.";
            }
        }
    }
    
    // Process checkout form submission
    if(isset($_POST['checkout']) && isset($_POST['cart_data']) && isset($_POST['customer_id'])) {
        $cartData = json_decode($_POST['cart_data'], true);
        $customer_id = (int)$_POST['customer_id'];
        
        if(empty($cartData)) {
            $error_message = "Your cart is empty.";
        } elseif($customer_id <= 0) {
            $error_message = "Please select or register a customer account before checkout.";
        } else {
            // Start transaction
            $conn->beginTransaction();
            
            try {
                // Get current date for order
                $date = date('Y-m-d H:i:s');
                
                // Create new order with date and customer ID
                $order_sql = "INSERT INTO `Order` (Customer_ID, OrderDate) VALUES (?, ?)";
                $order_stmt = $conn->prepare($order_sql);
                $order_stmt->execute([$customer_id, $date]);
                
                // Get the new order ID
                $order_id = $conn->lastInsertId();
                
                // Insert order items
                $item_sql = "INSERT INTO OrderItem (Product_ID, Order_ID) VALUES (?, ?)";
                $item_stmt = $conn->prepare($item_sql);
                
                // Update product inventory
                $update_sql = "UPDATE Product SET Quantity = Quantity - ? WHERE ID = ?";
                $update_stmt = $conn->prepare($update_sql);
                
                // Calculate total sale amount to update store balance
                $total_sale_amount = 0;
                
                foreach($cartData as $item) {
                    // Insert each product in the cart
                    for($i = 0; $i < $item['quantity']; $i++) {
                        $item_stmt->execute([$item['id'], $order_id]);
                    }
                    
                    // Update product inventory - reduce by quantity ordered
                    $update_stmt->execute([$item['quantity'], $item['id']]);
                    
                    // Add to total sale amount
                    $total_sale_amount += $item['price'] * $item['quantity'];
                }
                
                // Update store balance (we keep this for database integrity, but don't show it to the user)
                $update_balance_sql = "UPDATE Store SET Balance = Balance + ? WHERE ID = 1";
                $update_balance_stmt = $conn->prepare($update_balance_sql);
                $update_balance_stmt->execute([$total_sale_amount]);
                
                // Commit transaction
                $conn->commit();
                
                // Get customer name
                $name_sql = "SELECT Name FROM Customer WHERE ID = ?";
                $name_stmt = $conn->prepare($name_sql);
                $name_stmt->execute([$customer_id]);
                $customer_name = $name_stmt->fetchColumn();
                
                // Format date for display
                $formatted_date = date('F j, Y', strtotime($date));
                
                // Set success message in session
                $_SESSION['order_success'] = "Order #" . $order_id . " has been placed successfully on " . $formatted_date . " for " . $customer_name . "!";
                
                // Redirect to prevent form resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
                
            } catch(PDOException $e) {
                // Rollback transaction in case of error
                $conn->rollBack();
                $error_message = "Error processing order: " . $e->getMessage();
            }
        }
    }
    
    // Check for success message in session
    if(isset($_SESSION['order_success'])) {
        $success_message = $_SESSION['order_success'];
        // Clear the session variable to prevent showing the message again on refresh
        unset($_SESSION['order_success']);
    }
    
    // Prepare query to retrieve products
    $sql = "SELECT ID, Name, Quantity, Sell_Price, Category 
            FROM Product 
            ORDER BY Name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    // Set fetch mode to associative array
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    <title>Products</title>
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
.low-stock {
    background-color: #ffcccc;
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
.add-to-cart {
    background-color: #1e5631; /* Darker green */
    color: white; /* Changed from black to white for better contrast */
    border: none;
    padding: 8px 16px;
    border-radius: 4px; /* Made consistent with other buttons */
    cursor: pointer;
    min-width: 110px;
    text-align: center;
    white-space: nowrap;
}
.add-to-cart:hover {
    background-color: #2e7d41; /* Consistent hover state */
}
#cart-container {
    position: fixed;
    top: 80px;
    right: 20px;
    background-color: white;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    max-width: 300px;
    z-index: 1000;
    max-height: 80vh;
    overflow-y: auto;
    border-top: 3px solid #1e5631; /* Added accent border */
}
#cart-items {
    margin-top: 10px;
    max-height: 300px;
    overflow-y: auto;
}
.cart-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}
.remove-item {
    color: #f44336;
    cursor: pointer;
    margin-left: 10px;
}
.cart-total {
    margin-top: 10px;
    font-weight: bold;
    text-align: right;
}
.cart-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
}
.cart-buttons button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.checkout-btn {
    background-color: #1e5631; /* Darker green */
    color: white;
}
.checkout-btn:hover {
    background-color: #2e7d41; /* Consistent hover state */
}
.clear-btn {
    background-color: #f44336;
    color: white;
}
.clear-btn:hover {
    background-color: #e53935; /* Added hover state */
}
.quantity-control {
    display: flex;
    align-items: center;
}
.quantity-control input {
    width: 40px;
    text-align: center;
    margin: 0 5px;
    padding: 3px;
}
.quantity-control button {
    background-color: #ddd;
    border: none;
    width: 25px;
    height: 25px;
    cursor: pointer;
    font-weight: bold;
    border-radius: 4px; /* Made consistent */
}
.quantity-control button:hover {
    background-color: #ccc; /* Added hover state */
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
.modal p {
    margin-bottom: 20px;
}
.modal button {
    padding: 10px 20px;
    background-color: #1e5631; /* Darker green */
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}
.modal button:hover {
    background-color: #2e7d41; /* Consistent hover state */
}
/* Disabled state for buttons */
.disabled {
    opacity: 0.5;
    cursor: not-allowed !important;
}
/* Out of stock styling */
.out-of-stock {
    color: #721c24;
    font-weight: bold;
}
/* Customer form styling */
.customer-form {
    margin-top: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
}
.customer-form h3 {
    margin-top: 0;
    color: #1e5631;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
.form-group input {
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
.customer-info {
    background-color: #e9f5fb;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #b8e1f3;
}
.customer-name {
    font-weight: bold;
    color: #1e5631;
}
.btn-block {
    display: block;
    width: 100%;
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
    
    <div id="cart-container" style="display: none;">
        <h3>Shopping Cart</h3>
        <div id="cart-items">
            <!-- Cart items will be added here dynamically -->
        </div>
        <div class="cart-total">Total: $<span id="cart-total">0.00</span></div>
        <div class="cart-buttons">
            <button class="clear-btn" onclick="clearCart()">Clear Cart</button>
            <button class="checkout-btn" onclick="checkoutConfirm()">Checkout</button>
        </div>
    </div>
    
    <!-- Checkout confirmation modal -->
    <div id="checkout-modal" class="modal">
        <div class="modal-content">
            <h2>Customer Information</h2>
            
            <?php if(isset($customer_info) && $customer_info): ?>
                <div class="customer-info">
                    <p>You are checking out as: <span class="customer-name"><?php echo htmlspecialchars($customer_info['Name']); ?></span></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($customer_info['Email']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($customer_info['Address']); ?></p>
                </div>
                <div class="form-buttons">
                    <button onclick="closeModal()" style="background-color: #f44336;">Cancel</button>
                    <button onclick="completeCheckout(<?php echo $customer_info['ID']; ?>)">Place Order</button>
                </div>
            <?php else: ?>
                <div class="tab">
                    <button class="tablinks active" onclick="openTab(event, 'ExistingCustomer')">Existing Customer</button>
                    <button class="tablinks" onclick="openTab(event, 'NewCustomer')">New Customer</button>
                </div>
                
                <div id="ExistingCustomer" class="tabcontent" style="display: block;">
                    <form id="existing-customer-form" method="POST" action="">
                        <div class="form-group">
                            <label for="customer_email">Email Address:</label>
                            <input type="email" id="customer_email" name="customer_email" required>
                        </div>
                        <input type="hidden" name="find_customer" value="1">
                        <button type="submit" class="btn-block">Find Account</button>
                    </form>
                </div>
                
                <div id="NewCustomer" class="tabcontent">
                    <form id="new-customer-form" method="POST" action="">
                        <div class="form-group">
                            <label for="new_customer_name">Full Name:</label>
                            <input type="text" id="new_customer_name" name="customer_name" required>
                        </div>
                        <div class="form-group">
                            <label for="new_customer_email">Email Address:</label>
                            <input type="email" id="new_customer_email" name="customer_email" required>
                        </div>
                        <div class="form-group">
                            <label for="customer_address">Address:</label>
                            <input type="text" id="customer_address" name="customer_address" required>
                        </div>
                        <input type="hidden" name="register_customer" value="1">
                        <button type="submit" class="btn-block">Register & Continue</button>
                    </form>
                </div>
                
                <div style="margin-top: 20px;">
                    <button onclick="closeModal()" style="background-color: #f44336;">Cancel Checkout</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Success modal -->
    <div id="success-modal" class="modal">
        <div class="modal-content">
            <h2>Order Successful!</h2>
            <p id="success-message">Your order has been placed successfully.</p>
            <button onclick="closeSuccessModal()">Continue Shopping</button>
        </div>
    </div>

    <div class="container">
        <h1>Products</h1>
        
        <div class="search-container">
            <form action="" method="GET">
                <input type="text" name="search" placeholder="Search by product name..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit">Search</button>
            </form>
        </div>
        
        <?php
        try {
            // Get all categories for the filter
            $cat_query = "SELECT DISTINCT Category FROM Product ORDER BY Category";
            $cat_stmt = $conn->prepare($cat_query);
            $cat_stmt->execute();
            $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Filter by category if selected
            $category_filter = isset($_GET['category']) && !empty($_GET['category']) ? $_GET['category'] : null;
            $search_filter = isset($_GET['search']) && !empty($_GET['search']) ? $_GET['search'] : null;
            
            // Prepare query with filters
            if ($category_filter || $search_filter) {
                $sql = "SELECT ID, Name, Quantity, Sell_Price, Category 
                        FROM Product 
                        WHERE 1=1 ";
                        
                if ($category_filter) {
                    $sql .= " AND Category = :category";
                }
                
                if ($search_filter) {
                    $sql .= " AND Name LIKE :search";
                }
                
                $sql .= " ORDER BY Name";
                
                $stmt = $conn->prepare($sql);
                
                if ($category_filter) {
                    $stmt->bindParam(':category', $category_filter);
                }
                
                if ($search_filter) {
                    $search_param = "%" . $search_filter . "%";
                    $stmt->bindParam(':search', $search_param);
                }
                
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
        } catch(PDOException $e) {
            echo "<p style='color:red'>Error loading filters: " . $e->getMessage() . "</p>";
        }
        ?>

        <div class="filters">
            <form action="" method="GET">
                <select name="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php
                    if(isset($categories)) {
                        foreach($categories as $category) {
                            $selected = (isset($_GET['category']) && $_GET['category'] == $category['Category']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($category['Category']) . "' $selected>" . 
                                htmlspecialchars($category['Category']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>In Stock</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($result)) {
                    foreach($result as $row) {
                        $row_class = ($row["Quantity"] < 5 && $row["Quantity"] > 0) ? "low-stock" : "";
                        $out_of_stock = $row["Quantity"] <= 0;
                        
                        echo "<tr class='$row_class'>";
                        echo "<td>" . htmlspecialchars($row["Name"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["Category"]) . "</td>";
                        
                        // Show stock status
                        if ($out_of_stock) {
                            echo "<td class='out-of-stock'>Out of stock</td>";
                        } else {
                            echo "<td>" . $row["Quantity"] . "</td>";
                        }
                        
                        echo "<td>$" . number_format($row["Sell_Price"], 2) . "</td>";
                        echo "<td>";
                        
                        if ($out_of_stock) {
                            echo "<button class='add-to-cart disabled' disabled>Out of Stock</button>";
                        } else {
                            echo "<div class='quantity-control'>";
                            echo "<button onclick='decrementQuantity(\"qty-{$row["ID"]}\")'>-</button>";
                            echo "<input type='number' id='qty-{$row["ID"]}' min='1' value='1' max='{$row["Quantity"]}'>";
                            echo "<button onclick='incrementQuantity(\"qty-{$row["ID"]}\", {$row["Quantity"]})'>+</button>";
                            echo "<button class='add-to-cart' onclick='addToCart({$row["ID"]}, \"".htmlspecialchars($row["Name"])."\", {$row["Sell_Price"]}, document.getElementById(\"qty-{$row["ID"]}\").value, {$row["Quantity"]})'>Add to Cart</button>";
                            echo "</div>";
                        }
                        
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No products found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; text-align: right;">
            <p>Total Products: <?php echo isset($result) ? count($result) : 0; ?></p>
        </div>
    </div>

    <!-- Checkout form (hidden) -->
    <form id="checkout-form" method="POST" action="" style="display: none;">
        <input type="hidden" name="checkout" value="1">
        <input type="hidden" name="cart_data" id="cart_data" value="">
        <input type="hidden" name="customer_id" id="customer_id" value="">
    </form>

    <script>
        // Initialize cart from localStorage or as empty array
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        // Display cart on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartDisplay();
            updateCartBadge();
            
            // Listen for the custom event from navbar cart button
            document.addEventListener('toggleCart', function() {
                const cartContainer = document.getElementById('cart-container');
                if (cartContainer.style.display === 'none') {
                    cartContainer.style.display = 'block';
                } else {
                    cartContainer.style.display = 'none';
                }
            });
            
            // Show success modal if there's a success message
            <?php if(isset($success_message)): ?>
            // Clear the cart after successful order
            cart = [];
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartDisplay();
            updateCartBadge();
            
            // Show success modal with the message
            document.getElementById('success-message').textContent = "<?php echo $success_message; ?>";
            document.getElementById('success-modal').style.display = 'block';
            <?php endif; ?>
        });
        
        // Function to add item to cart
        function addToCart(id, name, price, quantity, maxQuantity) {
            quantity = parseInt(quantity);
            if (quantity <= 0) {
                alert('Please enter a valid quantity');
                return;
            }
            
            if (quantity > maxQuantity) {
                alert(`Sorry, only ${maxQuantity} items in stock!`);
                return;
            }
            
            // Check if product already exists in cart
            const existingItemIndex = cart.findIndex(item => item.id === id);
            
            if (existingItemIndex !== -1) {
                // Update quantity if not exceeding stock
                const newQuantity = cart[existingItemIndex].quantity + quantity;
                if (newQuantity > maxQuantity) {
                    alert(`Sorry, adding ${quantity} more would exceed available stock. You already have ${cart[existingItemIndex].quantity} in your cart.`);
                    return;
                }
                cart[existingItemIndex].quantity = newQuantity;
            } else {
                // Add new item
                cart.push({
                    id: id,
                    name: name,
                    price: price,
                    quantity: quantity,
                    maxQuantity: maxQuantity
                });
            }
            
            // Save to localStorage
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Update UI
            updateCartDisplay();
            updateCartBadge();
            
            // Show confirmation
            alert(`${quantity} x ${name} added to cart!`);
        }
        
        // Function to remove item from cart
        function removeFromCart(index) {
            cart.splice(index, 1);
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartDisplay();
            updateCartBadge();
        }
        
        // Function to clear cart
        function clearCart() {
            if (confirm('Are you sure you want to clear your cart?')) {
                cart = [];
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartDisplay();
                updateCartBadge();
            }
        }
        
        // Function to open checkout confirmation modal
        function checkoutConfirm() {
            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }
            
            document.getElementById('checkout-modal').style.display = 'block';
        }
        
        // Function to close checkout modal
        function closeModal() {
            document.getElementById('checkout-modal').style.display = 'none';
        }
        
        // Function to close success modal
        function closeSuccessModal() {
            document.getElementById('success-modal').style.display = 'none';
        }
        
        // Function to complete checkout with customer ID
        function completeCheckout(customerId) {
            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }
            
            // Set the cart data and customer ID in the hidden form fields
            document.getElementById('cart_data').value = JSON.stringify(cart);
            document.getElementById('customer_id').value = customerId;
            
            // Submit the form
            document.getElementById('checkout-form').submit();
        }
        
        // Function to update cart display
        function updateCartDisplay() {
            const cartItemsContainer = document.getElementById('cart-items');
            const cartTotalElement = document.getElementById('cart-total');
            
            // Clear current items
            cartItemsContainer.innerHTML = '';
            
            if (cart.length === 0) {
                cartItemsContainer.innerHTML = '<p>Your cart is empty</p>';
                cartTotalElement.textContent = '0.00';
                return;
            }
            
            let totalPrice = 0;
            
            // Add each item to the cart display
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                totalPrice += itemTotal;
                
                const itemElement = document.createElement('div');
                itemElement.className = 'cart-item';
                itemElement.innerHTML = `
                    <div>
                        <span>${item.name}</span>
                        <span> x ${item.quantity}</span>
                    </div>
                    <div>
                        <span>$${itemTotal.toFixed(2)}</span>
                        <span class="remove-item" onclick="removeFromCart(${index})">✕</span>
                    </div>
                `;
                cartItemsContainer.appendChild(itemElement);
            });
            
            // Update total
            cartTotalElement.textContent = totalPrice.toFixed(2);
        }
        
        // Update cart badge with number of items
        function updateCartBadge() {
            const badge = document.getElementById('cart-badge');
            const itemCount = cart.reduce((total, item) => total + parseInt(item.quantity || 0), 0);
            
            badge.textContent = itemCount;
            badge.style.display = itemCount > 0 ? 'inline' : 'none';
        }
        
        // Increment quantity input
        function incrementQuantity(inputId, max) {
            const input = document.getElementById(inputId);
            const currentVal = parseInt(input.value);
            if (currentVal < max) {
                input.value = currentVal + 1;
            }
        }
        
        // Decrement quantity input
        function decrementQuantity(inputId) {
            const input = document.getElementById(inputId);
            const currentVal = parseInt(input.value);
            if (currentVal > 1) {
                input.value = currentVal - 1;
            }
        }
        
        // Function to switch between tabs in the checkout modal
        function openTab(evt, tabName) {
            // Declare all variables
            var i, tabcontent, tablinks;

            // Get all elements with class="tabcontent" and hide them
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }

            // Get all elements with class="tablinks" and remove the class "active"
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }

            // Show the current tab, and add an "active" class to the button that opened the tab
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
    </script>
</body>
</html>

<?php
// Close the connection
$conn = null;
?>