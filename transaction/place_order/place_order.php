<?php

session_start();
include('../../includes/config.php');
require_once __DIR__ . '/../../includes/order_helpers.php';

class OrderRuleError extends Exception
{
}

$checkoutPage = '/InfoMan-Project/transaction/checkout/checkout.php';
$successPage  = '/InfoMan-Project/transaction/place_order/order_success.php';

requireCustomer();
$customerId = (int) $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $checkoutPage);
    exit();
}

function backToCheckout(array $errors): void
{
    global $checkoutPage;
    $_SESSION['co_errors'] = $errors;
    header('Location: ' . $checkoutPage);
    exit();
}

$token = $_POST['token'] ?? '';
$saved = $_SESSION['place_token'] ?? '';
unset($_SESSION['place_token']);
if ($saved === '' || !hash_equals($saved, $token)) {
    if (!empty($_SESSION['last_order_id'])) {          // just placed it: show that order again
        header('Location: ' . $successPage . '?id=' . (int) $_SESSION['last_order_id']);
        exit();
    }
    backToCheckout(['This page was out of date. Please check your order and press Place order again.']);
}

$same   = isset($_POST['same']);
$rName  = mb_substr(trim($_POST['recipient_name'] ?? ''), 0, 120);
$rPhone = mb_substr(trim($_POST['recipient_phone'] ?? ''), 0, 25);
$note   = mb_substr(trim($_POST['note'] ?? ''), 0, 500);
$_SESSION['checkout']['same']            = $same;
$_SESSION['checkout']['recipient_name']  = $rName;
$_SESSION['checkout']['recipient_phone'] = $rPhone;
$_SESSION['checkout']['note']            = $note;

$errors = [];

$res = mysqli_execute_query($conn, "SELECT first_name, last_name, contact_number, account_status FROM tbl_customers WHERE customer_id = ? LIMIT 1", [$customerId]);
$me  = mysqli_fetch_assoc($res);
if (!$me || strtolower($me['account_status']) !== 'active') {
    backToCheckout(['Your account cannot place orders.']);
}
if ($same) {
    $rName  = $me['first_name'] . ' ' . $me['last_name'];
    $rPhone = $me['contact_number'];
} else {
    if (mb_strlen($rName) < 2) {
        $errors[] = 'Please enter the name of the person who will receive the order.';
    }
    if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $rPhone)) {
        $errors[] = 'The recipient phone number should look like 09123456789.';
    }
}

$choice = $_SESSION['checkout'] ?? [];
$method = $choice['method'] ?? '';
if (!in_array($method, ['Pickup', 'Delivery'], true)) {
    backToCheckout(['Please choose Pick-up or Delivery.']);
}
[$scheduledAt, $scheduleError] = validateChoice($choice['date'] ?? '', $choice['time'] ?? '');
if ($scheduleError) {
    $errors[] = $scheduleError . ' Please press Edit under Order type.';
}
$areaId  = (int) ($choice['area_id'] ?? 0);
$address = trim($choice['address'] ?? '');
if ($method === 'Delivery') {
    if (!isset(areaList($conn)[$areaId])) {
        $errors[] = 'Please press Edit under Order type and choose your delivery city.';
    }
    if (strlen($address) < 10 || strlen($address) > 255) {
        $errors[] = 'Please press Edit under Order type and enter your street address.';
    }
}

//basket
$lines = getCartLines($conn);
if (!$lines) {
    $_SESSION['message'] = 'Your basket is empty. Add a product to check out.';
    header('Location: /InfoMan-Project/index.php');
    exit();
}
if ($problem = cartProblem($lines)) {
    $errors[] = $problem;
}

if ($errors) {
    backToCheckout($errors);
}

try {
    mysqli_begin_transaction($conn);

    mysqli_execute_query($conn, "SELECT customer_id FROM tbl_customers WHERE customer_id = ? FOR UPDATE", [$customerId]);

    // fake-order guard
    $res = mysqli_execute_query($conn, "SELECT COUNT(*) AS n FROM tbl_orders WHERE customer_id = ? AND order_status = 'Pending'", [$customerId]);
    if ((int) mysqli_fetch_assoc($res)['n'] >= MAX_PENDING_ORDERS) {
        throw new OrderRuleError('You already have ' . MAX_PENDING_ORDERS . ' pending orders. Please wait for the store to confirm them first.');
    }

    $ids   = array_map(fn($l) => $l['product_id'], $lines);
    sort($ids);
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $res   = mysqli_execute_query($conn, "SELECT product_id, product_name, unit_price, stock_quantity, product_status FROM tbl_products WHERE product_id IN ($marks) ORDER BY product_id FOR UPDATE", $ids);
    $products = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $products[(int) $row['product_id']] = $row;
    }
    foreach ($lines as $l) {
        $p = $products[$l['product_id']] ?? null;
        if (!$p || $p['product_status'] !== 'Active') {
            throw new OrderRuleError($l['name'] . ' is no longer available.');
        }
        if ($l['qty'] > (int) $p['stock_quantity']) {
            throw new OrderRuleError('Only ' . (int) $p['stock_quantity'] . ' left of ' . $p['product_name'] . '.');
        }
    }

    mysqli_execute_query(
        $conn,
        "INSERT INTO tbl_orders (customer_id, order_date, order_status, fulfillment_method, scheduled_at, recipient_name, recipient_phone, customer_note)
         VALUES (?, NOW(), 'Pending', ?, ?, ?, ?, ?)",
        [$customerId, $method, $scheduledAt, $rName, $rPhone, ($note === '' ? null : $note)]
    );
    $orderId = (int) mysqli_insert_id($conn);

    foreach ($lines as $l) {
        $p = $products[$l['product_id']];
        mysqli_execute_query($conn, "INSERT INTO tbl_order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)", [$orderId, $l['product_id'], $l['qty'], $p['unit_price']]);
        mysqli_execute_query($conn, "UPDATE tbl_products SET stock_quantity = stock_quantity - ? WHERE product_id = ?", [$l['qty'], $l['product_id']]);
        mysqli_execute_query(
            $conn,
            "INSERT INTO tbl_stock_movements (product_id, order_id, movement_type, quantity_change, reason) VALUES (?, ?, 'Order Placed', ?, ?)",
            [$l['product_id'], $orderId, -$l['qty'], 'Order ' . orderNumber($orderId)]
        );
    }

    if ($method === 'Delivery') {
        $res = mysqli_execute_query($conn, "SELECT delivery_fee FROM tbl_delivery_areas WHERE area_id = ? LIMIT 1", [$areaId]);
        $fee = mysqli_fetch_assoc($res);
        if (!$fee) {
            throw new OrderRuleError('We no longer deliver to that city. Please choose another city.');
        }
        mysqli_execute_query($conn, "INSERT INTO tbl_deliveries (order_id, area_id, delivery_address, delivery_fee) VALUES (?, ?, ?, ?)", [$orderId, $areaId, $address, $fee['delivery_fee']]);
    }

    mysqli_execute_query(
        $conn,
        "INSERT INTO tbl_payments (order_id, payment_method, payment_status, amount_paid) VALUES (?, ?, 'Unpaid', 0)",
        [$orderId, $method === 'Delivery' ? 'Cash on Delivery' : 'Cash on Pickup']
    );

    mysqli_commit($conn);   
} catch (OrderRuleError $e) {            
    mysqli_rollback($conn);
    backToCheckout([$e->getMessage()]);
} catch (Throwable $e) {                 
    mysqli_rollback($conn);
    error_log('place_order failed: ' . $e->getMessage());
    backToCheckout(['Something went wrong and your order was NOT placed. Please try again.']);
}


unset($_SESSION['cart_products'], $_SESSION['checkout'], $_SESSION['pending_add']);
$_SESSION['last_order_id'] = $orderId;
header('Location: ' . $successPage . '?id=' . $orderId);
exit();
