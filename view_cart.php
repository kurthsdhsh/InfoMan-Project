<?php
session_start();
include('includes/header.php');
include('includes/config.php');

$total = 0; //set initial total value
$hasItems = !empty($_SESSION["cart_products"]);
?>

<h1 align="center">View Cart</h1>
<div class="cart-view-table-back">
    <form method="POST" action="cart_update.php">
        <input type="hidden" name="redirect" value="view_cart.php">
        <table width="100%" cellpadding="6" cellspacing="0">
            <thead>
                <tr>
                    <th>Quantity</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th>Remove</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($hasItems) //check session var
                {

                    $b = 0; //var for zebra stripe table 

                    foreach ($_SESSION["cart_products"] as $cart_itm) {

                        //set variables to use in content below

                        $product_name = htmlspecialchars($cart_itm["item_name"]);

                        $product_qty = (int)$cart_itm["item_qty"];

                        $product_price = $cart_itm["item_price"];

                        $product_code = (int)$cart_itm["item_id"];
                        $subtotal = ($product_price * $product_qty); //calculate Price x Qty

                        $bg_color = ($b++ % 2 == 1) ? 'odd' : 'even'; //class for zebra stripe 

                        echo '<tr class="' . $bg_color . '">';

                        echo '<td><input type="text" size="2" maxlength="2" name="product_qty[' . $product_code . ']" value="' . $product_qty . '" /></td>';

                        echo '<td>' . $product_name . '</td>';

                        echo '<td>' . number_format($product_price, 2) . '</td>';

                        echo '<td>' . number_format($subtotal, 2) . '</td>';

                        echo '<td><input type="checkbox" name="remove_code[]" value="' . $product_code . '" /></td>';

                        echo '</tr>';

                        $total = ($total + $subtotal); //add subtotal to total var

                    }
                } else {
                    echo '<tr><td colspan="5" style="text-align:center;">Your cart is empty.</td></tr>';
                }

                ?>

                <tr>
                    <td colspan="5"><span style="float:right;text-align: right;">Amount Payable :
                            <?php echo sprintf("%01.2f", $total); ?></span></td>
                </tr>

                <tr>
                    <td colspan="5">
                        <a href="index.php" class="button">Add More Items</a>

                        <?php if ($hasItems): ?>
                            <a href="checkout.php" class="button">checkout</a>
                            <button type="submit">Update</button>
                        <?php endif; ?>
                    </td>
                </tr>

            </tbody>

        </table>



    </form>

</div>
<?php
include('./includes/footer.php');
?>