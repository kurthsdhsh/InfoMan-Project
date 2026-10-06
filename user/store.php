<?php
// store.php: handles the register form and inserts into tbl_customers.
// No HTML output here (no header.php) so header("Location: ...") always works.
session_start();
include("../includes/config.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit();
}

$first_name     = trim($_POST['first_name'] ?? '');
$last_name      = trim($_POST['last_name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$password       = trim($_POST['password'] ?? '');
$confirmPass    = trim($_POST['confirmPass'] ?? '');

// ---------- validation ----------
$error = null;

if ($first_name === '' || $last_name === '') {
    $error = 'first name and last name are required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'email invalid format';
} elseif (!preg_match('/^(09\d{9}|\+639\d{9})$/', $contact_number)) {
    // PH mobile format: 09XXXXXXXXX or +639XXXXXXXXX. Adjust if you accept other formats.
    $error = 'contact number should look like 09123456789';
} elseif (strlen($password) < 6) {
    $error = 'password should be at least 6 characters';
} elseif ($password !== $confirmPass) {
    $error = 'passwords do not match';
}

if ($error) {
    $_SESSION['message'] = $error;
    header("Location: register.php");
    exit();
}

// ---------- insert ----------
try {
    // email already used?
    $check = mysqli_execute_query($conn, "SELECT customer_id FROM tbl_customers WHERE email = ? LIMIT 1", [$email]);
    if ($check->num_rows > 0) {
        $_SESSION['message'] = 'that email is already registered';
        header("Location: register.php");
        exit();
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $sql  = "INSERT INTO tbl_customers (first_name, last_name, email, password_hash, contact_number, account_status)
             VALUES (?, ?, ?, ?, ?, 'active')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssss', $first_name, $last_name, $email, $password_hash, $contact_number);
    mysqli_stmt_execute($stmt);

    $_SESSION['success'] = 'Account created. You can now sign in.';
    header("Location: login.php");
    exit();
} catch (mysqli_sql_exception $e) {
    $_SESSION['message'] = 'something went wrong, please try again';
    // error_log($e->getMessage());  // uncomment to log the real reason
    header("Location: register.php");
    exit();
}



// 



// $result = mysqli_query($conn, $sql);
// if ($result) {
//     $_SESSION['userId'] = mysqli_insert_id($conn);
//     header("Location: profile.php");
// }