<?php
include('auth.php');
$pageTitle = 'Admin Dashboard';
include('../includes/adminSidebar.php');
include('../includes/config.php');

// ---- table / column names: change these if yours are different ----
$stockMovesTable     = 'tbl_stock_movements';
$ordersTable         = 'tbl_orders';
$orderStatusColumn   = 'order_status';
$pendingValue        = 'Pending';
$expensesTable       = 'tbl_expenses';
$expenseAmountColumn = 'amount';
$lowStockLimit       = 5;     // products with this many or fewer are "low stock"

// runs a query that returns one number; returns null if the table/column does not exist
function dashValue($conn, $sql)
{
    try {
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            return null;
        }
        $row = mysqli_fetch_row($result);
        return $row ? $row[0] : null;
    } catch (Throwable $e) {
        return null;
    }
}

function dashNumber($value)
{
    return $value === null ? '—' : number_format((float)$value);
}

$categoryCount  = dashValue($conn, "SELECT COUNT(*) FROM tbl_categories");
$productCount   = dashValue($conn, "SELECT COUNT(*) FROM tbl_products");
$activeProducts = dashValue($conn, "SELECT COUNT(*) FROM tbl_products WHERE product_status = 'Active'");
$totalStock     = dashValue($conn, "SELECT COALESCE(SUM(stock_quantity), 0) FROM tbl_products");
$lowStock       = dashValue($conn, "SELECT COUNT(*) FROM tbl_products WHERE stock_quantity > 0 AND stock_quantity <= $lowStockLimit");
$outOfStock     = dashValue($conn, "SELECT COUNT(*) FROM tbl_products WHERE stock_quantity <= 0");
$movementCount  = dashValue($conn, "SELECT COUNT(*) FROM $stockMovesTable");
$orderCount     = dashValue($conn, "SELECT COUNT(*) FROM $ordersTable");
$pendingOrders  = dashValue($conn, "SELECT COUNT(*) FROM $ordersTable WHERE $orderStatusColumn = '$pendingValue'");
$expenseTotal   = dashValue($conn, "SELECT COALESCE(SUM($expenseAmountColumn), 0) FROM $expensesTable");

// one card per sidebar page: [title, big number, icon, link, small text]
$cards = [
    ['Categories',    dashNumber($categoryCount), 'fa-table-cells-large', 'category/categories.php',       'Total categories'],
    ['Products',      dashNumber($productCount),  'fa-cube',              'product/products.php',          dashNumber($activeProducts) . ' active'],
    ['Inventory',     dashNumber($totalStock),    'fa-warehouse',         'inventory/inventory.php',       'units in stock · ' . dashNumber($lowStock) . ' low · ' . dashNumber($outOfStock) . ' out'],
    ['Stock History', dashNumber($movementCount), 'fa-clock-rotate-left', 'inventory/stock_history.php',   'stock movements'],
    ['Orders',        dashNumber($orderCount),    'fa-cart-shopping',     'order/orders.php',              dashNumber($pendingOrders) . ' pending'],
    ['Expenses',      $expenseTotal === null ? '—' : '₱' . number_format($expenseTotal, 2), 'fa-money-bill-1', 'expense/expenses.php', 'total expenses'],
];

// products that need restocking
$lowStockResult = mysqli_query(
    $conn,
    "SELECT product_id, product_name, stock_quantity, product_status
     FROM tbl_products
     WHERE stock_quantity <= " . (int)$lowStockLimit . "
     ORDER BY stock_quantity ASC, product_id DESC
     LIMIT 8"
);
?>

<h1 class="page-title">Dashboard</h1>

<!-- one card for every page in the sidebar -->
<div class="dash-grid">
    <?php foreach ($cards as [$title, $value, $icon, $link, $note]): ?>
        <a class="dash-card" href="/InfoMan-Project/admin/<?= $link ?>">
            <span class="dash-icon"><i class="fa-solid <?= $icon ?>"></i></span>
            <span class="dash-info">
                <span class="dash-title"><?= htmlspecialchars($title) ?></span>
                <span class="dash-value"><?= htmlspecialchars($value) ?></span>
                <span class="dash-note"><?= htmlspecialchars($note) ?></span>
            </span>
        </a>
    <?php endforeach; ?>
</div>

<!-- low stock list -->
<div class="admin-container dash-section">

    <div class="admin-toolbar">
        <h2>Low Stock Products</h2>
        <a href="/InfoMan-Project/admin/inventory/inventory.php" class="btn-add" role="button">Manage Stock</a>
    </div>

    <div class="table-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lowStockResult && mysqli_num_rows($lowStockResult) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($lowStockResult)): ?>
                        <?php
                        $stockClass  = $row['stock_quantity'] <= 0 ? 'status-inactive' : 'status-active';
                        $statusClass = 'status-' . strtolower(preg_replace('/[^a-z]/i', '', $row['product_status']));
                        ?>
                        <tr>
                            <td><?= (int)$row['product_id'] ?></td>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><span class="status <?= $stockClass ?>"><?= (int)$row['stock_quantity'] ?></span></td>
                            <td><span class="status <?= $statusClass ?>"><?= htmlspecialchars($row['product_status']) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">All products are well stocked.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</main>
</body>

</html>