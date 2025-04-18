<?php
  // Get current file name (e.g., "products.php")
  $currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
  nav {
    background-color: #333;
    padding: 15px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  }
  nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    gap: 30px;
    justify-content: flex-start;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
  }
  nav ul li a {
    text-decoration: none;
    color: #f2f2f2;
    font-weight: bold;
    transition: all 0.3s ease;
    padding: 8px 15px;
    border-radius: 3px;
    font-size: 16px;
  }
  nav ul li a:hover {
    color: #ffcc00;
    background-color: rgba(255, 255, 255, 0.1);
  }
  nav ul li a.active {
    color: #ffcc00;
    border-bottom: 2px solid #ffcc00;
  }
  .logo {
    font-size: 22px;
    font-weight: bold;
    color: #ffcc00;
    margin-right: 20px;
  }
  body {
    margin: 0;
    font-family: Arial, sans-serif;
    padding-top: 0; /* Adjust if needed for fixed navbar */
  }
  /* Add spacing for cart button */
  .nav-spacer {
    margin-top: 70px; /* Adjust to match navbar height */
  }
</style>
<nav>
  <ul>
    <li class="logo">Grocery Store</li>
    <li><a href="/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a></li>
    <li><a href="/products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">Products</a></li>
    <li><a href="/orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">Orders</a></li>
    <li><a href="/inventory.php" class="<?= $currentPage === 'inventory.php' ? 'active' : '' ?>">Inventory</a></li>
    <!-- Shopping Cart link removed as requested -->
  </ul>
</nav>