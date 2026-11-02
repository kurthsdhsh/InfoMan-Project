<?php
mysqli_report(MYSQLI_REPORT_OFF);

include('../auth.php');
include('../../includes/config.php');
include('../../includes/category_validation.php');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: categories.php");
    exit;
}

$category_name = trim($_POST['category_name'] ?? '');
$description = trim($_POST['description'] ?? '');

$old = [
    'category_name' => $category_name,
    'description' => $description
];

$errors = validateCategory($category_name, $description);

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old'] = $old;
    header("Location: category_form.php");
    exit;
}

$sql = "INSERT INTO tbl_categories (category_name, description)
        VALUES (?, ?)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $category_name, $description);

if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);
    $_SESSION['success'] = "Category added successfully.";
    header("Location: categories.php");
    exit;
}

// name already exists
if (mysqli_stmt_errno($stmt) == 1062) {
    $_SESSION['form_errors'] = ['name' => 'Category name already exists.'];
} else {
    $_SESSION['error'] = "Failed to add category.";
}

mysqli_stmt_close($stmt);
$_SESSION['form_old'] = $old;
header("Location: category_form.php");
exit;