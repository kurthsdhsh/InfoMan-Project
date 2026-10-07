<?php
session_start();
include('../includes/config.php');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$product_id = (int)basename($path);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $nameInput = trim($_POST['product_name'] ?? '');
    $desc = trim($_POST['product_description'] ?? '');
    $categoryInput = trim($_POST['category_id'] ?? '');
    $priceInput = trim($_POST['unit_price'] ?? '');
    $qtyInput = trim($_POST['stock_quantity'] ?? '');
    $hasErrors = false;

    if ($nameInput === '') {
        $_SESSION['nameError'] = 'Please input a Product Name';
        $hasErrors = true;
    }
    if ($desc === '') {
        $_SESSION['descError'] = 'Please input a Product description';
        $hasErrors = true;
    }
    if ($priceInput === '' || !is_numeric($priceInput)) {
        $_SESSION['priceError'] = 'Please enter a valid unit price';
        $hasErrors = true;
    }
    if ($qtyInput === '' || filter_var($qtyInput, FILTER_VALIDATE_INT) === false || (int)$qtyInput < 0) {
        $_SESSION['qtyError'] = 'Please enter a valid quantity';
        $hasErrors = true;
    }

    if ($hasErrors) {
        $_SESSION['name'] = $nameInput;
        $_SESSION['desc'] = $desc;
        $_SESSION['category'] = $categoryInput;
        $_SESSION['price'] = $priceInput;
        $_SESSION['qty'] = $qtyInput;
        header("Location: edit.php?id={$product_id}");
        exit;
    }

    $price = (float)$priceInput;
    $qty = (int)$qtyInput;
    $category = $categoryInput !== '' ? (int)$categoryInput : null;

    // only replace the image when a new file was chosen
    $target = '';
    if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] !== UPLOAD_ERR_NO_FILE) {
        $type = strtolower($_FILES['image_path']['type']);
        if ($type == "image/jpeg" || $type == "image/jpg" || $type == "image/png") {
            $source = $_FILES['image_path']['tmp_name'];
            $target = 'images/' . $_FILES['image_path']['name'];
            move_uploaded_file($source, $target) or die("Couldn't copy");
        } else {
            $_SESSION['imageError'] = "wrong file type";
            header("Location: edit.php?id={$product_id}");
            exit;
        }
    }

    try {
        if ($target !== '') {
            $sql = "UPDATE tbl_products
                    SET category_id = ?, product_name = ?, product_description = ?, unit_price = ?, stock_quantity = ?, image_path = ?
                    WHERE product_id = ?";
            mysqli_execute_query($conn, $sql, [$category, $nameInput, $desc, $price, $qty, $target, $product_id]);
        } else {
            $sql = "UPDATE tbl_products
                    SET category_id = ?, product_name = ?, product_description = ?, unit_price = ?, stock_quantity = ?
                    WHERE product_id = ?";
            mysqli_execute_query($conn, $sql, [$category, $nameInput, $desc, $price, $qty, $product_id]);
        }
    } catch (mysqli_sql_exception $e) {
        die($e->getMessage());
    }

    // clear only the form values (not the whole session, that would log the admin out)
    unset($_SESSION['name'], $_SESSION['desc'], $_SESSION['category'], $_SESSION['price'], $_SESSION['qty']);
    header("Location: /InfoMan-Project/admin_item/index.php");
    // header("Location: index.php");

    exit;
}