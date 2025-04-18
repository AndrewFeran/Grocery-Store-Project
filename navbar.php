<?php
  // Get current file name (e.g., "products.php")
  $currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
  nav {
    background-color: #333;
    padding: 10px 20px;
  }

  nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    gap: 30px;
  }

  nav ul li a {
    text-decoration: none;
    color: #f2f2f2;
    font-weight: bold;
    transition: color 0.3s, border-bottom 0.3s;
    padding-bottom: 3px;
  }

  nav ul li a:hover {
    color: #ffcc00;
  }

  nav ul li a.active {
    color: #ffcc00;
    border-bottom: 2px solid #ffcc00;
  }

  body {
    margin: 0;
    font-family: Arial, sans-serif;
  }
</style>

<nav>
  <ul>
    <li><a href="/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a></li>
    <li><a href="/products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">Products</a></li>
    <li><a href="/orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">Orders</a></li>
    <li><a href="/inventory.php" class="<?= $currentPage === 'inventory.php' ? 'active' : '' ?>">Inventory</a></li>
    <li><a href="/cart.php" class="<?= $currentPage === 'cart.php' ? 'active' : '' ?>">Shopping Cart</a></li>
  </ul>
</nav>
