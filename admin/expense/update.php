<?php
require_once '../auth.php';
require_once '../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: expenses.php');
    exit;
}

$expense_id = filter_input(INPUT_POST, 'expense_id', FILTER_VALIDATE_INT);
$expense_type = trim($_POST['expense_type'] ?? '');
$other_type = trim($_POST['other_type'] ?? '');
$expense_date = $_POST['expense_date'] ?? '';
$amount_input = $_POST['amount'] ?? '';
$description = trim($_POST['description'] ?? '');

$allowed_types = [
    'Utilities',
    'Transportation',
    'Packaging and Supplies',
    'Inventory / Restocking',
    'Marketing',
    'Maintenance',
    'Rent',
    'Salaries / Wages'
];

if ($expense_type === 'Other') {
    $expense_type = $other_type;
} elseif (!in_array($expense_type, $allowed_types, true)) {
    $_SESSION['error'] = 'Please select a valid expense type.';
    header('Location: expenses.php');
    exit;
}

$date = DateTime::createFromFormat('!Y-m-d', $expense_date);

if (
    !$expense_id ||
    $expense_id <= 0 ||
    $expense_type === '' ||
    strlen($expense_type) > 80 ||
    !$date ||
    $date->format('Y-m-d') !== $expense_date ||
    !is_numeric($amount_input) ||
    !is_finite((float)$amount_input) ||
    (float)$amount_input <= 0 ||
    (float)$amount_input > 99999999.99 ||
    strlen($description) > 255
) {
    $_SESSION['error'] = 'Please enter valid expense details.';
    header('Location: expenses.php');
    exit;
}

$amount = (float)$amount_input;

$stmt = mysqli_prepare(
    $conn,
    "UPDATE tbl_expenses
     SET expense_type = ?,
         expense_date = ?,
         amount = ?,
         description = ?
     WHERE expense_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ssdsi",
    $expense_type,
    $expense_date,
    $amount,
    $description,
    $expense_id
);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = 'Expense updated successfully.';
} else {
    $_SESSION['error'] = 'Failed to update expense. Please try again.';
}

mysqli_stmt_close($stmt);

header('Location: expenses.php');
exit;
