<?php
include('../auth.php');
include('../../includes/config.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// read stock movement history
$sql = "SELECT
            sm.movement_id,
            p.product_name,
            sm.movement_type,
            sm.quantity_change,
            sm.reason,
            a.admin_name,
            sm.movement_date
        FROM tbl_stock_movements sm
        INNER JOIN tbl_products p
            ON sm.product_id = p.product_id
        LEFT JOIN tbl_admins a
            ON sm.admin_id = a.admin_id
        ORDER BY sm.movement_date DESC, sm.movement_id DESC";

$result = mysqli_query($conn, $sql);
?>

<?php include('../../includes/adminHeader.php'); ?>

<div class="inventory-page">

    <div class="inventory-heading">
        <div>
            <h2>Stock History</h2>
        </div>

        <a
            href="inventory.php"
            class="inventory-button secondary-button"
        >
            Back to Inventory
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="inventory-alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="inventory-alert alert-error">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="inventory-card">

        <div class="card-heading">
            <div>
                <h3>Movement Records</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Movement Type</th>
                        <th>Quantity Change</th>
                        <th>Reason</th>
                        <th>Admin</th>
                        <th>Date and Time</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $movement_type = $row['movement_type'];
                            $quantity_change = (int) $row['quantity_change'];

                            $type_class = 'movement-adjustment';

                            if ($movement_type === 'Restock') {
                                $type_class = 'movement-restock';
                            } elseif ($movement_type === 'Order Placed') {
                                $type_class = 'movement-order';
                            } elseif ($movement_type === 'Order Cancelled') {
                                $type_class = 'movement-cancelled';
                            }
                            ?>

                            <tr>
                                <td class="product-name">
                                    <?= htmlspecialchars($row['product_name']) ?>
                                </td>

                                <td>
                                    <span class="movement-badge <?= $type_class ?>">
                                        <?= htmlspecialchars($movement_type) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="quantity-change
                                        <?= $quantity_change > 0 ? 'quantity-positive' : ($quantity_change < 0 ? 'quantity-negative' : '') ?>">
                                        <?= $quantity_change > 0 ? '+' : '' ?><?= $quantity_change ?>
                                    </span>
                                </td>

                                <td class="reason-cell">
                                    <?= htmlspecialchars($row['reason'] ?? '—') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['admin_name'] ?? 'System / Unknown') ?>
                                </td>

                                <td class="date-cell">
                                    <?= htmlspecialchars($row['movement_date']) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                No stock movement records found yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>
</body>
</html>
