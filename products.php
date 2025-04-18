<?php include 'navbar.php'; ?>
<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
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
            background-color: #4CAF50;
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
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .add-to-cart:hover {
            background-color: #45a049;
        }
        #cart-container {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            max-width: 300px;
            z-index: 1000;
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
            color: red;
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
            background-color: #4CAF50;
            color: white;
        }
        .clear-btn {
            background-color: #f44336;
            color: white;
        }
        .cart-badge {
            background-color: #f44336;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            position: relative;
            top: -10px;
            left: -5px;
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
        }
        /* Cart toggle button */
        #cart-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 1001;
        }
    </style>
</head>
<body>
    <button id="cart-toggle">
        Cart <span id="cart-badge" class="cart-badge">0</span>
    </button>
    
    <div id="cart-container" style="display: none;">
        <h3>Shopping Cart</h3>
        <div id="cart-items">
            <!-- Cart items will be added here dynamically -->
        </div>
        <div class="cart-total">Total: $<span id="cart-total">0.00</span></div>
        <div class="cart-buttons">
            <button class="clear-btn" onclick="clearCart()">Clear Cart</button>
            <button class="checkout-btn" onclick="checkout()">Checkout</button>
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
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($result)) {
                    foreach($result as $row) {
                        $row_class = ($row["Quantity"] < 5) ? "low-stock" : "";
                        
                        echo "<tr class='$row_class'>";
                        echo "<td>" . htmlspecialchars($row["Name"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["Category"]) . "</td>";
                        echo "<td>" . $row["Quantity"] . "</td>";
                        echo "<td>$" . number_format($row["Sell_Price"], 2) . "</td>";
                        echo "<td>";
                        echo "<div class='quantity-control'>";
                        echo "<button onclick='decrementQuantity(\"qty-{$row["ID"]}\")'>-</button>";
                        echo "<input type='number' id='qty-{$row["ID"]}' min='1' value='1' max='{$row["Quantity"]}'>";
                        echo "<button onclick='incrementQuantity(\"qty-{$row["ID"]}\", {$row["Quantity"]})'>+</button>";
                        echo "<button class='add-to-cart' onclick='addToCart({$row["ID"]}, \"".htmlspecialchars($row["Name"])."\", {$row["Sell_Price"]}, document.getElementById(\"qty-{$row["ID"]}\").value, {$row["Quantity"]})'>Add to Cart</button>";
                        echo "</div>";
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

    <script>
        // Initialize cart from localStorage or as empty array
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        // Display cart on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartDisplay();
            updateCartBadge();
        });
        
        // Toggle cart visibility
        document.getElementById('cart-toggle').addEventListener('click', function() {
            const cartContainer = document.getElementById('cart-container');
            if (cartContainer.style.display === 'none') {
                cartContainer.style.display = 'block';
            } else {
                cartContainer.style.display = 'none';
            }
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
        
        // Function to checkout
        function checkout() {
            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }
            
            alert('Proceeding to checkout...');
            // Here you would normally redirect to a checkout page
            // window.location.href = 'checkout.php';
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
            const itemCount = cart.reduce((total, item) => total + item.quantity, 0);
            badge.textContent = itemCount;
            
            // Hide badge if cart is empty
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
    </script>
</body>
</html>

<?php
// Close the connection
$conn = null;
?>
