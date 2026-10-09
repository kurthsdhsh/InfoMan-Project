<?php
require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/filter_helper.php';


// Read search and expense-type filters.
$search = adminFilterValue('search');
$typeFilter = adminFilterValue('expense_type');

$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = '(e.expense_type LIKE ? OR e.description LIKE ?)';

    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

if ($typeFilter !== '') {
    $conditions[] = 'e.expense_type = ?';
    $params[] = $typeFilter;
    $types .= 's';
}

$sql = "SELECT e.*, a.admin_name
        FROM tbl_expenses e
        LEFT JOIN tbl_admins a
            ON e.admin_id = a.admin_id";

if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY e.expense_date DESC, e.expense_id DESC';

$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    $bindParams = [$types];

    foreach ($params as $key => $value) {
        $bindParams[] = &$params[$key];
    }

    call_user_func_array(
        [$stmt, 'bind_param'],
        $bindParams
    );
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$itemCount = mysqli_num_rows($result);

// Get the available expense types for the dropdown.
$typeResult = mysqli_query(
    $conn,
    "SELECT DISTINCT expense_type
     FROM tbl_expenses
     ORDER BY expense_type ASC"
);

$typeOptions = [];

while ($typeRow = mysqli_fetch_assoc($typeResult)) {
    $type = $typeRow['expense_type'];
    $typeOptions[$type] = $type;
}
?>

<?php
// Get expense summary statistics
$summary_sql = "SELECT
                    COALESCE(SUM(amount), 0) AS total_expenses,
                    COALESCE(SUM(
                        CASE
                            WHEN expense_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                             AND expense_date < DATE_FORMAT(
                                 CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01'
                             )
                            THEN amount
                            ELSE 0
                        END
                    ), 0) AS monthly_expenses,
                    COUNT(*) AS total_records,
                    COALESCE(MAX(amount), 0) AS highest_expense
                FROM tbl_expenses";

$summary_result = mysqli_query($conn, $summary_sql);

if (!$summary_result) {
    die("Error retrieving expense summary: " . mysqli_error($conn));
}

$summary = mysqli_fetch_assoc($summary_result);

$total_expenses = $summary['total_expenses'];
$monthly_expenses = $summary['monthly_expenses'];
$total_records = $summary['total_records'];
$highest_expense = $summary['highest_expense'];

// Session messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);
?>

<?php require_once '../../includes/adminHeader.php'; ?>

<div class="admin-container">
        
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Expense Management</h2>
        </div>

        <a href="expense_form.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Expense
        </a>
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

    <!-- Expense Summary Cards -->
    <div class="row g-3 mb-4">

        <!-- Total Expenses -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">Total Expenses</h6>
                        <i class="fas fa-wallet fa-lg" style="color: #B77466;"></i>
                    </div>

                    <h4 class="mb-1">
                        ₱<?= number_format((float)$total_expenses, 2) ?>
                    </h4>

                    <small class="text-muted">All recorded expenses</small>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">This Month</h6>
                        <i class="fas fa-calendar-days fa-lg" style="color: #957C62;"></i>
                    </div>

                    <h4 class="mb-1">
                        ₱<?= number_format((float)$monthly_expenses, 2) ?>
                    </h4>

                    <small class="text-muted">
                        <?= date('F Y') ?> expenses
                    </small>
                </div>
            </div>
        </div>

        <!-- Total Records -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">Total Records</h6>
                        <i class="fas fa-receipt fa-lg" style="color: #957C62;"></i>
                    </div>

                    <h4 class="mb-1">
                        <?= number_format((int)$total_records) ?>
                    </h4>

                    <small class="text-muted">Recorded expense entries</small>
                </div>
            </div>
        </div>

        <!-- Highest Expense -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">Highest Expense</h6>
                        <i class="fas fa-arrow-trend-up fa-lg" style="color: #B77466;"></i>
                    </div>

                    <h4 class="mb-1">
                        ₱<?= number_format((float)$highest_expense, 2) ?>
                    </h4>

                    <small class="text-muted">Largest single expense</small>
                </div>
            </div>
        </div>

    </div>
    <!-- End Expense Summary Cards -->

    <div class="card">
        <div class="card-body">

        
                <?php
                renderAdminFilterForm(
                    basename($_SERVER['PHP_SELF']),
                    $search,
                    'Search expense type or description...',
                    [
                        [
                            'name' => 'expense_type',
                            'label' => 'All Expense Types',
                            'options' => $typeOptions,
                            'selected' => $typeFilter
                        ]
                    ]
                );
                ?>

                <p class="text-muted mb-3">
                    Matching records: <?= (int)$itemCount ?>
                </p>


            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Expense Type</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Recorded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>

                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <?= (int)$row['expense_id'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['expense_type']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['expense_date']) ?>
                                </td>

                                <td>
                                    ₱<?= number_format((float)$row['amount'], 2) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['description'] ?? '') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['admin_name'] ?? 'Unknown') ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">

                                        <a
                                            href="expense_form.php?expense_id=<?= (int)$row['expense_id'] ?>"
                                            class="btn btn-sm btn-warning"
                                            title="Edit expense">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <form
                                            action="delete.php"
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this expense?');">

                                            <input
                                                type="hidden"
                                                name="expense_id"
                                                value="<?= (int)$row['expense_id'] ?>">

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                                title="Delete expense">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                        </form>

                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center py-4">
                                No expenses recorded yet.
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
