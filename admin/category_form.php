<?php
session_start();
include('../includes/config.php');

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: adminlogin.php');
    exit;
}

$categoryId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
$isEdit = (bool) $categoryId;
$category = [
    'category_id' => null,
    'category_name' => '',
    'description' => '',
];

if ($isEdit) {
    $result = mysqli_execute_query(
        $conn,
        'SELECT category_id, category_name, description FROM tbl_categories WHERE category_id = ? LIMIT 1',
        [$categoryId]
    );
    $found = $result ? mysqli_fetch_assoc($result) : null;
    if (!$found) {
        $_SESSION['message'] = 'Category not found.';
        header('Location: categories.php');
        exit;
    }
    $category = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $isEdit = (bool) $postedId;
    $name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $hasErrors = false;

    $_SESSION['category_name'] = $name;
    $_SESSION['category_description'] = $description;

    if ($name === '') {
        $_SESSION['nameError'] = 'Please enter a category name.';
        $hasErrors = true;
    } elseif (mb_strlen($name) > 80) {
        $_SESSION['nameError'] = 'Category name must be 80 characters or less.';
        $hasErrors = true;
    }

    if (mb_strlen($description) > 255) {
        $_SESSION['descError'] = 'Description must be 255 characters or less.';
        $hasErrors = true;
    }

    if (!$hasErrors) {
        $duplicateSql = $isEdit
            ? 'SELECT category_id FROM tbl_categories WHERE category_name = ? AND category_id <> ? LIMIT 1'
            : 'SELECT category_id FROM tbl_categories WHERE category_name = ? LIMIT 1';
        $duplicateParams = $isEdit ? [$name, $postedId] : [$name];
        $duplicate = mysqli_execute_query($conn, $duplicateSql, $duplicateParams);

        if ($duplicate && mysqli_num_rows($duplicate) > 0) {
            $_SESSION['nameError'] = 'That category name is already in use.';
            $hasErrors = true;
        }
    }

    if ($hasErrors) {
        $redirect = $isEdit ? 'category_form.php?id=' . (int) $postedId : 'category_form.php';
        header('Location: ' . $redirect);
        exit;
    }

    $descriptionValue = $description === '' ? null : $description;

    if ($isEdit) {
        mysqli_execute_query(
            $conn,
            'UPDATE tbl_categories SET category_name = ?, description = ? WHERE category_id = ?',
            [$name, $descriptionValue, $postedId]
        );
        $_SESSION['success'] = 'Category updated.';
    } else {
        mysqli_execute_query(
            $conn,
            'INSERT INTO tbl_categories (category_name, description) VALUES (?, ?)',
            [$name, $descriptionValue]
        );
        $_SESSION['success'] = 'Category added.';
    }

    unset($_SESSION['category_name'], $_SESSION['category_description']);
    header('Location: categories.php');
    exit;
}

include('../includes/adminHeader.php');
echo '<link rel="stylesheet" href="../includes/style/adminstyle.css?v=' . time() . '">';

$nameValue = $_SESSION['category_name'] ?? $category['category_name'];
$descValue = $_SESSION['category_description'] ?? ($category['description'] ?? '');
unset($_SESSION['category_name'], $_SESSION['category_description']);
?>

<div class="container">
    <?php include('../includes/alert.php'); ?>
    <h2 class="my-4"><?= $isEdit ? 'Edit category' : 'Add category' ?></h2>
    <form method="POST" action="category_form.php<?= $isEdit ? '?id=' . (int) $category['category_id'] : '' ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="category_id" value="<?= (int) $category['category_id'] ?>">
        <?php endif; ?>

        <div class="form-group mb-3">
            <label for="category_name">Category name</label>
            <input type="text" class="form-control" id="category_name" name="category_name"
                maxlength="80" placeholder="Enter category name"
                value="<?= htmlspecialchars($nameValue) ?>">
            <small class="text-danger">
                <?php
                if (isset($_SESSION['nameError'])) {
                    echo htmlspecialchars($_SESSION['nameError']);
                    unset($_SESSION['nameError']);
                }
                ?>
            </small>
        </div>

        <div class="form-group mb-3">
            <label for="description">Description</label>
            <input type="text" class="form-control" id="description" name="description"
                maxlength="255" placeholder="Optional description"
                value="<?= htmlspecialchars($descValue) ?>">
            <small class="text-danger">
                <?php
                if (isset($_SESSION['descError'])) {
                    echo htmlspecialchars($_SESSION['descError']);
                    unset($_SESSION['descError']);
                }
                ?>
            </small>
        </div>

        <button type="submit" class="btn btn-primary" name="submit">Save</button>
        <a href="categories.php" role="button" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php include('../includes/footer.php'); ?>
