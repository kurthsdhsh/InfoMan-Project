<?php
session_start();
include('./includes/config.php');
include('./includes/header.php');

// ---- EDIT THIS: the image column in tbl_products (I couldn't see it in your query) ----
$imageColumn = 'image_path';
?>
<h1 class="text-center my-4">Products</h1>

<?php
// ---------- Shopping cart ----------
if (isset($_SESSION["cart_products"]) && count($_SESSION["cart_products"]) > 0) {
    echo '<div class="cart-view-table-front" id="view-cart">';
    echo '<h3>Your Shopping Cart</h3>';
    echo '<form method="POST" action="cart_update.php">';
    echo '<table width="100%" cellpadding="6" cellspacing="0">';
    echo '<tbody>';
    $total = 0;
    $b = 0;
    foreach ($_SESSION["cart_products"] as $cart_itm) {
        $product_name  = htmlspecialchars($cart_itm["item_name"]);
        $product_qty   = (int) $cart_itm["item_qty"];
        $product_price = $cart_itm["item_price"];
        $product_code  = $cart_itm["item_id"];
        $bg_color = ($b++ % 2 == 1) ? 'odd' : 'even';
        echo '<tr class="' . $bg_color . '">';
        echo "<td>Qty <input type='number' size='2' maxlength='2' name='product_qty[$product_code]' value='{$product_qty}' min='1' /></td>";
        echo "<td>{$product_name}</td>";
        echo '<td>' . number_format($product_price * $product_qty, 2) . '</td>';
        echo '<td><input type="checkbox" name="remove_code[]" value="' . $product_code . '" /> Remove</td>';
        echo '</tr>';
        $total += $product_price * $product_qty;
    }
    echo '<tr><td colspan="2"><strong>Total</strong></td><td colspan="2"><strong>' . number_format($total, 2) . '</strong></td></tr>';
    echo '<tr><td colspan="4">';
    echo '<button type="submit">Update</button> <a href="view_cart.php" class="button">Checkout</a>';
    echo '</td></tr>';
    echo '</tbody>';
    echo '</table>';
    echo '</form>';
    echo '</div>';
}

// ---------- Products ----------
$sql = "SELECT * FROM tbl_products ORDER BY product_id ASC";

$results = mysqli_query($conn, $sql);
if ($results) {
    echo '<ul class="products">';

    while ($row = mysqli_fetch_assoc($results)) {
        $id    = (int) $row['product_id'];
        $name  = htmlspecialchars($row['product_name']);
        $desc  = htmlspecialchars($row['product_description']);
        $price = number_format($row['unit_price'], 2);
        $qty   = (int) $row['stock_quantity'];
        $img   = htmlspecialchars($row[$imageColumn] ?? '');
        ?>
        <li class="product">
            <form method="POST" action="cart_update.php">
                <div class="product-content">
                    <h3><?php echo $name; ?></h3>
                    <div class="product-thumb">
                        <img src="./admin_item/<?php echo $img; ?>" width="50" height="50" alt="<?php echo $name; ?>">
                    </div>
                    <div class="product-info">
                        <?php echo $desc; ?><br>
                        Price <?php echo $price; ?><br>
                        <?php if ($qty > 0): ?>
                            <fieldset>
                                <label>
                                    <span>Quantity</span>
                                    <input type="number" name="item_qty" value="1" min="1" max="<?php echo $qty; ?>" />
                                </label>
                            </fieldset>
                            <input type="hidden" name="item_id" value="<?php echo $id; ?>" />
                            <input type="hidden" name="type" value="add" />
                            <div align="center"><button type="submit" class="add_to_cart">Add</button></div>
                        <?php else: ?>
                            <p>Out of stock</p>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </li>
        <?php
    }

    echo '</ul>';
}