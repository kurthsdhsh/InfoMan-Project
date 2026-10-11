<?php
require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/order_helpers.php';
require_once '../../includes/report_helpers.php';

[$from, $to, $error] = getReportDates();

$sql = "SELECT o.order_id, o.order_date, o.fulfillment_method,
               c.first_name, c.last_name,
               p.payment_status,
               SUM(oi.quantity * oi.unit_price) AS items_total,
               COALESCE(d.delivery_fee, 0) AS delivery_fee
        FROM tbl_orders o
        INNER JOIN tbl_customers c ON o.customer_id = c.customer_id
        INNER JOIN tbl_payments p ON o.order_id = p.order_id
        INNER JOIN tbl_order_items oi ON o.order_id = oi.order_id
        LEFT JOIN tbl_deliveries d ON o.order_id = d.order_id
        WHERE o.order_status = 'Completed'
          AND DATE(o.order_date) BETWEEN ? AND ?
        GROUP BY o.order_id, o.order_date, o.fulfillment_method,
                 c.first_name, c.last_name, p.payment_status, d.delivery_fee
        ORDER BY o.order_date DESC";

$result = mysqli_execute_query($conn, $sql, [$from, $to]);

$rows = [];
while ($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
}

// Grand total: COUNT and SUM done by MySQL
$sumSql = "SELECT COUNT(DISTINCT o.order_id) AS order_count,
                  COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS items_sum
           FROM tbl_orders o
           INNER JOIN tbl_order_items oi ON o.order_id = oi.order_id
           WHERE o.order_status = 'Completed'
             AND DATE(o.order_date) BETWEEN ? AND ?";
$sum = mysqli_fetch_assoc(mysqli_execute_query($conn, $sumSql, [$from, $to]));

$feeSql = "SELECT COALESCE(SUM(d.delivery_fee), 0) AS fee_sum
           FROM tbl_orders o
           INNER JOIN tbl_deliveries d ON o.order_id = d.order_id
           WHERE o.order_status = 'Completed'
             AND DATE(o.order_date) BETWEEN ? AND ?";
$fee = mysqli_fetch_assoc(mysqli_execute_query($conn, $feeSql, [$from, $to]));

$orderCount = (int)$sum['order_count'];
$deliveryTotal = (float)$fee['fee_sum'];
$grandTotal = (float)$sum['items_sum'] + $deliveryTotal;

// PDF export (must happen before any HTML is printed)
if (isset($_GET['pdf'])) {
    require_once '../../includes/report_pdf.php';

    $subtitle = 'Completed orders: ' . date('M j, Y', strtotime($from)) . ' to ' . date('M j, Y', strtotime($to));
    $pdf = new ReportPDF('Sales Report', $subtitle);

    $pdf->summaryCards([
        ['Completed orders', $orderCount],
        ['Delivery fees', pdfMoney($deliveryTotal)],
        ['Total sales', pdfMoney($grandTotal)]
    ]);
    $pdf->sectionTitle('Order details');

    $headers = ['Order #', 'Date', 'Customer', 'Method', 'Payment', 'Delivery Fee', 'Total'];
    $widths  = [24, 26, 44, 22, 20, 26, 28];
    $aligns  = ['L', 'L', 'L', 'L', 'L', 'R', 'R'];

    $pdfRows = [];
    foreach ($rows as $r) {
        $total = (float)$r['items_total'] + (float)$r['delivery_fee'];
        $pdfRows[] = [
            orderNumber((int)$r['order_id']),
            date('M j, Y', strtotime($r['order_date'])),
            $r['first_name'] . ' ' . $r['last_name'],
            $r['fulfillment_method'],
            $r['payment_status'],
            pdfMoney($r['delivery_fee']),
            pdfMoney($total)
        ];
    }

    $totalRow = ['Grand total', '', $orderCount . ' orders', '', '', pdfMoney($deliveryTotal), pdfMoney($grandTotal)];

    $pdf->drawTable($headers, $widths, $aligns, $pdfRows, $totalRow, 6);
    $pdf->Output('I', 'sales_report.pdf');
    exit;
}

$pdfLink = 'sales.php?from=' . urlencode($from) . '&to=' . urlencode($to) . '&pdf=1';
?>

<?php require_once '../../includes/adminHeader.php'; ?>

<div class="admin-container">

    <div class="mb-4">
        <h2>Sales Report</h2>
        <p class="text-muted mb-0">Completed orders only</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">

            <form method="GET" action="sales.php"
                  style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; align-items:center;">

                <label for="from">From</label>
                <input type="date" id="from" name="from" class="form-control"
                       value="<?= h($from) ?>" style="max-width:180px;">

                <label for="to">To</label>
                <input type="date" id="to" name="to" class="form-control"
                       value="<?= h($to) ?>" style="max-width:180px;">

                <button type="submit" class="btn btn-primary">Generate</button>
                <a href="sales.php" class="btn btn-secondary">Reset</a>
                <a href="<?= h($pdfLink) ?>" target="_blank" class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Method</th>
                            <th>Payment</th>
                            <th class="text-end">Delivery Fee</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($rows) > 0): ?>

                        <?php foreach ($rows as $r): ?>
                            <?php $total = (float)$r['items_total'] + (float)$r['delivery_fee']; ?>
                            <tr>
                                <td><?= h(orderNumber((int)$r['order_id'])) ?></td>
                                <td><?= h(date('M j, Y', strtotime($r['order_date']))) ?></td>
                                <td><?= h($r['first_name'] . ' ' . $r['last_name']) ?></td>
                                <td><?= h($r['fulfillment_method']) ?></td>
                                <td>
                                    <span class="badge <?= $r['payment_status'] === 'Paid' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                        <?= h($r['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="text-end"><?= peso($r['delivery_fee']) ?></td>
                                <td class="text-end"><?= peso($total) ?></td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center py-4">
                                No completed orders in this date range.
                            </td>
                        </tr>

                    <?php endif; ?>
                    </tbody>

                    <?php if (count($rows) > 0): ?>
                        <tfoot>
                            <tr style="background-color:#FFF3DC; font-weight:600;">
                                <td colspan="5">Grand total (<?= $orderCount ?> orders)</td>
                                <td class="text-end"><?= peso($deliveryTotal) ?></td>
                                <td class="text-end"><?= peso($grandTotal) ?></td>
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