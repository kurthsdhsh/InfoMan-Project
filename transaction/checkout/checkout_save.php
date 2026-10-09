<?php

session_start();
include('../../includes/config.php');
require_once __DIR__ . '/../../includes/order_helpers.php';

$back = '/InfoMan-Project/transaction/checkout/checkout.php';

requireCustomer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $back);
    exit();
}

$_SESSION['checkout']['same']            = isset($_POST['same']);
$_SESSION['checkout']['recipient_name']  = mb_substr(trim($_POST['recipient_name'] ?? ''), 0, 120);
$_SESSION['checkout']['recipient_phone'] = mb_substr(trim($_POST['recipient_phone'] ?? ''), 0, 25);
$_SESSION['checkout']['note']            = mb_substr(trim($_POST['note'] ?? ''), 0, 500);

[$action, $id] = array_pad(explode(':', $_POST['cart'] ?? 'none'), 2, '');
$id = (int) $id;

if ($id > 0 && isset($_SESSION['cart_products'][$id])) {
    $qty = (int) $_SESSION['cart_products'][$id]['item_qty'];

    if ($action === 'remove') {
        unset($_SESSION['cart_products'][$id]);
    } elseif ($action === 'plus' || $action === 'minus') {
        $qty += ($action === 'plus') ? 1 : -1;
        $result = mysqli_execute_query($conn, "SELECT stock_quantity FROM tbl_products WHERE product_id = ? LIMIT 1", [$id]);
        $row = mysqli_fetch_assoc($result);
        $qty = min($qty, (int) ($row['stock_quantity'] ?? 0));   
        if ($qty >= 1) {
            $_SESSION['cart_products'][$id]['item_qty'] = $qty;
        }
    }
}

header('Location: ' . $back);   
exit();
