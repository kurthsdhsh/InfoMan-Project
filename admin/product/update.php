<?php
session_start();

mysqli_report(MYSQLI_REPORT_OFF);

include('../../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $product_id = $_POST['product_id'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $product_name = trim($_POST['product_name'] ?? '');
    $product_description = trim($_POST['product_description'] ?? '');
    $unit_price = $_POST['unit_price'] ?? '';
    $stock_quantity = $_POST['stock_quantity'] ?? '';
    $product_status = $_POST['product_status'] ?? 'Active';


    // Check required fields
    if (
        $product_id == '' ||
        $category_id == '' ||
        $product_name == '' ||
        $unit_price == '' ||
        $stock_quantity == ''
    ) {

        $_SESSION['error'] = "Please complete all required fields.";
        header("Location: product_form.php?id=" . $product_id);
        exit;
    }


    // Get current image
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


    $image_path = $row['image_path'];


    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

        $image_name = $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];

        $image_extension = strtolower(
            pathinfo($image_name, PATHINFO_EXTENSION)
        );

        $allowed_extensions = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp'
        ];


        if (!in_array($image_extension, $allowed_extensions)) {

            $_SESSION['error'] = "Invalid image type.";
            header("Location: product_form.php?id=" . $product_id);
            exit;
        }


        // Create a new unique file name
        $new_image_name = uniqid('product_') . '.' . $image_extension;

        $upload_path = '../../uploads/products/' . $new_image_name;


        if (move_uploaded_file($image_tmp, $upload_path)) {

            $image_path = 'uploads/products/' . $new_image_name;

            // Delete old image
            if (!empty($row['image_path'])) {

                $old_image = '../../' . $row['image_path'];

                if (file_exists($old_image)) {
                    unlink($old_image);
                }
            }

        } else {

            $_SESSION['error'] = "Failed to upload product image.";
            header("Location: product_form.php?id=" . $product_id);
            exit;
        }
    }


    // Update product
    $sql = "UPDATE tbl_products
            SET
                category_id = ?,
                product_name = ?,
                product_description = ?,
                unit_price = ?,
                stock_quantity = ?,
                image_path = ?,
                product_status = ?
            WHERE product_id = ?";


    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "issdissi",
        $category_id,
        $product_name,
        $product_description,
        $unit_price,
        $stock_quantity,
        $image_path,
        $product_status,
        $product_id
    );


    if (mysqli_stmt_execute($stmt)) {

        $_SESSION['success'] = "Product updated successfully.";
        header("Location: products.php");
        exit;

    } else {

        $_SESSION['error'] = "Failed to update product.";
        header("Location: product_form.php?id=" . $product_id);
        exit;
    }

    mysqli_stmt_close($stmt);
}
?>