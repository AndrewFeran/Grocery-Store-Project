<!-- Products.php changes - PARTIAL FILE ONLY showing the parts that need to be changed -->

<!-- 1. REMOVE this cart button from the beginning of the body -->
<!-- DELETE THIS:
<button id="cart-toggle">
    <i class="fa fa-shopping-cart"></i> Cart <span id="cart-badge" class="cart-badge">0</span>
</button>
-->

<!-- 2. REPLACE the content of the style tag with this new CSS -->
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
    width: 400px;
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
/* Store balance styling */
.store-info {
    background-color: #e9f5fb;
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #b8e1f3;
    text-align: right;
}
.store-balance {
    font-weight: bold;
    color: #0077b6;
}
</style>

<!-- 3. Find this script that handles cart toggle button and update it -->
<!-- Look for these lines in your JavaScript code and update it to this: -->
<script>
    // Document ready function
    document.addEventListener('DOMContentLoaded', function() {
        updateCartDisplay();
        updateCartBadge();
        
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
    
    // No need to add a toggle event listener here since it's now in the navbar
    
    // Rest of your cart functions remain the same...
</script>