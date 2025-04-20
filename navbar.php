<?php
  // Get current file name (e.g., "products.php")
  $currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
  nav {
    background-color: #4CAF50;
    padding: 15px 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
    color: white;
    font-weight: bold;
    transition: all 0.3s ease;
    padding: 8px 15px;
    border-radius: 4px;
    font-size: 16px;
  }

  nav ul li a:hover {
    background-color: #45a049;
    color: #fff;
  }

  nav ul li a.active {
    background-color: #388E3C;
    color: white;
    border-bottom: 2px solid #ffcc00;
  }

  .logo {
    font-size: 22px;
    font-weight: bold;
    color: #ffcc00;
    margin-right: 20px;
  }

  /* Invisible links (Orders & Inventory) */
  .invisible-link {
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
  }

  /* Become visible when hovered individually */
  .invisible-link:hover,
  nav ul li:hover .invisible-link {
    opacity: 1;
    pointer-events: auto;
  }
</style>
<nav>
  <ul>
    <li class="logo">PubliCS</li>
    <li><a href="/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a></li>
    <li><a href="/products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">Products</a></li>
    <li><a href="/orders.php" class="invisible-link <?= $currentPage === 'orders.php' ? 'active' : '' ?>">Orders</a></li>
    <li><a href="/inventory.php" class="invisible-link <?= $currentPage === 'inventory.php' ? 'active' : '' ?>">Inventory</a></li>
  </ul>
</nav>