<?php
session_start();
include('../includes/config.php');

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: adminlogin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $categoryId = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);

    if (!$categoryId) {
        $_SESSION['message'] = 'Invalid category.';
        header('Location: categories.php');
        exit;
    }

    $categoryResult = mysqli_execute_query(
        $conn,
        'SELECT category_id, category_name FROM tbl_categories WHERE category_id = ? LIMIT 1',
        [$categoryId]
    );
    $category = $categoryResult ? mysqli_fetch_assoc($categoryResult) : null;

    if (!$category) {
        $_SESSION['message'] = 'Category not found.';
        header('Location: categories.php');
        exit;
    }

    $productResult = mysqli_execute_query(
        $conn,
        'SELECT COUNT(*) AS product_count FROM tbl_products WHERE category_id = ?',
        [$categoryId]
    );
    $productCount = (int) (mysqli_fetch_assoc($productResult)['product_count'] ?? 0);

    if ($productCount > 0) {
        $_SESSION['message'] = 'Cannot delete "' . $category['category_name'] . '". This category still has '
            . $productCount . ' product' . ($productCount === 1 ? '' : 's')
            . '. Reassign or delete those products first.';
        header('Location: categories.php');
        exit;
    }

    mysqli_execute_query($conn, 'DELETE FROM tbl_categories WHERE category_id = ?', [$categoryId]);
    $_SESSION['success'] = '"' . $category['category_name'] . '" was deleted.';
    header('Location: categories.php');
    exit;
}

include('../includes/adminHeader.php');
echo '<link rel="stylesheet" href="../includes/style/adminstyle.css?v=' . time() . '">';

$sql = 'SELECT c.category_id, c.category_name, c.description,
               COUNT(p.product_id) AS product_count
        FROM tbl_categories c
        LEFT JOIN tbl_products p ON p.category_id = c.category_id
        GROUP BY c.category_id, c.category_name, c.description
        ORDER BY c.category_name ASC';
$result = mysqli_query($conn, $sql);
$categoryCount = $result ? mysqli_num_rows($result) : 0;
?>

<div class="admin-container">
    <?php include('../includes/alert.php'); ?>
    <div class="admin-toolbar">
        <h2>Categories (<?= (int) $categoryCount ?>)</h2>
        <a href="category_form.php" class="btn-add" role="button">Add category</a>
    </div>

    <div class="table-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Products</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($categoryCount === 0): ?>
                    <tr>
                        <td colspan="5">No categories yet. Add one to start grouping products.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= (int) $row['category_id'] ?></td>
                            <td><?= htmlspecialchars($row['category_name']) ?></td>
                            <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
                            <td><?= (int) $row['product_count'] ?></td>
                            <td>
                                <a href="category_form.php?id=<?= (int) $row['category_id'] ?>" title="Edit">
                                    <i class="fa-regular fa-pen-to-square" style="color: blue"></i>
                                </a>
                                <form method="POST" action="categories.php" class="d-inline"
                                    onsubmit="return confirm('Delete this category? This cannot be undone.');">
                                    <input type="hidden" name="delete_id" value="<?= (int) $row['category_id'] ?>">
                                    <button type="submit" class="btn-icon-delete" title="Delete">
                                        <i class="fa-solid fa-trash" style="color: red"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include('../includes/footer.php'); ?>
