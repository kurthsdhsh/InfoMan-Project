<?php
session_start();
include('./includes/config.php');
include('./includes/header.php');

$id = (int) ($_GET['id'] ?? 0);

$result = mysqli_execute_query(
    $conn,
    "SELECT p.*, c.category_name
     FROM tbl_products p
     LEFT JOIN tbl_categories c ON c.category_id = p.category_id
     WHERE p.product_id = ? AND p.product_status = 'Active' LIMIT 1",
    [$id]
);
$product = mysqli_fetch_assoc($result);

if (!$product) {
        echo '<p class="text-center my-5">Product not found. <a href="/InfoMan-Project/index.php" class="text-reset text-decoration-none">Back to the shop</a></p>';
    exit();
}

$name = htmlspecialchars($product['product_name']);
$qty  = (int) $product['stock_quantity'];
$img  = $product['image_path'] ?? '';



$slides = [];
 
if ($img !== '') {
    $slides[] = $img;
}
 
$galleryResult = mysqli_execute_query(
    $conn,
    "SELECT image_path FROM tbl_product_images WHERE product_id = ? ORDER BY image_id ASC",
    [$id]
);
 
while ($photo = mysqli_fetch_assoc($galleryResult)) {
    $slides[] = $photo['image_path'];
}
 
$slideCount = count($slides);
?>


<div class="container my-4">
    <a href="/InfoMan-Project/index.php" class="text-reset text-decoration-none">&larr; Back to products</a>

    <div class="row mt-3">

        <div class="col-md-5">
              <?php if ($slideCount > 0): ?>
 
                <div class="slideshow">
 
                    <?php foreach ($slides as $i => $path): ?>
                        <img src="/InfoMan-Project/<?php echo htmlspecialchars($path); ?>"
                            class="slide <?php echo $i === 0 ? 'active' : ''; ?>"
                            alt="<?php echo $name; ?> photo <?php echo $i + 1; ?>">
                    <?php endforeach; ?>
 
                    <?php if ($slideCount > 1): ?>
                        <button type="button" class="slide-btn slide-prev" onclick="changeSlide(-1)"
                            aria-label="Previous photo">&#10094;</button>
                        <button type="button" class="slide-btn slide-next" onclick="changeSlide(1)"
                            aria-label="Next photo">&#10095;</button>
                        <span class="slide-count" id="slideCount">1 / <?php echo $slideCount; ?></span>
                    <?php endif; ?>
 
                </div>

                    <?php if ($slideCount > 1): ?>
                    <script>
                        var currentSlide = 0;
 
                        function changeSlide(step) {
                            var slides = document.querySelectorAll('.slideshow .slide');
 
                            slides[currentSlide].classList.remove('active');
                            currentSlide = (currentSlide + step + slides.length) % slides.length;
                            slides[currentSlide].classList.add('active');
 
                            document.getElementById('slideCount').textContent =
                                (currentSlide + 1) + ' / ' + slides.length;
                        }
                    </script>
                <?php endif; ?>
 
            <?php else: ?>
                <p class="text-muted">No photo yet.</p>
            <?php endif; ?>
        </div>

        <div class="col-md-7">
            <h2><?php echo $name; ?></h2>
            <p class="text-muted"><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></p>
            <p><?php echo nl2br(htmlspecialchars($product['product_description'] ?? '')); ?></p>
            <h4>₱<?php echo number_format($product['unit_price'], 2); ?></h4>

            <?php if ($qty > 0): ?>
                <p class="text-muted"><?php echo $qty; ?> in stock</p>
                <form method="POST" action="/InfoMan-Project/cart_update.php" class="d-flex gap-2 align-items-center">
                    <input type="number" name="item_qty" value="1" class="form-control" style="width: 90px;">
                    <input type="hidden" name="item_id" value="<?php echo (int) $product['product_id']; ?>">
                    <input type="hidden" name="type" value="add">
                    <button type="submit" class="btn btn-primary">Add to basket</button>
                </form>
            <?php else: ?>
                <p class="text-danger">Out of stock</p>
            <?php endif; ?>
        </div>
    </div>

    <hr class="my-4">

    <?php include __DIR__ . '/includes/review_section.php'; ?>

</div>