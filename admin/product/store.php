<?php
mysqli_report(MYSQLI_REPORT_OFF);

include('../auth.php');
include('../../includes/config.php');
include('../../includes/product_validation.php');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: products.php");
    exit;
}

$category_id = productInput('category_id');
$product_name = productInput('product_name');
$product_description = productInput('product_description');
$unit_price = productInput('unit_price');
$product_status = productInput('product_status');
$imageFile = $_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE];
$photos = productPhotos();

$old = [
    'category_id' => $category_id,
    'product_name' => $product_name,
    'product_description' => $product_description,
    'unit_price' => $unit_price,
    'product_status' => $product_status
];

$errors = validateProduct(
    $conn,
    $category_id,
    $product_name,
    $product_description,
    $unit_price,
    $product_status,
    $imageFile
);

$errors = array_merge($errors, validatePhotos($photos, 0));

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old'] = $old;
    header("Location: product_form.php");
    exit;
}

$image_path = '';

if ($imageFile['error'] == UPLOAD_ERR_OK) {

    $image_extension = strtolower(
        pathinfo($imageFile['name'], PATHINFO_EXTENSION)
    );

    $new_image_name = uniqid('product_') . '.' . $image_extension;
    $upload_path = '../../uploads/products/' . $new_image_name;

    if (move_uploaded_file($imageFile['tmp_name'], $upload_path)) {

        $image_path = 'uploads/products/' . $new_image_name;

    } else {

        $_SESSION['error'] = "Failed to upload product image.";
        $_SESSION['form_old'] = $old;
        header("Location: product_form.php");
        exit;
    }
}

$category = (int)$category_id;
$price = (float)$unit_price;

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
    $category,
    $product_name,
    $product_description,
    $price,
    $image_path,
    $product_status
);

if (mysqli_stmt_execute($stmt)) {

    $new_product_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    $saved = savePhotos($conn, $new_product_id, $photos);

    $_SESSION['success'] = "Product added successfully with 0 stock. Use Inventory Management to add stock.";

    if ($saved < count($photos)) {
        $_SESSION['success'] .= " Some additional photos could not be saved.";
    }

    header("Location: products.php");
    exit;

} else {

    mysqli_stmt_close($stmt);

    if ($image_path != '' && file_exists($upload_path)) {
        unlink($upload_path);
    }

    $_SESSION['error'] = "Failed to add product.";
    $_SESSION['form_old'] = $old;
    header("Location: product_form.php");
    exit;
}