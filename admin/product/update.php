<?php
mysqli_report(MYSQLI_REPORT_OFF);

include('../auth.php');
include('../../includes/config.php');
include('../../includes/product_validation.php');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: products.php");
    exit;
}

$product_id = productInput('product_id');

if (!ctype_digit($product_id) || (int)$product_id < 1) {
    $_SESSION['error'] = "Invalid product ID.";
    header("Location: products.php");
    exit;
}

$product_id = (int)$product_id;

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

$category_id = productInput('category_id');
$product_name = productInput('product_name');
$product_description = productInput('product_description');
$unit_price = productInput('unit_price');
$product_status = productInput('product_status');
$imageFile = $_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE];
$photos = productPhotos();


$existing = getGallery($conn, $product_id);
$existingIds = array_map('intval', array_column($existing, 'image_id'));

$removeIds = [];
$posted = $_POST['remove_photos'] ?? [];

if (is_array($posted)) {
    foreach ($posted as $id) {
        if (is_string($id) && ctype_digit($id) && in_array((int)$id, $existingIds, true)) {
            $removeIds[] = (int)$id;
        }
    }
}

$removeIds = array_unique($removeIds);

$remaining = count($existing) - count($removeIds);

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

$errors = array_merge($errors, validatePhotos($photos, $remaining));

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old'] = $old;
    header("Location: product_form.php?id=" . $product_id);
    exit;
}

$image_path = $row['image_path'];

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
        header("Location: product_form.php?id=" . $product_id);
        exit;
    }
}

$category = (int)$category_id;
$price = (float)$unit_price;

$sql = "UPDATE tbl_products
        SET
            category_id = ?,
            product_name = ?,
            product_description = ?,
            unit_price = ?,
            image_path = ?,
            product_status = ?
        WHERE product_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "issdssi",
    $category,
    $product_name,
    $product_description,
    $price,
    $image_path,
    $product_status,
    $product_id
);

if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    if (isset($new_image_name) && !empty($row['image_path'])) {

        $old_image = '../../' . $row['image_path'];

        if (file_exists($old_image)) {
            unlink($old_image);
        }
    }

    removePhotos($conn, $product_id, $removeIds);
    $saved = savePhotos($conn, $product_id, $photos);

    $_SESSION['success'] = "Product updated successfully.";

    if ($saved < count($photos)) {
        $_SESSION['success'] .= " Some additional photos could not be saved.";
    }

    header("Location: products.php");
    exit;

} else {

    mysqli_stmt_close($stmt);

    if (isset($new_image_name) && file_exists($upload_path)) {
        unlink($upload_path);
    }

    $_SESSION['error'] = "Failed to update product.";
    $_SESSION['form_old'] = $old;
    header("Location: product_form.php?id=" . $product_id);
    exit;
}