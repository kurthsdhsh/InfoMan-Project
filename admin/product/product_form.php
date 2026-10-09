<?php
session_start();

include('../../includes/adminHeader.php');
include('../../includes/config.php');

echo '<link rel="stylesheet" href="/InfoMan-Project/includes/style/adminstyle.css?v=' . time() . '">';

$product_id = '';
$category_id = '';
$product_name = '';
$product_description = '';
$unit_price = '';
$image_path = '';
$product_status = 'Active';

$isEdit = false;


// Check if editing
if (isset($_GET['id'])) {

    $product_id = $_GET['id'];
    $isEdit = true;

    $sql = "SELECT * FROM tbl_products WHERE product_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $row = mysqli_fetch_assoc($result);

        $category_id = $row['category_id'];
        $product_name = $row['product_name'];
        $product_description = $row['product_description'];
        $unit_price = $row['unit_price'];
        $image_path = $row['image_path'];
        $product_status = $row['product_status'];

    } else {

        $_SESSION['error'] = "Product not found.";
        header("Location: products.php");
        exit;
    }

    mysqli_stmt_close($stmt);
}


// Get categories for dropdown
$sql = "SELECT * FROM tbl_categories ORDER BY category_name ASC";
$category_result = mysqli_query($conn, $sql);

?>

<body>

    <div class="admin-container">

        <div class="admin-toolbar">

            <h2>
                <?php
                if ($isEdit) {
                    echo "Edit product";
                } else {
                    echo "Add product";
                }
                ?>
            </h2>

        </div>


        <div class="table-card" style="padding: 30px;">

            <form
                action="<?php echo $isEdit ? 'update.php' : 'store.php'; ?>"
                method="POST"
                enctype="multipart/form-data">


                <?php if ($isEdit): ?>

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?php echo htmlspecialchars($product_id); ?>">

                <?php endif; ?>


                <!-- Category -->

                <div class="mb-3">

                    <label for="category_id" class="form-label">
                        Category
                    </label>

                    <select
                        class="form-select"
                        id="category_id"
                        name="category_id"
                        required>

                        <option value="">Select category</option>

                        <?php while ($category = mysqli_fetch_assoc($category_result)): ?>

                            <option
                                value="<?php echo $category['category_id']; ?>"
                                <?php
                                if ($category_id == $category['category_id']) {
                                    echo 'selected';
                                }
                                ?>>

                                <?php echo htmlspecialchars($category['category_name']); ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- Product Name -->

                <div class="mb-3">

                    <label for="product_name" class="form-label">
                        Product Name
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="product_name"
                        name="product_name"
                        value="<?php echo htmlspecialchars($product_name); ?>"
                        required>

                </div>


                <!-- Description -->

                <div class="mb-3">

                    <label for="product_description" class="form-label">
                        Description
                    </label>

                    <textarea
                        class="form-control"
                        id="product_description"
                        name="product_description"
                        rows="4"><?php echo htmlspecialchars($product_description); ?></textarea>

                </div>


                <!-- Unit Price -->

                <div class="mb-3">

                    <label for="unit_price" class="form-label">
                        Unit Price
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="unit_price"
                        name="unit_price"
                        value="<?php echo htmlspecialchars($unit_price); ?>"
                        min="0"
                        step="0.01"
                        required>

                </div>


                <!-- Image -->

                <div class="mb-3">

                    <label for="image" class="form-label">
                        Product Image
                    </label>

                    <input
                        type="file"
                        class="form-control"
                        id="image"
                        name="image"
                        accept="image/*">

                    <?php if (!empty($image_path)): ?>

                        <div class="mt-2">

                            <p>Current image:</p>

                            <img
                                src="/InfoMan-Project/<?php echo htmlspecialchars($image_path); ?>"
                                width="120"
                                height="120"
                                style="object-fit: cover; border-radius: 8px;">

                        </div>

                    <?php endif; ?>

                </div>


                <!-- Status -->

                <div class="mb-3">

                    <label for="product_status" class="form-label">
                        Status
                    </label>

                    <select
                        class="form-select"
                        id="product_status"
                        name="product_status"
                        required>

                        <option
                            value="Active"
                            <?php
                            if ($product_status == 'Active') {
                                echo 'selected';
                            }
                            ?>>
                            Active
                        </option>

                        <option
                            value="Inactive"
                            <?php
                            if ($product_status == 'Inactive') {
                                echo 'selected';
                            }
                            ?>>
                            Inactive
                        </option>

                    </select>

                </div>


                <!-- Buttons -->

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn-add"
                        style="border: none; cursor: pointer;">

                        <?php
                        echo $isEdit ? "Update product" : "Add product";
                        ?>

                    </button>


                    <a
                        href="products.php"
                        class="btn btn-secondary">

                        Cancel

                    </a>

                </div>

            </form>

        </div>

    </div>

</body>