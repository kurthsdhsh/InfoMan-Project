<?php
session_start();
include("../includes/config.php");
include("../includes/upload_helper.php");

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

$errors = [];

if ($first_name === '') {
    $errors['first_name'] = 'First name is required.';
} elseif (strlen($first_name) > 50) {
    $errors['first_name'] = 'First name is too long (50 letters max).';
}

if ($last_name === '') {
    $errors['last_name'] = 'Last name is required.';
} elseif (strlen($last_name) > 50) {
    $errors['last_name'] = 'Last name is too long (50 letters max).';
}

if ($email === '') {
    $errors['email'] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Email format is not valid.';
} else {
    // email already used?
    $check = mysqli_execute_query($conn, "SELECT customer_id FROM tbl_customers WHERE email = ? LIMIT 1", [$email]);
    if (mysqli_num_rows($check) > 0) {
        $errors['email'] = 'That email is already registered.';
    }
}

if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $contact_number)) {
    $errors['contact_number'] = 'Contact number should look like 09123456789.';
}

if (strlen($password) < 6) {
    $errors['password'] = 'Password should be at least 6 characters.';
}

if ($password !== $confirmPass) {
    $errors['confirmPass'] = 'Passwords do not match.';
}

$photo = '';
if (empty($errors)) {
    $photo = saveImage($_FILES['photo'] ?? [], 'uploads/profiles', $photoError);
    if ($photoError) {
        $errors['photo'] = $photoError;
    }
}

// something is wrong: go back and show the messages + what was typed (never the password)
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = [
        'first_name'     => $first_name,
        'last_name'      => $last_name,
        'email'          => $email,
        'contact_number' => $contact_number,
    ];
    header("Location: register.php");
    exit();
}

try {
    $password = password_hash($password, PASSWORD_BCRYPT);
    $photoValue = ($photo !== '') ? $photo : null;

    $sql = "INSERT INTO tbl_customers (first_name, last_name, email, password_hash, contact_number, profile_photo, account_status) VALUES(?,?,?,?,?,?,'Active')";
    $stmt1 = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt1, 'ssssss', $first_name, $last_name, $email, $password, $contact_number, $photoValue);
    mysqli_stmt_execute($stmt1);

    $_SESSION['success'] = 'Account created. You can now sign in.';
    header("Location: login.php");
    exit();
} catch (mysqli_sql_exception $e) {
    if ($photo !== '') { @unlink(__DIR__ . '/../' . $photo); }
    $_SESSION['message'] = 'could not register, please try again';
    header("Location: register.php");
    exit();
}