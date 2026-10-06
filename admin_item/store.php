<?php
session_start();
include('../includes/config.php');
$cost =  (float)$_POST['unit_price'];
$name = trim($_POST['product_name']);
$desc = trim($_POST['product_description']);
$qty = (int)$_POST['stock_quantity'];


// var_dump($_POST['submit']);
if (empty($_POST['product_name'])) {
    $_SESSION['descError'] = 'Please input a Product Name';

    header("Location: create.php");
}
if (empty($_POST['product_description'])) {
    $_SESSION['descError'] = 'Please input a Product Description';

    header("Location: create.php");
}
if (empty($_POST['unit_price']) || (! is_numeric($_POST['unit_price']))) {
    $_SESSION['costError'] = 'error product price format';
    header("Location: create.php");
}

if (empty($_POST['image_path'])) {
    $_SESSION['imageError'] = 'Please select an image';

    header("Location: create.php");
}

if (isset($_POST['submit'])) {
    // $target = '';
    $_SESSION['cost'] = $_POST['unit_price'];
    $_SESSION['name'] = $_POST['product_name'];
    $_SESSION['desc'] = $_POST['product_name'];
    $_SESSION['qty'] = $_POST['stock_quantity'];

    if (isset($_FILES['image_path'])) {
        var_dump($_FILES);
        // exit();
        if ($_FILES['image_path']['type'] == "image/jpeg" || $_FILES['img_path']['type'] == "image/jpg" || $_FILES['img_path']['type'] == "image/PNG") {
            $source = $_FILES['image_path']['tmp_name'];
            $target = 'images/' . $_FILES['image_path']['name'];
            move_uploaded_file($source, $target) or die("Couldn't copy");
        } else {
            $_SESSION['imageError'] = "wrong file type";
            header("Location: create.php");
        }
    }

    $sql = "INSERT INTO tbl_products (proudct_name, product_description, unit_price, stock_quantity, img_path) VALUES('{$name}','{$desc}', {$cost}, {$qty}, '{$target}')";

    $result = mysqli_query($conn, $sql);

    $_SESSION = array();
    header("Location: index.php");
}