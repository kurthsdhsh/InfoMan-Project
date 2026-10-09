<?php
require_once '../auth.php';
require_once '../../includes/config.php';

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

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM tbl_expenses WHERE expense_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $expense_id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) === 1) {
        $_SESSION['success'] = 'Expense deleted successfully.';
    } else {
        $_SESSION['error'] = 'Expense record not found.';
    }
} else {
    $_SESSION['error'] = 'Failed to delete expense. Please try again.';
}

mysqli_stmt_close($stmt);

header('Location: expenses.php');
exit;
