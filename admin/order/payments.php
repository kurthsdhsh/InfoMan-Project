<?php

require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/filter_helper.php';
require_once '../../includes/order_helpers.php';

$search       = adminFilterValue('search');
$statusFilter = adminFilterValue('payment_status');
$methodFilter = adminFilterValue('payment_method');

$conditions = [];
$params = [];

if ($search !== '') {
    $digits = preg_replace('/\D/', '', $search);
    $conditions[] = "(CONCAT(c.first_name, ' ', c.last_name) LIKE ? OR ad.admin_name LIKE ?" . ($digits !== '' ? ' OR o.order_id = ?' : '') . ')';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    if ($digits !== '') {
        $params[] = (int) $digits;
    }
}

if ($statusFilter === 'Paid' || $statusFilter === 'Unpaid') {
    $conditions[] = 'p.payment_status = ?';
    $params[] = $statusFilter;
}

if ($methodFilter === 'Cash on Pickup' || $methodFilter === 'Cash on Delivery') {
    $conditions[] = 'p.payment_method = ?';
    $params[] = $methodFilter;
}

$sql = "SELECT p.payment_id, p.payment_method, p.payment_status, p.amount_paid, p.payment_date,
               o.order_id, o.order_status,
               CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
               ad.admin_name,
               (SELECT COALESCE(SUM(i.quantity * i.unit_price), 0) FROM tbl_order_items i WHERE i.order_id = o.order_id)
                 + COALESCE(d.delivery_fee, 0) AS to_collect
        FROM tbl_payments p
        INNER JOIN tbl_orders o ON o.order_id = p.order_id
        INNER JOIN tbl_customers c ON c.customer_id = o.customer_id
        LEFT JOIN tbl_deliveries d ON d.order_id = o.order_id
        LEFT JOIN tbl_admins ad ON ad.admin_id = p.confirmed_by";

if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= " ORDER BY (p.payment_status = 'Paid') ASC, COALESCE(p.payment_date, o.order_date) DESC, o.order_id DESC
          LIMIT 300";

$result = mysqli_execute_query($conn, $sql, $params);
$payments = mysqli_fetch_all($result, MYSQLI_ASSOC);
$itemCount = count($payments);

$summary = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT
        COALESCE(SUM(CASE WHEN p.payment_status = 'Paid' THEN p.amount_paid END), 0) AS total_collected,
        COALESCE(SUM(CASE WHEN p.payment_status = 'Paid' AND DATE(p.payment_date) = CURDATE() THEN p.amount_paid END), 0) AS today_collected,
        COALESCE(SUM(p.payment_status = 'Paid'), 0) AS paid_count,
        COALESCE(SUM(CASE WHEN p.payment_status = 'Unpaid' AND o.order_status <> 'Cancelled' THEN
            (SELECT COALESCE(SUM(i.quantity * i.unit_price), 0) FROM tbl_order_items i WHERE i.order_id = o.order_id)
            + COALESCE((SELECT d2.delivery_fee FROM tbl_deliveries d2 WHERE d2.order_id = o.order_id), 0)
        END), 0) AS still_to_collect,
        COALESCE(SUM(p.payment_status = 'Unpaid' AND o.order_status <> 'Cancelled'), 0) AS unpaid_count
     FROM tbl_payments p
     INNER JOIN tbl_orders o ON o.order_id = p.order_id"
));

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);
?>

<?php require_once '../../includes/adminHeader.php'; ?>

<div class="admin-container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Cash Payments</h2>
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

    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">Total Collected</h6>
                        <i class="fas fa-wallet fa-lg"></i>
                    </div>

                    <h4 class="mb-1"><?= peso($summary['total_collected']) ?></h4>

                    <small class="text-muted">All cash received</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">Collected Today</h6>
                        <i class="fas fa-calendar-days fa-lg"></i>
                    </div>

                    <h4 class="mb-1"><?= peso($summary['today_collected']) ?></h4>

                    <small class="text-muted"><?= date('F j, Y') ?></small>
                </div>
            </div>
        </div>

        <!-- Paid Orders -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">Paid Orders</h6>
                        <i class="fas fa-receipt fa-lg"></i>
                    </div>

                    <h4 class="mb-1"><?= number_format((int) $summary['paid_count']) ?></h4>

                    <small class="text-muted">Orders paid in cash</small>
                </div>
            </div>
        </div>

        <!-- Still To Collect -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <i class="fas fa-arrow-trend-up fa-lg"></i>
                    </div>

                    <h4 class="mb-1"><?= peso($summary['still_to_collect']) ?></h4>

                    <small class="text-muted"><?= (int) $summary['unpaid_count'] ?> unpaid order<?= (int) $summary['unpaid_count'] === 1 ? '' : 's' ?> (not cancelled)</small>
                </div>
            </div>
        </div>

    </div>

    <div class="card">
        <div class="card-body">

            <?php
            renderAdminFilterForm(
                basename($_SERVER['PHP_SELF']),
                $search,
                'Search order no., customer or admin...',
                [
                    [
                        'name' => 'payment_status',
                        'label' => 'All Payment Statuses',
                        'options' => ['Unpaid' => 'Unpaid', 'Paid' => 'Paid'],
                        'selected' => $statusFilter
                    ],
                    [
                        'name' => 'payment_method',
                        'label' => 'All Methods',
                        'options' => ['Cash on Pickup' => 'Cash on Pickup', 'Cash on Delivery' => 'Cash on Delivery'],
                        'selected' => $methodFilter
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
                            <th>Method</th>
                            <th>Order Status</th>
                            <th>Payment</th>
                            <th>Amount</th>
                            <th>Date Received</th>
                            <th>Confirmed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($itemCount > 0): ?>

                        <?php foreach ($payments as $row):
                            $paid = $row['payment_status'] === 'Paid';
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars(orderNumber((int) $row['order_id'])) ?></strong></td>

                                <td><?= htmlspecialchars($row['customer_name']) ?></td>

                                <td><?= htmlspecialchars($row['payment_method']) ?></td>

                                <td>
                                    <span class="badge <?= orderBadgeClass($row['order_status']) ?>">
                                        <?= htmlspecialchars($row['order_status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge <?= $paid ? 'text-bg-success' : 'text-bg-warning' ?>">
                                        <?= htmlspecialchars($row['payment_status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($paid): ?>
                                        <?= peso($row['amount_paid']) ?>
                                    <?php elseif ($row['order_status'] === 'Cancelled'): ?>
                                        <span class="text-muted">-</span>
                                    <?php else: ?>
                                        <?= peso($row['to_collect']) ?> <small class="text-muted">to collect</small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= $paid ? htmlspecialchars(date('M j, Y g:i A', strtotime($row['payment_date']))) : '-' ?>
                                </td>

                                <td><?= htmlspecialchars($row['admin_name'] ?? '-') ?></td>

                                <td>
                                    <a
                                        href="../order/order_detail.php?id=<?= (int) $row['order_id'] ?>"
                                        class="btn btn-sm btn-primary"
                                        title="Open the order">
                                        View Order
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="text-center py-4">
                                No payments found.
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
