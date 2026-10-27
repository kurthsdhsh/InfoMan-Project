<?php

const MAX_GALLERY_PHOTOS = 5;

function productInput($key)
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function checkImageFile($file)
{
    if ($file['error'] == UPLOAD_ERR_INI_SIZE || $file['error'] == UPLOAD_ERR_FORM_SIZE) {
        return 'Image must not be larger than 2 MB.';
    }

    if ($file['error'] != UPLOAD_ERR_OK) {
        return 'There was a problem uploading the image. Please try again.';
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $allowed, true)) {
        return 'Only JPG, PNG, GIF or WEBP images are allowed.';
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return 'Image must not be larger than 2 MB.';
    }

    if (getimagesize($file['tmp_name']) === false) {
        return 'The chosen file is not a real image.';
    }

    return '';
}


function validateProduct($conn, $category_id, $product_name, $product_description, $unit_price, $product_status, $imageFile)
{
    $errors = [];

    if ($category_id === '') {
        $errors['category'] = 'Please select a category.';
    } elseif (!ctype_digit($category_id)) {
        $errors['category'] = 'Invalid category.';
    } else {
        $id = (int)$category_id;
        $stmt = mysqli_prepare($conn, "SELECT category_id FROM tbl_categories WHERE category_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 0) {
            $errors['category'] = 'The selected category does not exist.';
        }
        mysqli_stmt_close($stmt);
    }

    if ($product_name === '') {
        $errors['name'] = 'Please enter the product name.';
    } elseif (mb_strlen($product_name) < 2) {
        $errors['name'] = 'Product name must be at least 2 characters.';
    } elseif (mb_strlen($product_name) > 150) {
        $errors['name'] = 'Product name must not be more than 150 characters.';
    }

    if (mb_strlen($product_description) > 500) {
        $errors['description'] = 'Description must not be more than 500 characters.';
    }

    if ($unit_price === '') {
        $errors['price'] = 'Please enter the unit price.';
    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $unit_price)) {
        $errors['price'] = 'Unit price must be a valid amount, like 120 or 120.50.';
    } elseif ((float)$unit_price <= 0) {
        $errors['price'] = 'Unit price must be greater than 0.';
    } elseif ((float)$unit_price > 99999999.99) {
        $errors['price'] = 'Unit price is too large.';
    }

    if ($product_status !== 'Active' && $product_status !== 'Inactive') {
        $errors['status'] = 'Please choose a valid status.';
    }

    if ($imageFile['error'] != UPLOAD_ERR_NO_FILE) {
        $message = checkImageFile($imageFile);

        if ($message != '') {
            $errors['image'] = $message;
        }
    }

    return $errors;
}



function productPhotos()
{
    $photos = [];

    if (!isset($_FILES['photos']) || !is_array($_FILES['photos']['name'])) {
        return $photos;
    }

    for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {

        if ($_FILES['photos']['error'][$i] == UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $photos[] = [
            'name' => $_FILES['photos']['name'][$i],
            'tmp_name' => $_FILES['photos']['tmp_name'][$i],
            'size' => $_FILES['photos']['size'][$i],
            'error' => $_FILES['photos']['error'][$i]
        ];
    }

    return $photos;
}

function validatePhotos($photos, $existingCount)
{
    $errors = [];

    if ($existingCount + count($photos) > MAX_GALLERY_PHOTOS) {
        $errors['photos'] = 'A product can have at most ' . MAX_GALLERY_PHOTOS . ' additional photos.';
        return $errors;
    }

    foreach ($photos as $photo) {
        $message = checkImageFile($photo);

        if ($message != '') {
            $errors['photos'] = $photo['name'] . ': ' . $message;
            break;
        }
    }

    return $errors;
}

function getGallery($conn, $product_id)
{
    $gallery = [];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT image_id, image_path
         FROM tbl_product_images
         WHERE product_id = ?
         ORDER BY image_id ASC"
    );

    if (!$stmt) {
        return $gallery;
    }

    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $gallery[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $gallery;
}

function savePhotos($conn, $product_id, $photos)
{
    $saved = 0;

    foreach ($photos as $photo) {

        $extension = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
        $new_name = uniqid('photo_', true) . '.' . $extension;
        $target = __DIR__ . '/../uploads/products/' . $new_name;

        if (!move_uploaded_file($photo['tmp_name'], $target)) {
            continue;
        }

        $path = 'uploads/products/' . $new_name;

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO tbl_product_images (product_id, image_path) VALUES (?, ?)"
        );

        if (!$stmt) {
            unlink($target);
            continue;
        }

        mysqli_stmt_bind_param($stmt, "is", $product_id, $path);

        if (mysqli_stmt_execute($stmt)) {
            $saved++;
        } else {
            unlink($target);
        }

        mysqli_stmt_close($stmt);
    }

    return $saved;
}

function removePhotos($conn, $product_id, $imageIds)
{
    foreach ($imageIds as $image_id) {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT image_path FROM tbl_product_images WHERE image_id = ? AND product_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $image_id, $product_id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$row) {
            continue;
        }

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM tbl_product_images WHERE image_id = ? AND product_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $image_id, $product_id);
        $deleted = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($deleted) {
            $file = __DIR__ . '/../' . $row['image_path'];

            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}