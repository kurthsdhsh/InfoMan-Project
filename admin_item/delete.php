<?php
session_start();
include('../includes/config.php');
// $id = $_GET['id']
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id) {
    header('Location: /InfoMan-Project/admin_item/index.php');
    exit;
}

mysqli_begin_transaction($conn);

try {
    $stockStmt = mysqli_prepare($conn, 'DELETE FROM tbl_stock_movements WHERE product_id = ?');
    mysqli_stmt_bind_param($stockStmt, 'i', $product_id);
    mysqli_stmt_execute($stockStmt);

    $itemStmt = mysqli_prepare($conn, 'DELETE FROM tbl_products WHERE product_id = ?');
    mysqli_stmt_bind_param($itemStmt, 'i', $product_id);
    mysqli_stmt_execute($itemStmt);

    mysqli_commit($conn);
} catch (Throwable $error) {
    mysqli_rollback($conn);
    die($error->getMessage());
}

header('Location: /InfoMan-Project/admin_item/index.php');
exit;