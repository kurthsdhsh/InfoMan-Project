<?php
include('../auth.php');
$pageTitle = 'Category';
include('../../includes/adminSidebar.php');
include('../../includes/config.php');

$category_id = '';
$category_name = '';
$description = '';

$isEdit = false;

if (isset($_GET['id'])) {

    $category_id = (int)$_GET['id'];
    $isEdit = true;

    $sql = "SELECT * FROM tbl_categories WHERE category_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $row = mysqli_fetch_assoc($result);

        $category_name = $row['category_name'];
        $description = $row['description'];

    } else {

        $_SESSION['error'] = "Category not found.";
        header("Location: categories.php");
        exit;
    }

    mysqli_stmt_close($stmt);
}

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['form_old'] ?? [];
$error = $_SESSION['error'] ?? '';

unset($_SESSION['form_errors'], $_SESSION['form_old'], $_SESSION['error']);

if (!empty($old)) {
    $category_name = $old['category_name'];
    $description = $old['description'];
}

?>

<div class="admin-container">

    <div class="admin-toolbar">

        <h2>
            <?php
            if ($isEdit) {
                echo "Edit category";
            } else {
                echo "Add category";
            }
            ?>
        </h2>

    </div> 

    <?php if ($error != ''): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            Please correct the errors below.
        </div>
    <?php endif; ?>

    <div class="table-card" style="padding: 30px;">

    <form
        action="<?php echo $isEdit ? 'update.php' : 'store.php'; ?>"
        method="POST"
        novalidate>

            <?php if ($isEdit): ?>

                <input
                    type="hidden"
                    name="category_id"
                    value="<?php echo htmlspecialchars($category_id); ?>">

            <?php endif; ?>


            <div class="mb-3">

                <label for="category_name" class="form-label">
                    Category Name
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="category_name"
                    name="category_name"
                    value="<?php echo htmlspecialchars($category_name); ?>">

                <?php if (isset($errors['name'])): ?>
                    <small class="text-danger">
                        <?php echo htmlspecialchars($errors['name']); ?>
                    </small>
                <?php endif; ?>

            </div>


            <div class="mb-3">

                <label for="description" class="form-label">
                    Description
                </label>

                <textarea
                    class="form-control"
                    id="description"
                    name="description"
                    rows="4"><?php echo htmlspecialchars($description ?? ''); ?></textarea>

                <?php if (isset($errors['description'])): ?>
                    <small class="text-danger">
                        <?php echo htmlspecialchars($errors['description']); ?>
                    </small>
                <?php endif; ?>

            </div>


            <div class="d-flex gap-2">

                <button
                    type="submit"
                    class="btn-add"
                    style="border: none; cursor: pointer;">

                    <?php
                    echo $isEdit ? "Update category" : "Add category";
                    ?>

                </button>

                <a
                    href="categories.php"
                    class="btn btn-secondary">

                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>

</main>
</body>

</html>