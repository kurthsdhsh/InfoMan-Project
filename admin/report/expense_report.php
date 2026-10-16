<?php
require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/order_helpers.php';
require_once '../../includes/report_helpers.php';

[$from, $to, $error] = getReportDates();

$sql = "SELECT e.expense_type,
               COUNT(e.expense_id) AS entries,
               SUM(e.amount) AS total_amount,
               GROUP_CONCAT(DISTINCT a.first_name ORDER BY a.first_name SEPARATOR ', ') AS recorded_by
        FROM tbl_expenses e
        INNER JOIN tbl_customers a ON e.admin_id = a.customer_id
        WHERE e.expense_date BETWEEN ? AND ?
        GROUP BY e.expense_type
        ORDER BY total_amount DESC, e.expense_type ASC";

$result = mysqli_execute_query($conn, $sql, [$from, $to]);

$rows = [];
$totalEntries = 0;
$totalAmount = 0;

while ($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
    $totalEntries += (int)$r['entries'];
    $totalAmount += (float)$r['total_amount'];
}

$typeCount = count($rows);

if (isset($_GET['pdf'])) {
    require_once '../../includes/report_pdf.php';

    $subtitle = 'Expenses: ' . date('M j, Y', strtotime($from)) . ' to ' . date('M j, Y', strtotime($to));
    $pdf = new ReportPDF('Expenses Summary', $subtitle);

    $pdf->summaryCards([
        ['Expense types', $typeCount],
        ['Entries', $totalEntries],
        ['Total expenses', pdfMoney($totalAmount)]
    ]);
    $pdf->sectionTitle('Totals per expense type');

    $headers = ['Expense Type', 'Entries', 'Total Amount', 'Recorded By'];
    $widths  = [64, 24, 42, 60];
    $aligns  = ['L', 'R', 'R', 'L'];

    $pdfRows = [];
    foreach ($rows as $r) {
        $pdfRows[] = [
            $r['expense_type'],
            $r['entries'],
            pdfMoney($r['total_amount']),
            $r['recorded_by']
        ];
    }

    $totalRow = ['Total', $totalEntries, pdfMoney($totalAmount), ''];

    $pdf->drawTable($headers, $widths, $aligns, $pdfRows, $totalRow, 2);
    $pdf->Output('I', 'expenses_summary.pdf');
    exit;
}

$pdfLink = 'expense_report.php?from=' . urlencode($from) . '&to=' . urlencode($to) . '&pdf=1';
?>

<?php require_once '../../includes/adminSidebar.php'; ?>

<div class="admin-container">

    <div class="mb-4">
        <h2>Expenses Summary</h2>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">

            <form method="GET" action="expense_report.php"
                  style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; align-items:center;">

                <label for="from">From</label>
                <input type="date" id="from" name="from" class="form-control"
                       value="<?= h($from) ?>" style="max-width:180px;">

                <label for="to">To</label>
                <input type="date" id="to" name="to" class="form-control"
                       value="<?= h($to) ?>" style="max-width:180px;">

                <button type="submit" class="btn btn-primary">Generate</button>
                <a href="expense_report.php" class="btn btn-secondary">Reset</a>
                <a href="<?= h($pdfLink) ?>" target="_blank" class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Expense Type</th>
                            <th class="text-end">Entries</th>
                            <th class="text-end">Total Amount</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($typeCount > 0): ?>

                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= h($r['expense_type']) ?></td>
                                <td class="text-end"><?= (int)$r['entries'] ?></td>
                                <td class="text-end"><?= peso($r['total_amount']) ?></td>
                                <td><?= h($r['recorded_by']) ?></td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4" class="text-center py-4">
                                No expenses in this date range.
                            </td>
                        </tr>

                    <?php endif; ?>
                    </tbody>

                    <?php if ($typeCount > 0): ?>
                        <tfoot>
                            <tr style="background-color:#FFF3DC; font-weight:600;">
                                <td>Total</td>
                                <td class="text-end"><?= $totalEntries ?></td>
                                <td class="text-end"><?= peso($totalAmount) ?></td>
                                <td></td>
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