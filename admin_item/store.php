<?php
session_start();
include('../includes/config.php');
$price = (float)($_POST['unit_price'] ?? 0);
$name = trim($_POST['product_name'] ?? '');
$desc = trim($_POST['product_description'] ?? '');
$qty = (int)($_POST['stock_quantity'] ?? 0);
$category = ($_POST['category_id'] ?? '') !== '' ? (int)$_POST['category_id'] : null;

// keep what was typed so create.php can fill the form again after an error
$_SESSION['name'] = $_POST['product_name'] ?? '';
$_SESSION['desc'] = $_POST['product_description'] ?? '';
$_SESSION['category'] = $_POST['category_id'] ?? '';
$_SESSION['price'] = $_POST['unit_price'] ?? '';
$_SESSION['qty'] = $_POST['stock_quantity'] ?? '';

// var_dump($_POST['submit']);
if (empty($_POST['product_name'])) {
    $_SESSION['nameError'] = 'Please input a Product Name';

    header("Location: create.php");
    exit();
}
if (empty($_POST['product_description'])) {
    $_SESSION['descError'] = 'Please input a Product Description';

    header("Location: create.php");
    exit();
}
if (empty($_POST['unit_price']) || (! is_numeric($_POST['unit_price']))) {
    $_SESSION['priceError'] = 'error product price format';
    header("Location: create.php");
    exit();
}

// a file input is in $_FILES, not $_POST
if (empty($_FILES['image_path']['name'])) {
    $_SESSION['imageError'] = 'Please select an image';

    header("Location: create.php");
    exit();
}

if (isset($_POST['submit'])) {
    $target = '';

    if (isset($_FILES['image_path'])) {
        // var_dump($_FILES);
        // exit();
        $type = strtolower($_FILES['image_path']['type']);
        if ($type == "image/jpeg" || $type == "image/jpg" || $type == "image/png") {
            $source = $_FILES['image_path']['tmp_name'];
            $target = 'images/' . $_FILES['image_path']['name'];
            move_uploaded_file($source, $target) or die("Couldn't copy");
        } else {
            $_SESSION['imageError'] = "wrong file type";
            header("Location: create.php");
            exit();
        }
    }

    $sql = "INSERT INTO tbl_products (category_id, product_name, product_description, unit_price, stock_quantity, image_path, product_status)
            VALUES (?, ?, ?, ?, ?, ?, 'Active')";

    $result = mysqli_execute_query($conn, $sql, [$category, $name, $desc, $price, $qty, $target]);

    // clear only the form values (not the whole session, that would log the admin out)
    unset($_SESSION['name'], $_SESSION['desc'], $_SESSION['category'], $_SESSION['price'], $_SESSION['qty']);
    header("Location: index.php");
    exit();
}