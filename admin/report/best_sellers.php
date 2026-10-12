<?php
require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/order_helpers.php';
require_once '../../includes/report_helpers.php';

[$from, $to, $error] = getReportDates();

$sql = "SELECT p.product_name, c.category_name,
               SUM(oi.quantity) AS units_sold,
               SUM(oi.quantity * oi.unit_price) AS revenue
        FROM tbl_order_items oi
        INNER JOIN tbl_orders o ON oi.order_id = o.order_id
        INNER JOIN tbl_products p ON oi.product_id = p.product_id
        INNER JOIN tbl_categories c ON p.category_id = c.category_id
        WHERE o.order_status = 'Completed'
          AND DATE(o.order_date) BETWEEN ? AND ?
        GROUP BY p.product_id, p.product_name, c.category_name
        ORDER BY units_sold DESC, revenue DESC, p.product_name ASC";

$result = mysqli_execute_query($conn, $sql, [$from, $to]);

$rows = [];
$totalUnits = 0;
$totalRevenue = 0;

while ($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
    $totalUnits += (int)$r['units_sold'];
    $totalRevenue += (float)$r['revenue'];
}

$productCount = count($rows);

if (isset($_GET['pdf'])) {
    require_once '../../includes/report_pdf.php';

    $subtitle = 'Completed orders: ' . date('M j, Y', strtotime($from)) . ' to ' . date('M j, Y', strtotime($to));
    $pdf = new ReportPDF('Best-Selling Products', $subtitle);

    $pdf->summaryCards([
        ['Products sold', $productCount],
        ['Units sold', $totalUnits],
        ['Product revenue', pdfMoney($totalRevenue)]
    ]);
    $pdf->sectionTitle('Products ranked by units sold');

    $headers = ['Rank', 'Product', 'Category', 'Units Sold', 'Revenue'];
    $widths  = [14, 64, 40, 32, 40];
    $aligns  = ['C', 'L', 'L', 'R', 'R'];

    $pdfRows = [];
    $rank = 1;
    foreach ($rows as $r) {
        $pdfRows[] = [
            $rank,
            $r['product_name'],
            $r['category_name'],
            $r['units_sold'],
            pdfMoney($r['revenue'])
        ];
        $rank++;
    }

    $totalRow = ['', 'Total', '', $totalUnits, pdfMoney($totalRevenue)];

    $pdf->drawTable($headers, $widths, $aligns, $pdfRows, $totalRow, 4);
    $pdf->Output('I', 'best_selling_products.pdf');
    exit;
}

$pdfLink = 'best_sellers.php?from=' . urlencode($from) . '&to=' . urlencode($to) . '&pdf=1';
?>

<?php require_once '../../includes/adminSidebar.php'; ?>

<div class="admin-container">

    <div class="mb-4">
        <h2>Best-Selling Products</h2>
        <p class="text-muted mb-0">For completed orders only</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">

            <form method="GET" action="best_sellers.php"
                  style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; align-items:center;">

                <label for="from">From</label>
                <input type="date" id="from" name="from" class="form-control"
                       value="<?= h($from) ?>" style="max-width:180px;">

                <label for="to">To</label>
                <input type="date" id="to" name="to" class="form-control"
                       value="<?= h($to) ?>" style="max-width:180px;">

                <button type="submit" class="btn btn-primary">Generate</button>
                <a href="best_sellers.php" class="btn btn-secondary">Reset</a>
                <a href="<?= h($pdfLink) ?>" target="_blank" class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">Units Sold</th>
                            <th class="text-end">Revenue</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($productCount > 0): ?>

                        <?php $rank = 1; ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= $rank ?></td>
                                <td><?= h($r['product_name']) ?></td>
                                <td><?= h($r['category_name']) ?></td>
                                <td class="text-end"><?= (int)$r['units_sold'] ?></td>
                                <td class="text-end"><?= peso($r['revenue']) ?></td>
                            </tr>
                            <?php $rank++; ?>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="text-center py-4">
                                No sales in this date range.
                            </td>
                        </tr>

                    <?php endif; ?>
                    </tbody>

                    <?php if ($productCount > 0): ?>
                        <tfoot>
                            <tr style="background-color:#FFF3DC; font-weight:600;">
                                <td colspan="3">Total (<?= $productCount ?> products)</td>
                                <td class="text-end"><?= $totalUnits ?></td>
                                <td class="text-end"><?= peso($totalRevenue) ?></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>

        </div>
    </div>

</div>

</main>
</body>
</html>