<?php
session_start();

include('../../includes/config.php');

if (isset($_GET['id'])) {

    $category_id = $_GET['id'];

    // check if has products
    $sql = "SELECT COUNT(*) AS product_count
            FROM tbl_products
            WHERE category_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    // block deletion if it has products
    if ($row['product_count'] > 0) {

        $_SESSION['error'] = "Cannot delete this category because it still has products.";
        header("Location: categories.php");
        exit;
    }

    // delete
    $sql = "DELETE FROM tbl_categories
            WHERE category_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $category_id);

    if (mysqli_stmt_execute($stmt)) {

        $_SESSION['success'] = "Category deleted successfully.";

    } else {

        $_SESSION['error'] = "Failed to delete category.";
    }

    mysqli_stmt_close($stmt);

    header("Location: categories.php");
    exit;
}
?>