<?php
if (!isset($conn)) {
  include_once __DIR__ . '/config.php';
}

// ---- basket numbers (used by the Basket button and the basket panel below) ----
$cart = $_SESSION['cart_products'] ?? [];
$subtotal = 0;
$cartCount = 0;
foreach ($cart as $cartItem) {
  $subtotal += $cartItem['item_price'] * $cartItem['item_qty'];
  $cartCount += $cartItem['item_qty'];
}
$serviceFeeRate = 0;                        // 5% service fee. Set to 0 to hide the Service fee row.
$serviceFee = round($subtotal * $serviceFeeRate, 2);
$total = $subtotal + $serviceFee;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="/InfoMan-Project/includes/style/style.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css">
  <link href="/InfoMan-Project/includes/style/loginstyle.css" rel="stylesheet" type="text/css">
  <link href="/InfoMan-Project/includes/style/transaction.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
  </script>
  <title>Baguio Pasalubong Shop </title>
</head>

<body>
  <nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
      <a class="navbar-brand" href="/InfoMan-Project/index.php">My Shop</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="/InfoMan-Project/index.php">Home</a>
          </li>
         <?php
          $navCats = mysqli_query($conn,
            "SELECT DISTINCT c.category_id, c.category_name
             FROM tbl_categories c
             JOIN tbl_products p ON p.category_id = c.category_id
             WHERE p.product_status = 'Active'
             ORDER BY c.category_name");
          while ($c = mysqli_fetch_assoc($navCats)):
          ?>
            <li class="nav-item">
              <a class="nav-link" href="/InfoMan-Project/index.php?category=<?php echo (int) $c['category_id']; ?>">
                <?php echo htmlspecialchars($c['category_name']); ?>
              </a>
            </li>
          <?php endwhile; ?>
          

        </ul>
        <form action="/InfoMan-Project/search.php" method="GET" class="d-flex">
          <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search"
          name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
          <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
        </form>

        <div class="d-flex align-items-center ms-lg-auto gap-2">
          <!-- Basket button: opens the basket panel -->
          <button class="btn-basket" type="button" data-bs-toggle="offcanvas" data-bs-target="#basketPanel"
            aria-controls="basketPanel">
            <i class="fa-solid fa-basket-shopping"></i> Basket
            <?php if ($cartCount > 0): ?>
              <span class="basket-count"><?php echo (int) $cartCount; ?></span>
            <?php endif; ?>
          </button>

          <?php
          if (!isset($_SESSION['user_id'])) {
            echo "<div class='navbar-nav'>
                        <a href='http://{$_SERVER['SERVER_NAME']}/InfoMan-Project/user/login.php' class='nav-item nav-link'>Login</a></div>";
           } else {
            $u = mysqli_fetch_assoc(mysqli_execute_query($conn,
              "SELECT first_name, last_name, profile_photo FROM tbl_customers WHERE customer_id = ?",
              [$_SESSION['user_id']]));
            $photo = !empty($u['profile_photo']) ? '/InfoMan-Project/' . $u['profile_photo'] : '';
          ?>
            <div class="dropdown">
              <button class="btn dropdown-toggle d-flex align-items-center gap-2" type="button"
                data-bs-toggle="dropdown" aria-expanded="false">
                <?php if ($photo): ?>
                  <img src="<?php echo htmlspecialchars($photo); ?>" class="rounded-circle" width="36" height="36" style="object-fit:cover" alt="">
                <?php else: ?>
                  <i class="fa-solid fa-circle-user fa-2x"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="/InfoMan-Project/user/profile.php">Profile</a></li>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                  <li><a class="dropdown-item" href="/InfoMan-Project/admin/home.php">Dashboard</a></li>
                <?php else: ?>
                  <li><a class="dropdown-item" href="/InfoMan-Project/user/myorders.php">My Orders</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="/InfoMan-Project/includes/logout.php">Log out</a></li>
              </ul>
            </div>
          <?php
          }
          ?>
        </div>
      </div>
    </div>
  </nav>

  <!-- ===================== BASKET PANEL ===================== -->
  <div class="offcanvas offcanvas-end basket-panel" tabindex="-1" id="basketPanel" aria-labelledby="basketTitle">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="basketTitle">Basket</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <form method="POST" action="/InfoMan-Project/cart_update.php" class="basket-form">
      <div class="offcanvas-body">
        <?php if (empty($cart)): ?>
          <p class="basket-empty">Your basket is empty.</p>
        <?php else: ?>
          <?php foreach ($cart as $id => $item): ?>
            <div class="basket-item">
              <div class="basket-qty">
                <button type="button" class="basket-step" onclick="basketStep(this, -1)"
                  aria-label="Decrease quantity">&minus;</button>
                <input type="number" name="product_qty[<?php echo (int) $id; ?>]"
                  value="<?php echo (int) $item['item_qty']; ?>" min="1" onchange="this.form.submit()"
                  aria-label="Quantity">
                <button type="button" class="basket-step" onclick="basketStep(this, 1)"
                  aria-label="Increase quantity">+</button>
              </div>
              <div class="basket-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
              <div class="basket-price">₱<?php echo number_format($item['item_price'] * $item['item_qty'], 2); ?></div>
              <button type="submit" name="remove_code[]" value="<?php echo (int) $id; ?>" class="basket-remove"
                aria-label="Remove item"><i class="fa-solid fa-trash"></i></button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php if (!empty($cart)): ?>
        <div class="basket-summary">
          <div class="basket-row"><span>Subtotal</span><span>₱<?php echo number_format($subtotal, 2); ?></span></div>
          <?php if ($serviceFeeRate > 0): ?>
            <div class="basket-row"><span>Service fee</span><span>₱<?php echo number_format($serviceFee, 2); ?></span></div>
          <?php endif; ?>
          <hr>
          <div class="basket-row basket-total"><span>Total</span><span>₱<?php echo number_format($total, 2); ?></span></div>
          <a href="/InfoMan-Project/transaction/checkout/checkout.php" class="basket-checkout">
            <i class="fa-solid fa-cart-shopping"></i> Checkout
          </a>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <script>
    // + / - on a basket item: change the number, then save the basket.
    // "-" on 1 removes the item.
    function basketStep(btn, delta) {
      var input = btn.parentNode.querySelector('input');
      var form = input.form;
      var value = (parseInt(input.value, 10) || 1) + delta;
      if (value < 1) {
        var remove = document.createElement('input');
        remove.type = 'hidden';
        remove.name = 'remove_code[]';
        remove.value = input.name.match(/\[(\d+)\]/)[1];
        form.appendChild(remove);
      } else {
        input.value = value;
      }
      form.submit();
    }
  </script>

  <?php if (!empty($_SESSION['open_basket'])): unset($_SESSION['open_basket']); ?>
    <!-- open the basket right after an item was added or changed -->
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Offcanvas(document.getElementById('basketPanel')).show();
      });
    </script>
  <?php endif; ?>