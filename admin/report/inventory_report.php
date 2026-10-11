<?php
require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/order_helpers.php';

$sql = "SELECT p.product_name, c.category_name, p.stock_quantity, p.reorder_level
        FROM tbl_products p
        INNER JOIN tbl_categories c ON p.category_id = c.category_id
        WHERE p.stock_quantity <= p.reorder_level
          AND p.product_status = 'Active'
        ORDER BY p.stock_quantity ASC, p.product_name ASC";

$result = mysqli_query($conn, $sql);

$rows = [];
$outOfStock = 0;

while ($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
    if ((int)$r['stock_quantity'] === 0) {
        $outOfStock++;
    }
}

$lowCount = count($rows);

$countResult = mysqli_query($conn, "SELECT COUNT(*) AS total_products FROM tbl_products WHERE product_status = 'Active'");
$countRow = mysqli_fetch_assoc($countResult);
$totalProducts = (int)$countRow['total_products'];

if (isset($_GET['pdf'])) {
    require_once '../../includes/report_pdf.php';

    $pdf = new ReportPDF('Inventory and Low Stock', 'Stock level as of ' . date('M j, Y g:i A'));

    $pdf->summaryCards([
        ['Total products', $totalProducts],
        ['Out of stock', $outOfStock],
        ['Needs restock', $lowCount]
    ]);
    $pdf->sectionTitle('Active products at or below reorder level');

    $headers = ['Product', 'Category', 'In Stock', 'Reorder Level', 'Status'];
    $widths  = [62, 38, 26, 30, 34];
    $aligns  = ['L', 'L', 'R', 'R', 'L'];

    $pdfRows = [];
    foreach ($rows as $r) {
        $status = ((int)$r['stock_quantity'] === 0) ? 'Out of stock' : 'Low stock';
        $pdfRows[] = [
            $r['product_name'],
            $r['category_name'],
            $r['stock_quantity'],
            $r['reorder_level'],
            $status
        ];
    }

    $pdf->drawTable($headers, $widths, $aligns, $pdfRows, null, 2);
    $pdf->Output('I', 'inventory_low_stock_report.pdf');
    exit;
}
?>

<?php require_once '../../includes/adminHeader.php'; ?>

<div class="admin-container">

    <div class="mb-4">
        <h2>Inventory and Low Stock</h2>
        <p class="text-muted mb-0">Active products at or below their reorder level.</p>
    </div>

    <div class="card">
        <div class="card-body">

            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; align-items:center;">
                <a href="inventory_report.php?pdf=1" target="_blank" class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="../inventory/inventory.php" class="btn btn-secondary">Go to Stock Management</a>
            </div>

            <p class="text-muted mb-3">
                <?= $lowCount ?> of <?= $totalProducts ?> products need restocking
                (<?= $outOfStock ?> out of stock).
            </p>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">In Stock</th>
                            <th class="text-end">Reorder Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($lowCount > 0): ?>

                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= h($r['product_name']) ?></td>
                                <td><?= h($r['category_name']) ?></td>
                                <td class="text-end"><?= (int)$r['stock_quantity'] ?></td>
                                <td class="text-end"><?= (int)$r['reorder_level'] ?></td>
                                <td>
                                    <?php if ((int)$r['stock_quantity'] === 0): ?>
                                        <span class="status-badge status-out">Out of stock</span>
                                    <?php else: ?>
                                        <span class="status-badge status-low">Low stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="text-center py-4">
                                All products are above their reorder level.
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