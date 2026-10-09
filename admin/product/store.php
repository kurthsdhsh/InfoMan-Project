
<?php
mysqli_report(MYSQLI_REPORT_OFF);

include('../auth.php');
include('../../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $category_id = $_POST['category_id'] ?? '';
    $product_name = trim($_POST['product_name'] ?? '');
    $product_description = trim($_POST['product_description'] ?? '');
    $unit_price = $_POST['unit_price'] ?? '';
    $product_status = $_POST['product_status'] ?? 'Active';

    // Check required fields
    // Stock quantity is no longer required from the form.
    if (
        $category_id == '' ||
        $product_name == '' ||
        $unit_price == ''
    ) {
        $_SESSION['error'] = "Please complete all required fields.";
        header("Location: product_form.php");
        exit;
    }

    // Handle image upload
    $image_path = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

        $image_name = $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];

        $image_extension = strtolower(
            pathinfo($image_name, PATHINFO_EXTENSION)
        );

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($image_extension, $allowed_extensions)) {

            $_SESSION['error'] = "Invalid image type.";
            header("Location: product_form.php");
            exit;
        }

        // Create a unique file name
        $new_image_name = uniqid('product_') . '.' . $image_extension;

        $upload_path = '../../uploads/products/' . $new_image_name;

        if (move_uploaded_file($image_tmp, $upload_path)) {

            $image_path = 'uploads/products/' . $new_image_name;

        } else {

            $_SESSION['error'] = "Failed to upload product image.";
            header("Location: product_form.php");
            exit;
        }
    }

    // Insert product with stock quantity fixed at 0
    $sql = "INSERT INTO tbl_products
            (
                category_id,
                product_name,
                product_description,
                unit_price,
                stock_quantity,
                image_path,
                product_status
            )
            VALUES (?, ?, ?, ?, 0, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "issdss",
        $category_id,
        $product_name,
        $product_description,
        $unit_price,
        $image_path,
        $product_status
    );

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        $_SESSION['success'] = "Product added successfully with 0 stock. Use Inventory Management to add stock.";
        header("Location: products.php");
        exit;

    } else {

        mysqli_stmt_close($stmt);

        $_SESSION['error'] = "Failed to add product.";
        header("Location: product_form.php");
        exit;
    }
}
?>
