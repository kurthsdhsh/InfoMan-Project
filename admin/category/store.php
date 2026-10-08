<?php
session_start();

mysqli_report(MYSQLI_REPORT_OFF);

include('../../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $category_name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // check if name is empty
    if ($category_name == '') {

        $_SESSION['error'] = "Category name is required.";
        header("Location: category_form.php");
        exit;
    }

    // insert
    $sql = "INSERT INTO tbl_categories (category_name, description)
            VALUES (?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $category_name,
        $description
    );
    
    if (mysqli_stmt_execute($stmt)) {

        $_SESSION['success'] = "Category added successfully.";
        header("Location: categories.php");
        exit;

    } else {

        // if name already exists or na-dup
        if (mysqli_stmt_errno($stmt) == 1062) {

            $_SESSION['error'] = "Category name already exists.";

        } else {

            $_SESSION['error'] = "Failed to add category.";
        }

        header("Location: categories.php");
        exit;
    }

    mysqli_stmt_close($stmt);
}
?>