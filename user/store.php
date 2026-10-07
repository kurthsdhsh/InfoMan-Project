<?php
session_start();
include("../includes/config.php");

$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = trim($_POST['email']);
$contact_number = trim($_POST['contact_number']);
$password = trim($_POST['password']);
$confirmPass = trim($_POST['confirmPass']);

//validation
if ($first_name == '' || $last_name == '') {
    $_SESSION['message'] = 'first name and last name are required';
    header("Location: register.php");
    exit();
} else if (!preg_match("/^\w+@\w+\.\w+/", $email)) {
    $_SESSION['message'] = 'email invalid format';
    header("Location: register.php");
    exit();
} else if ($contact_number == '') {
    $_SESSION['message'] = 'contact number is required';
    header("Location: register.php");
    exit();
} else if (!(strlen($password) >= 6)) {
    $_SESSION['message'] = 'password should be at least 6 characters';
    header("Location: register.php");
    exit();
} else if ($password !== $confirmPass) {
    $_SESSION['message'] = 'passwords do not match';
    header("Location: register.php");
    exit();
}

try {
    $password = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO tbl_customers (first_name, last_name, email, password_hash, contact_number, account_status) VALUES(?,?,?,?,?,'Active')";
    $stmt1 = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt1, 'sssss', $first_name, $last_name, $email, $password, $contact_number);
    mysqli_stmt_execute($stmt1);

    $_SESSION['success'] = 'Account created. You can now sign in.';
    header("Location: login.php");
    exit();
} catch (mysqli_sql_exception $e) {
    echo $e->getMessage();
}