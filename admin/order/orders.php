<?php

require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/filter_helper.php';
require_once '../../includes/order_helpers.php';

$search       = adminFilterValue('search');
$statusFilter = adminFilterValue('status');
$typeFilter   = adminFilterValue('type');

$statuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Out for Delivery', 'Completed', 'Cancelled'];

$conditions = [];
$params = [];

if ($search !== '') {
    $digits = preg_replace('/\D/', '', $search);
    $conditions[] = "(CONCAT(c.first_name, ' ', c.last_name) LIKE ?" . ($digits !== '' ? ' OR o.order_id = ?' : '') . ')';
    $params[] = '%' . $search . '%';
    if ($digits !== '') {
        $params[] = (int) $digits;
    }
}

if ($statusFilter === 'active') {
    $conditions[] = "o.order_status NOT IN ('Completed', 'Cancelled')";
} elseif (in_array($statusFilter, $statuses, true)) {
    $conditions[] = 'o.order_status = ?';
    $params[] = $statusFilter;
}

if ($typeFilter === 'Pickup' || $typeFilter === 'Delivery') {
    $conditions[] = 'o.fulfillment_method = ?';
    $params[] = $typeFilter;
}

$sql = "SELECT o.order_id, o.order_status, o.fulfillment_method, o.scheduled_at,
               CONCAT(c.first_name, ' ', c.last_name) AS customer_name, p.payment_status,
               (SELECT COALESCE(SUM(i.quantity * i.unit_price), 0) FROM tbl_order_items i WHERE i.order_id = o.order_id)
                 + COALESCE(d.delivery_fee, 0) AS grand_total
        FROM tbl_orders o
        INNER JOIN tbl_customers c ON c.customer_id = o.customer_id
        LEFT JOIN tbl_deliveries d ON d.order_id = o.order_id
        LEFT JOIN tbl_payments p ON p.order_id = o.order_id";

if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= " ORDER BY (o.order_status IN ('Completed', 'Cancelled')) ASC,
                   IF(o.order_status IN ('Completed', 'Cancelled'), 0, UNIX_TIMESTAMP(o.scheduled_at)) ASC,
                   o.order_id DESC
          LIMIT 300";

$result = mysqli_execute_query($conn, $sql, $params);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
$itemCount = count($orders);

$waiting = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tbl_orders WHERE order_status = 'Pending'"))[0];

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);
?>

<?php require_once '../../includes/adminHeader.php'; ?>

<div class="admin-container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Order Management</h2>
        </div>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($waiting > 0): ?>
        <div class="alert alert-warning">
            <?= $waiting ?> order<?= $waiting === 1 ? ' is' : 's are' ?> waiting for the store to confirm.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">

            <?php
            renderAdminFilterForm(
                basename($_SERVER['PHP_SELF']),
                $search,
                'Search order no. or customer name...',
                [
                    [
                        'name' => 'status',
                        'label' => 'All Statuses',
                        'options' => ['active' => 'Active (not finished)'] + array_combine($statuses, $statuses),
                        'selected' => $statusFilter
                    ],
                    [
                        'name' => 'type',
                        'label' => 'Pick-up and Delivery',
                        'options' => ['Pickup' => 'Pick-up', 'Delivery' => 'Delivery'],
                        'selected' => $typeFilter
                    ]
                ]
            );
            ?>

            <p class="text-muted mb-3">
                Matching records: <?= (int) $itemCount ?>
            </p>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Order No.</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Pick-up / Delivery Time</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($itemCount > 0): ?>

                        <?php foreach ($orders as $row):
                            $finished = in_array($row['order_status'], ['Completed', 'Cancelled'], true);
                            $late = !$finished && strtotime($row['scheduled_at']) < time();
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars(orderNumber((int) $row['order_id'])) ?></strong></td>

                                <td><?= htmlspecialchars($row['customer_name']) ?></td>

                                <td><?= $row['fulfillment_method'] === 'Delivery' ? 'Delivery' : 'Pick-up' ?></td>

                                <td>
                                    <?= htmlspecialchars(date('M j, Y g:i A', strtotime($row['scheduled_at']))) ?>
                                    <?php if ($late): ?>
                                        <br><small class="text-danger">Past the time</small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge <?= orderBadgeClass($row['order_status']) ?>">
                                        <?= htmlspecialchars($row['order_status']) ?>
                                    </span>
                                </td>

                                <td><?= htmlspecialchars($row['payment_status'] ?? 'Unpaid') ?></td>

                                <td><?= peso((float) $row['grand_total']) ?></td>

                                <td>
                                    <a
                                        href="order_detail.php?id=<?= (int) $row['order_id'] ?>"
                                        class="btn btn-sm btn-primary"
                                        title="View order">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center py-4">
                                No orders found.
                            </td>
                        </tr>

                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

</main>
</body>
</html>
