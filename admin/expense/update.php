<?php
require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/expense_validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: expenses.php');
    exit;
}

$expense_id = filter_input(INPUT_POST, 'expense_id', FILTER_VALIDATE_INT);

if (!$expense_id || $expense_id <= 0) {
    $_SESSION['error'] = 'Invalid expense record.';
    header('Location: expenses.php');
    exit;
}

$expense_type = expenseInput('expense_type');
$other_type = expenseInput('other_type');
$expense_date = expenseInput('expense_date');
$amount_input = expenseInput('amount');
$description = expenseInput('description');

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
    header('Location: expense_form.php?expense_id=' . $expense_id);
    exit;
}

if ($expense_type === 'Other') {
    $expense_type = $other_type;
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