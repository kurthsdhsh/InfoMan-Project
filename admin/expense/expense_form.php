<?php
require_once '../auth.php';
require_once '../../includes/config.php';

$expense_id = filter_input(INPUT_GET, 'expense_id', FILTER_VALIDATE_INT);
$is_edit = $expense_id !== null && $expense_id !== false && $expense_id > 0;

$expense = [
    'expense_type' => '',
    'expense_date' => date('Y-m-d'),
    'amount' => '',
    'description' => ''
];

$expense_types = [
    'Utilities',
    'Transportation',
    'Packaging and Supplies',
    'Inventory / Restocking',
    'Marketing',
    'Maintenance',
    'Rent',
    'Salaries / Wages'
];

if ($is_edit) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT expense_type, expense_date, amount, description
         FROM tbl_expenses
         WHERE expense_id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $expense_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $existing_expense = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$existing_expense) {
        $_SESSION['error'] = 'Expense record not found.';
        header('Location: expenses.php');
        exit;
    }

    $expense = $existing_expense;
}

$saved_type = $expense['expense_type'];
$is_custom_type = $saved_type !== ''
    && !in_array($saved_type, $expense_types, true);

$selected_type = $is_custom_type ? 'Other' : $saved_type;
$custom_type = $is_custom_type ? $saved_type : '';

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);
?>

<?php require_once '../../includes/adminHeader.php'; ?>

<div class="container-fluid py-4">

    <div class="mb-4">
        <h2><?= $is_edit ? 'Edit Expense' : 'Add Expense' ?></h2>
        <p class="text-muted">
            Record and manage your business expenses.
        </p>
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

    <div class="card">
        <div class="card-body">

            <form
                action="<?= $is_edit ? 'update.php' : 'store.php' ?>"
                method="POST">

                <?php if ($is_edit): ?>
                    <input type="hidden"
                           name="expense_id"
                           value="<?= (int)$expense_id ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="expense_type" class="form-label">
                        Expense Type
                    </label>

                    <select
                        class="form-select"
                        id="expense_type"
                        name="expense_type"
                        required>

                        <option value="">Select expense type</option>

                        <?php foreach ($expense_types as $type): ?>
                            <option
                                value="<?= htmlspecialchars($type) ?>"
                                <?= $selected_type === $type ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>

                        <option value="Other"
                            <?= $selected_type === 'Other' ? 'selected' : '' ?>>
                            Other
                        </option>
                    </select>
                </div>

                <div class="mb-3"
                     id="other_type_container"
                     style="<?= $selected_type === 'Other' ? '' : 'display: none;' ?>">

                    <label for="other_type" class="form-label">
                        Specify Expense Type
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="other_type"
                        name="other_type"
                        maxlength="80"
                        value="<?= htmlspecialchars($custom_type) ?>"
                        placeholder="Enter expense type">
                </div>

                <div class="mb-3">
                    <label for="expense_date" class="form-label">
                        Expense Date
                    </label>

                    <input
                        type="date"
                        class="form-control"
                        id="expense_date"
                        name="expense_date"
                        value="<?= htmlspecialchars($expense['expense_date']) ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label for="amount" class="form-label">
                        Amount (₱)
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="amount"
                        name="amount"
                        min="0.01"
                        max="99999999.99"
                        step="0.01"
                        value="<?= htmlspecialchars((string)$expense['amount']) ?>"
                        placeholder="0.00"
                        required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">
                        Description (Optional)
                    </label>

                    <textarea
                        class="form-control"
                        id="description"
                        name="description"
                        maxlength="255"
                        rows="3"
                        placeholder="Additional details"><?= htmlspecialchars($expense['description'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?= $is_edit ? 'Update Expense' : 'Save Expense' ?>
                    </button>

                    <a href="expenses.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

</main>
</body>
</html>

<script>
const expenseType = document.getElementById('expense_type');
const otherContainer = document.getElementById('other_type_container');
const otherInput = document.getElementById('other_type');

function toggleOtherType() {
    const isOther = expenseType.value === 'Other';

    otherContainer.style.display = isOther ? 'block' : 'none';
    otherInput.required = isOther;

    if (!isOther) {
        otherInput.value = '';
    }
}

expenseType.addEventListener('change', toggleOtherType);
toggleOtherType();
</script>
