<?php

session_start();
include('../includes/config.php');
require_once __DIR__ . '/../includes/order_helpers.php';

class CancelRuleError extends Exception
{
}

requireCustomer();
$customerId = (int) $_SESSION['customer_id'];
$orderId    = (int) ($_POST['order_id'] ?? 0);
$viewPage   = '/InfoMan-Project/user/order_view.php?id=' . $orderId;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $orderId < 1) {
    header('Location: /InfoMan-Project/user/myorders.php');
    exit();
}

$saved = $_SESSION['cancel_token'] ?? '';
unset($_SESSION['cancel_token']);
if ($saved === '' || !hash_equals($saved, (string) ($_POST['token'] ?? ''))) {
    $_SESSION['mo_error'] = 'That request expired. Please press Cancel order again.';
    header('Location: ' . $viewPage);
    exit();
}

try {
    mysqli_begin_transaction($conn);

    $res = mysqli_execute_query(
        $conn,
        "SELECT order_status FROM tbl_orders WHERE order_id = ? AND customer_id = ? FOR UPDATE",
        [$orderId, $customerId]
    );
    $order = mysqli_fetch_assoc($res);
    if (!$order) {
        throw new CancelRuleError('We could not find that order.');
    }
    if ($order['order_status'] === 'Cancelled') {
        throw new CancelRuleError('This order was already cancelled.');
    }
    if ($order['order_status'] !== 'Pending') {
        throw new CancelRuleError('The store is already working on this order, so it can no longer be cancelled here. Please contact the store.');
    }

    $res   = mysqli_execute_query($conn, "SELECT product_id, quantity FROM tbl_order_items WHERE order_id = ? ORDER BY product_id", [$orderId]);
    $items = mysqli_fetch_all($res, MYSQLI_ASSOC);

    foreach ($items as $it) {
        mysqli_execute_query($conn, "SELECT product_id FROM tbl_products WHERE product_id = ? FOR UPDATE", [(int) $it['product_id']]);
        mysqli_execute_query(
            $conn,
            "UPDATE tbl_products SET stock_quantity = stock_quantity + ? WHERE product_id = ?",
            [(int) $it['quantity'], (int) $it['product_id']]
        );
        mysqli_execute_query(
            $conn,
            "INSERT INTO tbl_stock_movements (product_id, admin_id, order_id, movement_type, quantity_change, reason)
             VALUES (?, NULL, ?, 'Order Cancelled', ?, ?)",
            [(int) $it['product_id'], $orderId, (int) $it['quantity'], 'Order ' . orderNumber($orderId) . ' cancelled by customer']
        );
    }

    mysqli_execute_query($conn, "UPDATE tbl_orders SET order_status = 'Cancelled' WHERE order_id = ?", [$orderId]);

    mysqli_commit($conn);
    $_SESSION['mo_message'] = 'Your order ' . orderNumber($orderId) . ' was cancelled.';
} catch (CancelRuleError $e) {
    mysqli_rollback($conn);
    $_SESSION['mo_error'] = $e->getMessage();
} catch (Throwable $e) {
    mysqli_rollback($conn);                                   // nothing was saved
    error_log('cancel_order failed: ' . $e->getMessage());
    $_SESSION['mo_error'] = 'Something went wrong and your order was NOT cancelled. Please try again.';
}

header('Location: ' . $viewPage);
exit();
