<?php
require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/expense_validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: expenses.php');
    exit;
}

$expense_type = expenseInput('expense_type');
$other_type = expenseInput('other_type');
$expense_date = expenseInput('expense_date');
$amount_input = expenseInput('amount');
$description = expenseInput('description');
$admin_id = (int)($_SESSION['admin_id'] ?? 0);

if ($admin_id <= 0) {
    $_SESSION['error'] = 'Unable to identify the logged-in admin.';
    header('Location: expenses.php');
    exit;
}

$old = [
    'expense_type' => $expense_type,
    'other_type' => $other_type,
    'expense_date' => $expense_date,
    'amount' => $amount_input,
    'description' => $description
];

$errors = validateExpense($expense_type, $other_type, $expense_date, $amount_input, $description);

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old'] = $old;
    header('Location: expense_form.php');
    exit;
}

if ($expense_type === 'Other') {
    $expense_type = $other_type;
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