<?php
include('../auth.php');
include('../../includes/config.php');
include('../../includes/filter_helper.php');

// token that protects the enable/disable and delete buttons
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// ---- steps used by the hard delete (a customer's orders are deleted with the customer) ----
// [SQL, optional]. An optional step is skipped when that table does not exist.
// Change the table / column names here if yours are different.
$deleteSteps = [
    ["DELETE p FROM tbl_payments p INNER JOIN tbl_orders o ON p.order_id = o.order_id WHERE o.customer_id = ?", true],
    ["DELETE oi FROM tbl_order_items oi INNER JOIN tbl_orders o ON oi.order_id = o.order_id WHERE o.customer_id = ?", true],
    ["DELETE FROM tbl_orders WHERE customer_id = ?", true],
    ["DELETE FROM tbl_customers WHERE customer_id = ?", false],
];


// ---------- actions: enable/disable and delete (runs BEFORE any HTML is printed) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['customer_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {

        $_SESSION['error'] = 'Invalid request. Please try again.';

    } elseif ($id <= 0) {

        $_SESSION['error'] = 'Customer not found.';
    
    } elseif ($id === (int)$_SESSION['admin_id'] && in_array($action, ['toggle', 'delete'], true)) {

        $_SESSION['error'] = 'You cannot deactivate or delete your own account.';

    } elseif ($action === 'delete' && mysqli_num_rows(mysqli_execute_query($conn,
            "SELECT 1 FROM tbl_customers WHERE customer_id = ? AND role = 'admin'", [$id])) > 0) {

        $_SESSION['error'] = 'Admins cannot be deleted. Deactivate the account instead.';

    } elseif ($action === 'toggle') {

        // Active <-> Inactive
        mysqli_execute_query(
            $conn,
            "UPDATE tbl_customers
             SET account_status = IF(LOWER(account_status) = 'active', 'Inactive', 'Active')
             WHERE customer_id = ?",
            [$id]
        );
        $_SESSION['success'] = 'Account status updated.';

    } elseif ($action === 'delete') {

        try {
            mysqli_begin_transaction($conn);

            foreach ($deleteSteps as [$sql, $optional]) {
                try {
                    mysqli_execute_query($conn, $sql, [$id]);
                } catch (mysqli_sql_exception $e) {
                    // 1146 = table does not exist: fine for optional steps
                    if ($optional && $e->getCode() == 1146) {
                        continue;
                    }
                    throw $e;
                }
            }

            mysqli_commit($conn);
            $_SESSION['success'] = 'Customer and their orders were deleted.';

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = ($_SERVER['SERVER_NAME'] === 'localhost')
                ? 'Could not delete: ' . $e->getMessage()
                : 'Could not delete this customer.';
        }
    }

    header("Location: user.php");
    exit();
}


// ---------- list with search and status filter ----------
$search = adminFilterValue('search');
$statusFilter = adminFilterValue('status');

$conditions = [];
$params = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?
                      OR email LIKE ? OR contact_number LIKE ?)";
    array_push($params, $like, $like, $like, $like, $like);
}

if ($statusFilter !== '') {
    $conditions[] = 'account_status = ?';
    $params[] = $statusFilter;
}

$sql = "SELECT customer_id, first_name, last_name, email, contact_number, account_status, role
        FROM tbl_customers";

if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY customer_id DESC';

$result = mysqli_execute_query($conn, $sql, $params ?: null);
$itemCount = mysqli_num_rows($result);

// how many orders each customer has (shown in the table and in the delete warning)
$orderCounts = [];
$ordersKnown = true;
try {
    $oc = mysqli_query($conn, "SELECT customer_id, COUNT(*) AS n FROM tbl_orders GROUP BY customer_id");
    while ($r = mysqli_fetch_assoc($oc)) {
        $orderCounts[$r['customer_id']] = (int)$r['n'];
    }
} catch (Throwable $e) {
    $ordersKnown = false;   // no orders table found
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$pageTitle = 'Customers';
include('../../includes/adminSidebar.php');
?>

<div class="admin-container">

    <?php if ($success != ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error != ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>


    <div class="admin-toolbar">
    <h2>Customers (<?= $itemCount ?>)</h2>
    <a href="user_add.php" class="btn-add" role="button">Add user</a>
    </div>

    <?php
    renderAdminFilterForm(
        basename($_SERVER['PHP_SELF']),
        $search,
        'Search name, email or contact...',
        [
            [
                'name' => 'status',
                'label' => 'All Statuses',
                'options' => [
                    'Active' => 'Active',
                    'Inactive' => 'Inactive'
                ],
                'selected' => $statusFilter
            ]
        ]
    );
    ?>


    <div class="table-card">

        <table class="admin-table customers-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Contact</th>
                    <th>Orders</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($itemCount === 0): ?>
                    <tr>
                        <td colspan="8">No customers found.</td>
                    </tr>
                <?php endif; ?>

                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php
                    $id = (int)$row['customer_id'];
                    $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
                    $isActive = strtolower($row['account_status']) === 'active';
                    $statusClass = 'status-' . strtolower(preg_replace('/[^a-z]/i', '', $row['account_status']));
                    $orders = $orderCounts[$id] ?? 0;

                    // the "Are you sure?" warning
                    $warning = "Are you sure you want to permanently delete {$fullName}?\n\n"
                        . ($ordersKnown ? "Their {$orders} order(s)" : "Their orders")
                        . " will be deleted too.\nThis cannot be undone.";
                    ?>
                    <tr>
                        <td><?= $id ?></td>
                        <td><?= htmlspecialchars($fullName) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['role']) ?></td>
                        <td><?= htmlspecialchars($row['contact_number'] ?? '') ?></td>
                        <td><?= $ordersKnown ? $orders : '—' ?></td>
                        <td><span class="status <?= $statusClass ?>"><?= htmlspecialchars($row['account_status']) ?></span></td>
                        <td>

                            <!-- edit details -->
                            <a href="customer_form.php?id=<?= $id ?>" title="Edit details">
                                <i class="fa-regular fa-pen-to-square" style="color: blue"></i>
                            </a>

                            <!-- enable / disable -->
                            <form method="POST" action="user.php" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="customer_id" value="<?= $id ?>">
                                <button type="submit" class="icon-btn"
                                    title="<?= $isActive ? 'Disable account' : 'Enable account' ?>">
                                    <?php if ($isActive): ?>
                                        <i class="fa-solid fa-ban" style="color: #B77466"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-check" style="color: green"></i>
                                    <?php endif; ?>
                                </button>
                            </form>

                            <!-- hard delete (with the warning) -->
                            <?php if ($row['role'] !== 'admin'): ?>
                            <form method="POST" action="user.php" class="inline-form"
                                onsubmit='return confirm(<?= htmlspecialchars(json_encode($warning), ENT_QUOTES) ?>);'>
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="customer_id" value="<?= $id ?>">
                                <button type="submit" class="icon-btn" title="Delete customer">
                                    <i class="fa-solid fa-trash" style="color: red"></i>
                                </button>   
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>

            </tbody>

        </table>

    </div>

</div>

</main>
</body>

</html>