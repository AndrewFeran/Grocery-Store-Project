<?php
  // Get current file name (e.g., "products.php")
  $currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
  nav {
    background-color: #4CAF50;
    padding: 15px 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 999;
  }

  nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    gap: 25px;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
  }

  nav ul li a {
    text-decoration: none;
    color: white;
    font-weight: bold;
    font-size: 16px;
    padding: 8px 14px;
    border-radius: 4px;
    transition: background-color 0.3s, opacity 0.3s ease;
  }

  nav ul li a:hover {
    background-color: #45a049;
  }

  nav ul li a.active {
    background-color: #388E3C;
    border-bottom: 2px solid #ffcc00;
  }

  .logo {
    font-size: 24px;
    font-weight: bold;
    color: #ffcc00;
    margin-right: 30px;
  }

  /* Invisible links (Orders & Inventory) */
  .invisible-link {
    opacity: 0;
    pointer-events: none;
  }

  nav ul li:hover .invisible-link,
  .invisible-link:hover {
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