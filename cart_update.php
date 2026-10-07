<?php
session_start();
// no header.php here: this file shows nothing, it only updates the cart and redirects
include('./includes/config.php');

if (isset($_POST["type"]) && $_POST["type"] == 'add' && $_POST["item_qty"] > 0) {
    // var_dump($_POST);
    $new_product = [];
    $new_product['item_id'] = (int)$_POST['item_id'];
    $new_product['item_qty'] = (int)$_POST['item_qty'];
    // var_dump($new_product);

    $sql = "SELECT product_id AS itemId, product_name, unit_price, stock_quantity FROM tbl_products WHERE product_id = ? LIMIT 1";
    $result = mysqli_execute_query($conn, $sql, [$new_product['item_id']]);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        //fetch product name, price from db and add to new_product array
        $new_product["item_name"] = $row['product_name'];
        $new_product["item_price"] = $row['unit_price'];
        // var_dump($new_product);

        if (isset($_SESSION["cart_products"])) {  //if session var already exist
            if (isset($_SESSION["cart_products"][$new_product['item_id']])) //check item exist in products array
            {
                // already in the cart: add to its quantity
                $new_product['item_qty'] += (int)$_SESSION["cart_products"][$new_product['item_id']]['item_qty'];
                unset($_SESSION["cart_products"][$new_product['item_id']]);
            }
        }

        // never more than what is in stock
        $new_product['item_qty'] = min($new_product['item_qty'], (int)$row['stock_quantity']);

        //update or create product session with new item
        if ($new_product['item_qty'] > 0) {
            $_SESSION["cart_products"][$new_product['item_id']] = $new_product;
        }
        // print_r($_SESSION);
    }
}

if (isset($_POST["product_qty"]) || isset($_POST["remove_code"])) {
    // var_dump($_POST["remove_code"], $_POST['product_qty']);
    //update item quantity in product session

    if (isset($_POST["product_qty"]) && is_array($_POST["product_qty"])) {
        // var_dump($_SESSION['cart_products']);
        foreach ($_POST["product_qty"] as $key => $value) {
            if (is_numeric($value) && (int)$value > 0 && isset($_SESSION["cart_products"][$key])) {
                // var_dump( $key, $value);
                $newQty = (int)$value;
                // never more than what is in stock
                $stockResult = mysqli_execute_query($conn, "SELECT stock_quantity FROM tbl_products WHERE product_id = ? LIMIT 1", [(int)$key]);
                $stockRow = mysqli_fetch_assoc($stockResult);
                if ($stockRow) {
                    $newQty = min($newQty, (int)$stockRow['stock_quantity']);
                }
                if ($newQty > 0) {
                    $_SESSION["cart_products"][$key]["item_qty"] = $newQty;
                }
            }
        }
    }

    if (isset($_POST["remove_code"]) && is_array($_POST["remove_code"])) {
        foreach ($_POST["remove_code"] as $key) {
            // var_dump($key);
            unset($_SESSION["cart_products"][$key]);
        }
    }
    // echo "<pre>";
    // print_r($_SESSION['cart_products']);
    // echo "</pre>";
}

// the View Cart page sends redirect=view_cart.php so you stay on it; everything else goes to the shop
if (($_POST['redirect'] ?? '') === 'view_cart.php') {
    header('Location: view_cart.php');
    exit();
}
$_SESSION['open_basket'] = true;   // header.php opens the basket panel
header('Location: index.php');
exit();