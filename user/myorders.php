<?php

session_start();
include('../includes/config.php');
require_once __DIR__ . '/../includes/order_helpers.php';

requireCustomer();
$customerId = (int) $_SESSION['customer_id'];

$show = $_GET['show'] ?? 'all';
$filters = [
    'all'       => ['All',       ''],
    'active'    => ['Active',    " AND o.order_status IN ('Pending','Confirmed','Preparing','Ready','Out for Delivery')"],
    'done'      => ['Completed', " AND o.order_status = 'Completed'"],
    'cancelled' => ['Cancelled', " AND o.order_status = 'Cancelled'"],
];
if (!isset($filters[$show])) {
    $show = 'all';
}

$res = mysqli_execute_query(
    $conn,
    "SELECT o.order_id, o.order_date, o.order_status, o.fulfillment_method, o.scheduled_at, p.payment_status,
            (SELECT COALESCE(SUM(i.quantity * i.unit_price), 0) FROM tbl_order_items i WHERE i.order_id = o.order_id)
              + COALESCE(d.delivery_fee, 0) AS grand_total
     FROM tbl_orders o
     LEFT JOIN tbl_deliveries d ON d.order_id = o.order_id
     LEFT JOIN tbl_payments p ON p.order_id = o.order_id
     WHERE o.customer_id = ?" . $filters[$show][1] . "
     ORDER BY o.order_id DESC",
    [$customerId]
);
$orders = mysqli_fetch_all($res, MYSQLI_ASSOC);

include('../includes/header.php');
?>

<style>
#moWrap { max-width: 900px; margin: 0 auto 40px; padding: 0 12px; color: #4a3b30; }
#moTabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
#moTabs a { padding: 6px 16px; border: 2px solid #E2B59A; border-radius: 999px; color: #4a3b30; text-decoration: none; }
#moTabs a.moOn { background: #B77466; border-color: #B77466; color: #fff; }
#moTable { width: 100%; border-collapse: collapse; background: #fff; }
#moTable th { text-align: left; font-size: 13px; color: #957C62; padding: 8px 6px; border-bottom: 3px solid #E2B59A; }
#moTable td { padding: 10px 6px; border-bottom: 1px solid #FFE3B0; vertical-align: middle; }
#moTable .moR { text-align: right; }
.moBadge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 13px; font-weight: 600; background: #FFE3B0; color: #4a3b30; }
.moBadge.moSt-Pending { background: #FFE3B0; }
.moBadge.moSt-Confirmed, .moBadge.moSt-Preparing { background: #DCE9F7; }
.moBadge.moSt-Ready, .moBadge.moSt-OutforDelivery { background: #D8EFD3; }
.moBadge.moSt-Completed { background: #B9E2B1; }
.moBadge.moSt-Cancelled { background: #EBD6D6; color: #8A3B3B; }
#moTable a.moView { color: #B77466; font-weight: 700; }
#moEmpty { text-align: center; padding: 40px 10px; color: #957C62; }
</style>

<div id="moWrap">
    <h1 class="text-center my-4">My Orders</h1>

    <div id="moTabs">
        <?php foreach ($filters as $key => $f): ?>
            <a href="myorders.php?show=<?= $key ?>" class="<?= $key === $show ? 'moOn' : '' ?>"><?= h($f[0]) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$orders): ?>
        <div id="moEmpty">
            No orders here yet.<br>
            <a href="/InfoMan-Project/index.php">Start shopping</a>
        </div>
    <?php else: ?>
        <table id="moTable">
            <thead>
                <tr>
                    <th>Order no.</th>
                    <th>Placed on</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th class="moR">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong><?= h(orderNumber((int) $o['order_id'])) ?></strong></td>
                    <td><?= h(date('M j, Y g:i A', strtotime($o['order_date']))) ?></td>
                    <td><?= h($o['fulfillment_method'] === 'Delivery' ? 'Delivery' : 'Pick-up') ?></td>
                    <td><span class="moBadge moSt-<?= h(str_replace(' ', '', $o['order_status'])) ?>"><?= h($o['order_status']) ?></span></td>
                    <td class="moR"><?= peso((float) $o['grand_total']) ?></td>
                    <td class="moR"><a class="moView" href="order_view.php?id=<?= (int) $o['order_id'] ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>
