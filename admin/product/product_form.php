<?php
include('../auth.php');
$pageTitle = 'Product';
include('../../includes/adminSidebar.php');
include('../../includes/config.php');
include('../../includes/product_validation.php');

$product_id = '';
$category_id = '';
$product_name = '';
$product_description = '';
$unit_price = '';
$image_path = '';
$product_status = 'Active';

$isEdit = false;


if (isset($_GET['id'])) {

    $product_id = (int)$_GET['id'];
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


$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['form_old'] ?? [];
$error = $_SESSION['error'] ?? '';

unset($_SESSION['form_errors'], $_SESSION['form_old'], $_SESSION['error']);

if (!empty($old)) {
    $category_id = $old['category_id'];
    $product_name = $old['product_name'];
    $product_description = $old['product_description'];
    $unit_price = $old['unit_price'];
    $product_status = $old['product_status'];
}


$gallery = $isEdit ? getGallery($conn, (int)$product_id) : [];


$sql = "SELECT * FROM tbl_categories ORDER BY category_name ASC";
$category_result = mysqli_query($conn, $sql);

?>

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
            enctype="multipart/form-data"
            novalidate>


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
                    name="category_id">

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

                <?php if (isset($errors['category'])): ?>
                    <small class="text-danger">
                        <?php echo htmlspecialchars($errors['category']); ?>
                    </small>
                <?php endif; ?>

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
                    value="<?php echo htmlspecialchars($product_name); ?>">

                <?php if (isset($errors['name'])): ?>
                    <small class="text-danger">
                        <?php echo htmlspecialchars($errors['name']); ?>
                    </small>
                <?php endif; ?>

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
                    rows="4"><?php echo htmlspecialchars($product_description ?? ''); ?></textarea>

                <?php if (isset($errors['description'])): ?>
                    <small class="text-danger">
                        <?php echo htmlspecialchars($errors['description']); ?>
                    </small>
                <?php endif; ?>

            </div>


            <!-- Unit Price -->

            <div class="mb-3">

                <label for="unit_price" class="form-label">
                    Unit Price
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="unit_price"
                    name="unit_price"
                    value="<?php echo htmlspecialchars($unit_price); ?>">

                <?php if (isset($errors['price'])): ?>
                    <small class="text-danger">
                        <?php echo htmlspecialchars($errors['price']); ?>
                    </small>
                <?php endif; ?>

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
                    name="image">

                <?php if (isset($errors['image'])): ?>
                    <small class="text-danger">
                        <?php echo htmlspecialchars($errors['image']); ?>
                    </small>
                <?php endif; ?>

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


            <!-- Additional photos (gallery) -->

            <div class="mb-3">

                <label for="photos" class="form-label">
                    Additional Photos
                </label>

                <input
                    type="file"
                    class="form-control"
                    id="photos"
                    name="photos[]"
                    multiple>

                <small class="text-muted">
                    You can add up to <?php echo MAX_GALLERY_PHOTOS; ?> additional photos
                    (JPG, PNG, GIF or WEBP, 2 MB each). Hold Ctrl to choose many files.
                </small>

                <?php if (isset($errors['photos'])): ?>
                    <br>
                    <small class="text-danger">
                        <?php echo htmlspecialchars($errors['photos']); ?>
                    </small>
                <?php endif; ?>

                <?php if (!empty($gallery)): ?>

                    <div class="mt-2">

                        <p class="mb-1">Current additional photos (tick to remove):</p>

                        <div class="d-flex flex-wrap gap-3">

                            <?php foreach ($gallery as $photo): ?>

                                <div class="text-center">

                                    <img
                                        src="/InfoMan-Project/<?php echo htmlspecialchars($photo['image_path']); ?>"
                                        width="90"
                                        height="90"
                                        style="object-fit: cover; border-radius: 8px;">

                                    <div>
                                        <label>
                                            <input
                                                type="checkbox"
                                                name="remove_photos[]"
                                                value="<?php echo (int)$photo['image_id']; ?>">
                                            Remove
                                        </label>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

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
                    name="product_status">

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

                <?php if (isset($errors['status'])): ?>
                    <small class="text-danger">
                        <?php echo htmlspecialchars($errors['status']); ?>
                    </small>
                <?php endif; ?>

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

</main>
</body>

</html>