<?php
session_start();

mysqli_report(MYSQLI_REPORT_OFF);

include('../../includes/config.php');

if (isset($_GET['id'])) {

    $product_id = $_GET['id'];


    // Get the image path before deleting the product
    $sql = "SELECT image_path
            FROM tbl_products
            WHERE product_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $product_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    // Check if product exists
    if (!$row) {

        $_SESSION['error'] = "Product not found.";
        header("Location: products.php");
        exit;
    }


    // Delete product
    $sql = "DELETE FROM tbl_products
            WHERE product_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $product_id
    );


    if (mysqli_stmt_execute($stmt)) {

        // Delete product image from uploads folder
        if (!empty($row['image_path'])) {

            $image_file = '../../' . $row['image_path'];

            if (file_exists($image_file)) {
                unlink($image_file);
            }
        }

        $_SESSION['success'] = "Product deleted successfully.";

    } else {

        $_SESSION['error'] = "Failed to delete product.";
    }


    mysqli_stmt_close($stmt);

    header("Location: products.php");
    exit;
}
?>