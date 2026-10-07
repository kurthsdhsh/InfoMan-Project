<?php
session_start();
include('./includes/config.php');
include('./includes/header.php');

$keyword = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM tbl_products WHERE product_name LIKE ? OR product_description LIKE ?";
$result = mysqli_execute_query($conn, $sql, ["%{$keyword}%", "%{$keyword}%"]);

$itemCount = mysqli_num_rows($result);

?>
<h1 align="center">Search Results</h1>
<h2>
    <?= $itemCount ?> result(s) for "<?= htmlspecialchars($keyword) ?>"
</h2>

<?php
if ($itemCount > 0) {
    echo '<ul class="products">';

    while ($row = mysqli_fetch_assoc($result)) {
        $id    = (int) $row['product_id'];
        $name  = htmlspecialchars($row['product_name']);
        $desc  = htmlspecialchars($row['product_description']);
        $price = number_format($row['unit_price'], 2);
        $qty   = (int) $row['stock_quantity'];
        $img   = htmlspecialchars($row['image_path']);
        ?>
        <li class="product">
            <form method="POST" action="cart_update.php">
                <div class="product-content">
                    <h3><?php echo $name; ?></h3>
                    <div class="product-thumb">
                        <img src="./admin_item/<?php echo $img; ?>" width="50" height="50" alt="<?php echo $name; ?>">
                    </div>
                    <div class="product-info">
                        <?php echo $desc; ?><br>
                        Price <?php echo $price; ?><br>
                        <?php if ($qty > 0): ?>
                            <fieldset>
                                <label>
                                    <span>Quantity</span>
                                    <input type="number" name="item_qty" value="1" min="1" max="<?php echo $qty; ?>" />
                                </label>
                            </fieldset>
                            <input type="hidden" name="item_id" value="<?php echo $id; ?>" />
                            <input type="hidden" name="type" value="add" />
                            <div align="center"><button type="submit" class="add_to_cart">Add</button></div>
                        <?php else: ?>
                            <p>Out of stock</p>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </li>
        <?php
    }

    echo '</ul>';
} else {
    echo '<p align="center">No products found.</p>';
}