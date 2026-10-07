<?php
// store.php: handles the admin register form and inserts into tbl_admins.
// No HTML output here (no header.php) so header("Location: ...") always works.
session_start();
include("../includes/config.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: registeradmin.php");
    exit();
}

$admin_name  = trim($_POST['admin_name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$password    = trim($_POST['password'] ?? '');
$confirmPass = trim($_POST['confirmPass'] ?? '');

// ---------- validation ----------
$error = null;

if ($admin_name === '') {
    $error = 'admin name is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'email invalid format';
} elseif (strlen($password) < 6) {
    $error = 'password should be at least 6 characters';
} elseif ($password !== $confirmPass) {
    $error = 'passwords do not match';
}

if ($error) {
    $_SESSION['message'] = $error;
    header("Location: registeradmin.php");
    exit();
}

// ---------- insert ----------
try {
    // email already used?
    $check = mysqli_execute_query($conn, "SELECT admin_id FROM tbl_admins WHERE email = ? LIMIT 1", [$email]);
    if ($check->num_rows > 0) {
        $_SESSION['message'] = 'that email is already registered';
        header("Location: registeradmin.php");
        exit();
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $sql  = "INSERT INTO tbl_admins (admin_name, email, password_hash, account_status)
             VALUES (?, ?, ?, 'Active')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sss', $admin_name, $email, $password_hash);
    mysqli_stmt_execute($stmt);

    $_SESSION['success'] = 'Account created. You can now sign in.';
    header("Location: adminlogin.php");
    exit();
} catch (mysqli_sql_exception $e) {
    $_SESSION['message'] = 'something went wrong, please try again';
    // error_log($e->getMessage());  // uncomment to log the real reason
    header("Location: registeradmin.php");
    exit();
}



// 



// $result = mysqli_query($conn, $sql);
// if ($result) {
//     $_SESSION['userId'] = mysqli_insert_id($conn);
//     header("Location: profile.php");
// }