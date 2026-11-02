<?php
include('../auth.php');
include('../../includes/config.php');
include('../../includes/filter_helper.php');

$pageTitle = 'Inventory Management';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// process stock updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    
    $product_id = trim($_POST['product_id'] ?? '');
    $movement_type = $_POST['movement_type'] ?? '';
    $quantity_input = trim($_POST['quantity'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $admin_id = $_SESSION['admin_id'] ?? null;

    $old = [
        'product_id' => $product_id,
        'movement_type' => $movement_type,
        'quantity' => $quantity_input,
        'reason' => $reason
    ];

    $errors = [];

    if ($product_id === '') {
        $errors['product'] = 'Please select a product.';
    } elseif (!ctype_digit($product_id)) {
        $errors['product'] = 'Invalid product.';
    }

    if ($movement_type === '') {
        $errors['movement'] = 'Please select a movement type.';
    } elseif (!in_array($movement_type, ['Restock', 'Adjustment'], true)) {
        $errors['movement'] = 'Invalid movement type.';
    }

    if ($quantity_input === '') {
        $errors['quantity'] = 'Please enter the quantity change.';
    } elseif (!preg_match('/^-?\d+$/', $quantity_input)) {
        $errors['quantity'] = 'Quantity must be a whole number, like 10 or -2.';
    } elseif ((int)$quantity_input == 0) {
        $errors['quantity'] = 'Quantity cannot be zero.';
    } elseif (abs((int)$quantity_input) > 1000000) {
        $errors['quantity'] = 'Quantity is too large.';
    } elseif ($movement_type === 'Restock' && (int)$quantity_input < 0) {
        $errors['quantity'] = 'Restock quantity must be greater than zero.';
    }

    if ($reason === '') {
        $errors['reason'] = 'Please enter the reason.';
    } elseif (mb_strlen($reason) > 255) {
        $errors['reason'] = 'Reason must not be more than 255 characters.';
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_old'] = $old;
        header("Location: inventory.php");
        exit;
    }

    if (!$admin_id) {
        $_SESSION['error'] = "Unable to identify the logged-in admin.";
        header("Location: inventory.php");
        exit;
    }

    $product_id = (int)$product_id;
    $quantity = (int)$quantity_input;


    try {
        $conn->begin_transaction();

        // lock the product row while updating stock
        $sql = "SELECT stock_quantity
                FROM tbl_products
                WHERE product_id = ?
                FOR UPDATE";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$product) {
            throw new Exception("Product not found.");
        }

        $current_stock = (int) $product['stock_quantity'];

        $quantity_change = (int) $quantity;

        $new_stock = $current_stock + $quantity_change;

        if ($new_stock < 0) {
            throw new Exception(
                "Insufficient stock. The stock quantity cannot be negative."
            );
        }

        // update product stock
        $sql = "UPDATE tbl_products
                SET stock_quantity = ?
                WHERE product_id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $new_stock,
            $product_id
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // record the stock movement
        $sql = "INSERT INTO tbl_stock_movements
                (product_id, admin_id, movement_type, quantity_change, reason)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            "iisis",
            $product_id,
            $admin_id,
            $movement_type,
            $quantity_change,
            $reason
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $conn->commit();

        $_SESSION['success'] = "Stock updated successfully.";
    } catch (Throwable $e) {
        $conn->rollback();

        $_SESSION['error'] = $e instanceof mysqli_sql_exception
            ? "Unable to update stock. Please check the product and database."
            : $e->getMessage();
    }

    header("Location: inventory.php");
    exit;

}

    include('../../includes/adminSidebar.php');


// Get ALL products for the stock update dropdown.
$sql = "SELECT product_id, product_name, stock_quantity
        FROM tbl_products
        ORDER BY product_name ASC";

$productResult = mysqli_query($conn, $sql);

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['form_old'] ?? [];

unset($_SESSION['form_errors'], $_SESSION['form_old']);

// Read search and stock-status filters.
$search = adminFilterValue('search');
$stockFilter = adminFilterValue('stock_status');

$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = 'p.product_name LIKE ?';
    $params[] = '%' . $search . '%';
    $types .= 's';
}

// Apply the selected stock status.
if ($stockFilter === 'in_stock') {
    $conditions[] = 'p.stock_quantity > p.reorder_level';
} elseif ($stockFilter === 'low_stock') {
    $conditions[] = 'p.stock_quantity > 0
                     AND p.stock_quantity <= p.reorder_level';
} elseif ($stockFilter === 'out_of_stock') {
    $conditions[] = 'p.stock_quantity = 0';
}

$sql = "SELECT
            p.product_id,
            p.product_name,
            p.stock_quantity,
            p.reorder_level,
            p.product_status,
            c.category_name
        FROM tbl_products p
        INNER JOIN tbl_categories c
            ON p.category_id = c.category_id";

if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY p.product_name ASC';

$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    $bindParams = [$types];

    foreach ($params as $key => $value) {
        $bindParams[] = &$params[$key];
    }

    call_user_func_array(
        [$stmt, 'bind_param'],
        $bindParams
    );
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$itemCount = mysqli_num_rows($result);
?>
       

<div class="inventory-page">

    <div class="inventory-heading">
        <div>
            <h2>Inventory Management</h2>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="inventory-alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="inventory-alert alert-error">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="inventory-card">
        <div class="card-heading">
            <div>
                <h3>Current Stock Levels (<?= $itemCount ?>)</h3>
                <p>Review available stock and reorder levels.</p>
            </div>

            <a
                href="stock_history.php"
                class="inventory-button secondary-button">
                View Stock History
            </a>
        </div>

        
            <?php
            renderAdminFilterForm(
                basename($_SERVER['PHP_SELF']),
                $search,
                'Search product name...',
                [
                    [
                        'name' => 'stock_status',
                        'label' => 'All Stock Statuses',
                        'options' => [
                            'in_stock' => 'In Stock',
                            'low_stock' => 'Low Stock',
                            'out_of_stock' => 'Out of Stock'
                        ],
                        'selected' => $stockFilter
                    ]
                ]
            );
            ?>


        <div class="table-wrap">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    mysqli_data_seek($result, 0);
                    $has_products = false;

                    while ($row = mysqli_fetch_assoc($result)):
                        $has_products = true;

                        $stock = (int) $row['stock_quantity'];
                        $reorder = (int) $row['reorder_level'];

                        if ($stock === 0) {
                            $stock_class = 'stock-out';
                            $stock_label = 'Out of stock';
                        } elseif ($stock <= $reorder) {
                            $stock_class = 'stock-low';
                            $stock_label = 'Low stock';
                        } else {
                            $stock_class = 'stock-normal';
                            $stock_label = 'In stock';
                        }
                    ?>
                        <tr>
                            <td class="product-name">
                                <?= htmlspecialchars($row['product_name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['category_name']) ?>
                            </td>

                            <td>
                                <span class="stock-badge <?= $stock_class ?>">
                                    <?= $stock ?>
                                </span>
                            </td>

                            <td><?= $reorder ?></td>


                            <td>
                                <span class="stock-status <?= $stock_class ?>">
                                    <?= htmlspecialchars($stock_label) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if (!$has_products): ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                No products found. Add a product first.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="inventory-card">
        <div class="card-heading">
            <div>
                <h3>Update Product Stock</h3>
                <p>Restock a product or make a stock adjustment.</p>
            </div>
        </div>

        <form method="POST" action="inventory.php" class="stock-form" novalidate>

            <div class="form-field">
                <label for="product_id">Product</label>
                <select name="product_id" id="product_id">
                    <option value="">Select a product</option>

                    <?php
                    while ($product = mysqli_fetch_assoc($productResult)):
                    ?>
                        <option
                            value="<?= (int) $product['product_id'] ?>"
                            <?= ($old['product_id'] ?? '') == $product['product_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($product['product_name']) ?>
                            — Stock: <?= (int) $product['stock_quantity'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <?php if (isset($errors['product'])): ?>
                    <small class="text-danger"><?= htmlspecialchars($errors['product']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="movement_type">Movement Type</label>
                <select name="movement_type" id="movement_type">
                    <option value="">Select movement type</option>
                    <option value="Restock" <?= ($old['movement_type'] ?? '') === 'Restock' ? 'selected' : '' ?>>Restock</option>
                    <option value="Adjustment" <?= ($old['movement_type'] ?? '') === 'Adjustment' ? 'selected' : '' ?>>Adjustment</option>
                </select>
                <?php if (isset($errors['movement'])): ?>
                    <small class="text-danger"><?= htmlspecialchars($errors['movement']) ?></small>
                <?php endif; ?>
                <small>
                    Use a positive quantity to add stock or a negative quantity
                    for a stock reduction.
                </small>
            </div>

            <div class="form-field">
                <label for="quantity">Quantity Change</label>
                <input
                    type="text"
                    inputmode="numeric"
                    name="quantity"
                    id="quantity"
                    value="<?= htmlspecialchars($old['quantity'] ?? '') ?>"
                    placeholder="Example: 10 or -2"
                >
                <?php if (isset($errors['quantity'])): ?>
                    <small class="text-danger"><?= htmlspecialchars($errors['quantity']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="reason">Reason</label>
                <input
                    type="text"
                    name="reason"
                    id="reason"
                    value="<?= htmlspecialchars($old['reason'] ?? '') ?>"
                    placeholder="Example: New delivery received"
                >
                <?php if (isset($errors['reason'])): ?>
                    <small class="text-danger"><?= htmlspecialchars($errors['reason']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" name="update_stock" value="1">
                    Save Stock Change
                </button>
            </div>

        </form>
    </div>

</div>
                 
</html>
