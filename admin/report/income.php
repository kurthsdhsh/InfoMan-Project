<?php
require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/order_helpers.php';
require_once '../../includes/report_helpers.php';

[$from, $to, $error] = getReportDates();

$incomeSql = "SELECT COALESCE(SUM(amount_paid), 0) AS total_income,
                     COUNT(payment_id) AS payment_count
              FROM tbl_payments
              WHERE payment_status = 'Paid'
                AND DATE(payment_date) BETWEEN ? AND ?";
$income = mysqli_fetch_assoc(mysqli_execute_query($conn, $incomeSql, [$from, $to]));

$expenseSql = "SELECT COALESCE(SUM(amount), 0) AS total_expenses,
                      COUNT(expense_id) AS expense_count
               FROM tbl_expenses
               WHERE expense_date BETWEEN ? AND ?";
$expense = mysqli_fetch_assoc(mysqli_execute_query($conn, $expenseSql, [$from, $to]));

$totalIncome = (float)$income['total_income'];
$paymentCount = (int)$income['payment_count'];
$totalExpenses = (float)$expense['total_expenses'];
$expenseCount = (int)$expense['expense_count'];

$netIncome = $totalIncome - $totalExpenses;
?>

<?php require_once '../../includes/adminSidebar.php'; ?>

<div class="admin-container">

    <div class="mb-4">
        <h2>Total Income and Net Income</h2>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">

            <form method="GET" action="income.php"
                  style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">

                <label for="from">From</label>
                <input type="date" id="from" name="from" class="form-control"
                       value="<?= h($from) ?>" style="max-width:180px;">

                <label for="to">To</label>
                <input type="date" id="to" name="to" class="form-control"
                       value="<?= h($to) ?>" style="max-width:180px;">

                <button type="submit" class="btn btn-primary">Generate</button>
                <a href="income.php" class="btn btn-secondary">Reset</a>
            </form>

        </div>
    </div>

    <p class="text-muted mb-3">
        Showing <?= h(date('M j, Y', strtotime($from))) ?> to <?= h(date('M j, Y', strtotime($to))) ?>
    </p>

    <div class="row g-3 mb-4">

        <!-- Total Income -->
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">Total Income</h6>
                        <i class="fas fa-coins fa-lg" style="color: #B77466;"></i>
                    </div>

                    <h4 class="mb-1"><?= peso($totalIncome) ?></h4>

                    <small class="text-muted">
                        <?= $paymentCount ?> paid <?= $paymentCount === 1 ? 'order' : 'orders' ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Total Expenses -->
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">Total Expenses</h6>
                        <i class="fas fa-receipt fa-lg" style="color: #957C62;"></i>
                    </div>

                    <h4 class="mb-1"><?= peso($totalExpenses) ?></h4>

                    <small class="text-muted">
                        <?= $expenseCount ?> expense <?= $expenseCount === 1 ? 'entry' : 'entries' ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Net Income -->
        <div class="col-12 col-md-4">
            <div class="card h-100" style="background-color:#FFF3DC; border-color:#B77466;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">Net Income</h6>
                        <i class="fas fa-scale-balanced fa-lg" style="color: #B77466;"></i>
                    </div>

                    <h4 class="mb-1 <?= $netIncome < 0 ? 'text-danger' : '' ?>">
                        <?= peso($netIncome) ?>
                    </h4>

                    <small class="text-muted">
                        <?= $netIncome < 0 ? 'Expenses are higher than income' : 'Income minus expenses' ?>
                    </small>
                </div>
            </div>
        </div>

    </div>

</div>

</main>
</body>
</html>