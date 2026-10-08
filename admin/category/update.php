<?php
session_start();

include('../../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $category_id = $_POST['category_id'] ?? '';
    $category_name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // check if id is valid
    if ($category_id == '') {

        $_SESSION['error'] = "Invalid category.";
        header("Location: categories.php");
        exit;
    }

    // check if name is empty
    if ($category_name == '') {

        $_SESSION['error'] = "Category name is required.";
        header("Location: category_form.php?id=" . $category_id);
        exit;
    }

    // update category
    $sql = "UPDATE tbl_categories
            SET category_name = ?, description = ?
            WHERE category_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ssi",
        $category_name,
        $description,
        $category_id
    );

    if (mysqli_stmt_execute($stmt)) {

        $_SESSION['success'] = "Category updated successfully.";
        header("Location: categories.php");
        exit;

    } else {

        // if name already exists or na-dup
        if (mysqli_stmt_errno($stmt) == 1062) {

            $_SESSION['error'] = "Category name already exists.";

        } else {

            $_SESSION['error'] = "Failed to update category.";
        }

        header("Location: category_form.php?id=" . $category_id);
        exit;
    }

    mysqli_stmt_close($stmt);
}
?>