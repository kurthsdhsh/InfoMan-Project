<?php
require_once '../auth.php';
require_once '../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: expenses.php');
    exit;
}

$expense_type = trim($_POST['expense_type'] ?? '');
$other_type = trim($_POST['other_type'] ?? '');
$expense_date = $_POST['expense_date'] ?? '';
$amount_input = $_POST['amount'] ?? '';
$description = trim($_POST['description'] ?? '');
$admin_id = (int)($_SESSION['admin_id'] ?? 0);

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
    header('Location: expense_form.php');
    exit;
}

$date = DateTime::createFromFormat('!Y-m-d', $expense_date);

if (
    $expense_type === '' ||
    strlen($expense_type) > 80 ||
    ($other_type !== '' && strlen($other_type) > 80) ||
    !$date ||
    $date->format('Y-m-d') !== $expense_date ||
    !is_numeric($amount_input) ||
    !is_finite((float)$amount_input) ||
    (float)$amount_input <= 0 ||
    (float)$amount_input > 99999999.99 ||
    strlen($description) > 255 ||
    $admin_id <= 0
) {
    $_SESSION['error'] = 'Please enter valid expense details.';
    header('Location: expense_form.php');
    exit;
}

$amount = (float)$amount_input;

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO tbl_expenses
        (admin_id, expense_type, expense_date, amount, description)
     VALUES (?, ?, ?, ?, ?)"
);

mysqli_stmt_bind_param(
    $stmt,
    "issds",
    $admin_id,
    $expense_type,
    $expense_date,
    $amount,
    $description
);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = 'Expense added successfully.';
} else {
    $_SESSION['error'] = 'Failed to add expense. Please try again.';
}

mysqli_stmt_close($stmt);

header('Location: expenses.php');
exit;
