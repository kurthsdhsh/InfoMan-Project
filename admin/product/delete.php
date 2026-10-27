<?php
mysqli_report(MYSQLI_REPORT_OFF);

include('../auth.php');
include('../../includes/config.php');

if (isset($_GET['id'])) {

    $product_id = filter_var(
        $_GET['id'],
        FILTER_VALIDATE_INT
    );

    if (!$product_id || $product_id < 1) {
        $_SESSION['error'] = "Invalid product ID.";
        header("Location: products.php");
        exit;
    }

    $sql = "SELECT image_path
            FROM tbl_products
            WHERE product_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$row) {
        $_SESSION['error'] = "Product not found.";
        header("Location: products.php");
        exit;
    }

    $sql = "SELECT movement_id
            FROM tbl_stock_movements
            WHERE product_id = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $has_movements = mysqli_num_rows($result) > 0;

    mysqli_stmt_close($stmt);

    if ($has_movements) {
        $_SESSION['error'] =
            "This product has stock history and cannot be deleted. "
            . "You can set its status to Inactive instead.";

        header("Location: products.php");
        exit;
    }

    $sql = "SELECT 1
            FROM tbl_order_items
            WHERE product_id = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        $_SESSION['error'] =
            "Could not verify the product's order records. "
            . "Please check the order-items table name.";

        header("Location: products.php");
        exit;
    }

    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $has_orders = mysqli_num_rows($result) > 0;

    mysqli_stmt_close($stmt);

    if ($has_orders) {
        $_SESSION['error'] =
            "This product is linked to an order and cannot be deleted. "
            . "You can set its status to Inactive instead.";

        header("Location: products.php");
        exit;
    }

    $gallery_paths = [];

    $stmt = mysqli_prepare($conn, "SELECT image_path FROM tbl_product_images WHERE product_id = ?");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($photo = mysqli_fetch_assoc($result)) {
            $gallery_paths[] = $photo['image_path'];
        }

        mysqli_stmt_close($stmt);
    }

    $sql = "DELETE FROM tbl_products
            WHERE product_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        if (!empty($row['image_path'])) {

            $image_file = '../../' . $row['image_path'];

            if (file_exists($image_file)) {
                unlink($image_file);
            }
        }

        foreach ($gallery_paths as $path) {

            $photo_file = '../../' . $path;

            if (file_exists($photo_file)) {
                unlink($photo_file);
            }
        }

        $_SESSION['success'] = "Product deleted successfully.";

    } else {

        mysqli_stmt_close($stmt);

        $_SESSION['error'] =
            "Failed to delete product. It may be referenced by other records.";
    }

    header("Location: products.php");
    exit;
}

header("Location: products.php");
exit;
?>