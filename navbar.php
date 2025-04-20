<?php
  // Get current file name (e.g., "products.php")
  $currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
  nav {
    background-color: #1e5631; /* Darker green */
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
    background-color: #2e7d41; /* Slightly lighter hover state */
  }

  nav ul li a.active {
    background-color: #133920; /* Even darker for active state */
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

  /* Cart button styling to match navbar */
  #cart-toggle {
    position: relative;
    top: 0;
    right: 0;
    background-color: #133920;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    transition: background-color 0.3s;
    display: flex;
    align-items: center;
  }

  #cart-toggle:hover {
    background-color: #2e7d41;
  }

  .cart-badge {
    background-color: #f44336;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 12px;
    margin-left: 5px;
  }
</style>

<nav>
  <ul>
    <li class="logo">PubliCS</li>
    <li><a href="/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a></li>
    <li><a href="/products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">Products</a></li>
    <li><a href="/orders.php" class="invisible-link <?= $currentPage === 'orders.php' ? 'active' : '' ?>">Orders</a></li>
    <li><a href="/inventory.php" class="invisible-link <?= $currentPage === 'inventory.php' ? 'active' : '' ?>">Inventory</a></li>
    <li style="margin-left: auto;">
      <button id="cart-toggle" type="button">
        <i class="fa fa-shopping-cart"></i> Cart <span id="cart-badge" class="cart-badge">0</span>
      </button>
    </li>
  </ul>
  
  <!-- Add Font Awesome for cart icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  
  <script>
    // Initialize cart from localStorage
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize cart from localStorage or as empty array
      let cart = JSON.parse(localStorage.getItem('cart')) || [];
      updateNavbarCartBadge();
      
      // Add click event for cart toggle button
      document.getElementById('cart-toggle').addEventListener('click', function() {
        // If we're on the products page
        if ('<?= $currentPage ?>' === 'products.php') {
          // Toggle cart visibility by triggering a custom event
          const toggleEvent = new CustomEvent('toggleCart');
          document.dispatchEvent(toggleEvent);
        } else {
          // Navigate to products page
          window.location.href = '/products.php';
        }
      });
      
      // Function to update cart badge count
      function updateNavbarCartBadge() {
        const badge = document.getElementById('cart-badge');
        const itemCount = cart.reduce((total, item) => total + parseInt(item.quantity || 0), 0);
        
        badge.textContent = itemCount;
        badge.style.display = itemCount > 0 ? 'inline' : 'none';
      }
    });
  </script>
</nav>