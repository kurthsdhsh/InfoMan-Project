<?php

require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/order_helpers.php';

class StoreRuleError extends Exception
{
}

$adminId  = (int) $_SESSION['admin_id'];
$orderId  = (int) ($_POST['order_id'] ?? 0);
$do       = $_POST['do'] ?? '';
$from     = $_POST['from'] ?? '';
$back     = '/InfoMan-Project/admin/order/order_detail.php?id=' . $orderId;
$list     = '/InfoMan-Project/admin/order/orders.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $orderId < 1 || !in_array($do, ['next', 'complete', 'cancel'], true)) {
    header('Location: ' . $list);
    exit();
}

$saved = $_SESSION['ao_token'] ?? '';
unset($_SESSION['ao_token']);
if ($saved === '' || !hash_equals($saved, (string) ($_POST['token'] ?? ''))) {
    $_SESSION['error'] = 'That page was out of date. Please try the button again.';
    header('Location: ' . $back);
    exit();
}

$reason  = trim((string) ($_POST['reason'] ?? ''));
$restock = isset($_POST['restock']);

try {
    mysqli_begin_transaction($conn);

    $res = mysqli_execute_query(
        $conn,
        "SELECT order_status, fulfillment_method FROM tbl_orders WHERE order_id = ? FOR UPDATE",
        [$orderId]
    );
    $order = mysqli_fetch_assoc($res);
    if (!$order) {
        throw new StoreRuleError('That order was not found.');
    }
    $status = $order['order_status'];
    if ($status !== $from) {
        throw new StoreRuleError('This order was just changed (it is now ' . $status . '). The page was refreshed, please check it.');
    }

    if ($do === 'next') {
        $step = nextOrderStep($status, $order['fulfillment_method']);
        if (!$step) {
            throw new StoreRuleError('This order cannot move to a next step from here.');
        }
        mysqli_execute_query($conn, "UPDATE tbl_orders SET order_status = ? WHERE order_id = ?", [$step[0], $orderId]);
        $done = orderNumber($orderId) . ' is now ' . $step[0] . '.';

    } elseif ($do === 'complete') {
        if ($status !== 'Ready' && $status !== 'Out for Delivery') {
            throw new StoreRuleError('Cash can be received only when the order is Ready or Out for Delivery.');
        }

        $res = mysqli_execute_query(
            $conn,
            "SELECT (SELECT COALESCE(SUM(quantity * unit_price), 0) FROM tbl_order_items WHERE order_id = ?)
                    + COALESCE((SELECT delivery_fee FROM tbl_deliveries WHERE order_id = ?), 0) AS total",
            [$orderId, $orderId]
        );
        $total = (float) mysqli_fetch_assoc($res)['total'];

        mysqli_execute_query(
            $conn,
            "UPDATE tbl_payments SET payment_status = 'Paid', amount_paid = ?, payment_date = NOW(), confirmed_by = ?
             WHERE order_id = ? AND payment_status = 'Unpaid'",
            [$total, $adminId, $orderId]
        );
        if (mysqli_affected_rows($conn) !== 1) {
            throw new StoreRuleError('The payment of this order is not Unpaid, so nothing was changed.');
        }
        mysqli_execute_query($conn, "UPDATE tbl_orders SET order_status = 'Completed' WHERE order_id = ?", [$orderId]);
        $done = orderNumber($orderId) . ' is completed. Cash received: ' . peso($total) . '.';

    } else {   // cancel
        if (!storeCanCancel($status)) {
            throw new StoreRuleError('This order is already ' . $status . ', so it cannot be cancelled.');
        }
        if (mb_strlen($reason) < 5) {
            $_SESSION['ao_form'] = ['reason' => $reason] + ($restock ? ['restock' => 1] : []);
            throw new StoreRuleError('Please write the reason for cancelling (at least 5 letters). The customer will see it.');
        }

        if ($restock) {
            // put the items back 
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
                     VALUES (?, ?, ?, 'Order Cancelled', ?, ?)",
                    [(int) $it['product_id'], $adminId, $orderId, (int) $it['quantity'], 'Order ' . orderNumber($orderId) . ' cancelled by the store']
                );
            }
        }
        mysqli_execute_query(
            $conn,
            "UPDATE tbl_orders SET order_status = 'Cancelled', cancel_reason = ? WHERE order_id = ?",
            [mb_substr($reason, 0, 255), $orderId]
        );
        $done = orderNumber($orderId) . ' was cancelled' . ($restock ? ' and the items went back to stock.' : '. Stock was not changed.');
    }

    mysqli_commit($conn);
    $_SESSION['success'] = $done;

} catch (StoreRuleError $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = $e->getMessage();
    if ($do === 'cancel' && isset($_SESSION['ao_form'])) {
        $back .= '&cancel=1';                                    
    }
} catch (Throwable $e) {
    mysqli_rollback($conn);                                      
    error_log('order_action failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Something went wrong and nothing was changed. Please try again.';
}

header('Location: ' . $back);
exit();
