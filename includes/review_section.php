<?php

require_once __DIR__ . '/review_helpers.php';

$rvProductId  = (int) $product['product_id'];
$rvCustomerId = (isset($_SESSION['customer_id']) && ($_SESSION['role'] ?? '') === 'customer') ? (int) $_SESSION['customer_id'] : 0;
$rvIsAdmin    = ($_SESSION['role'] ?? '') === 'admin';

$rvErrors = [];
$rvOld    = [];
if (isset($_SESSION['rv_flash']) && (int) $_SESSION['rv_flash']['product'] === $rvProductId) {
    $rvErrors = $_SESSION['rv_flash']['errors'] ?? [];
    $rvOld    = $_SESSION['rv_flash']['old'] ?? [];
}
unset($_SESSION['rv_flash']);
$rvMessage = $_SESSION['rv_message'] ?? '';
unset($_SESSION['rv_message']);

// average rating
$res   = mysqli_execute_query($conn, "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM tbl_reviews WHERE product_id = ?", [$rvProductId]);
$rvSum = mysqli_fetch_assoc($res);
$rvTotal = (int) $rvSum['total'];

// the reviews, newest activity first
$res = mysqli_execute_query(
    $conn,
    "SELECT r.review_id, r.customer_id, r.rating, r.comment, r.created_at, r.updated_at, c.first_name, c.last_name
     FROM tbl_reviews r
     INNER JOIN tbl_customers c ON c.customer_id = r.customer_id
     WHERE r.product_id = ?
     ORDER BY COALESCE(r.updated_at, r.created_at) DESC, r.review_id DESC",
    [$rvProductId]
);
$rvList = mysqli_fetch_all($res, MYSQLI_ASSOC);

// who sees the form
$rvCanReview = $rvCustomerId > 0 && customerCanReview($conn, $rvCustomerId, $rvProductId);
$rvOwn       = $rvCanReview ? getOwnReview($conn, $rvCustomerId, $rvProductId) : null;

$rvRating  = (string) ($rvOld['rating']  ?? ($rvOwn['rating']  ?? ''));
$rvComment = (string) ($rvOld['comment'] ?? ($rvOwn['comment'] ?? ''));

if ($rvCanReview) {
    $_SESSION['rv_token'] = bin2hex(random_bytes(16));
}
if ($rvCustomerId === 0 && !$rvIsAdmin) {
    $_SESSION['return_to'] = '/InfoMan-Project/product.php?id=' . $rvProductId;   // after login, come back here
}

$rvLabels = [5 => 'Excellent', 4 => 'Very good', 3 => 'Good', 2 => 'Fair', 1 => 'Poor'];
?>

<div id="reviews">
    <h4>Reviews</h4>

    <?php if ($rvMessage !== ''): ?>
        <div class="alert alert-success py-2"><?= h($rvMessage) ?></div>
    <?php endif; ?>

    <?php if ($rvTotal > 0): ?>
        <p class="mb-3">
            <span class="fs-4 text-warning"><?= reviewStars((int) round((float) $rvSum['avg_rating'])) ?></span>
            <strong><?= number_format((float) $rvSum['avg_rating'], 1) ?></strong> out of 5
            <span class="text-muted">(<?= $rvTotal ?> review<?= $rvTotal === 1 ? '' : 's' ?>)</span>
        </p>
    <?php else: ?>
        <p class="text-muted">No reviews yet.</p>
    <?php endif; ?>

    <!-- review form -->
    <?php if ($rvCanReview): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="card-title"><?= $rvOwn ? 'Edit your review' : 'Write a review' ?></h6>

                <?php if (isset($rvErrors['general'])): ?>
                    <div class="alert alert-danger py-2"><?= h($rvErrors['general']) ?></div>
                <?php endif; ?>

                <form method="POST" action="/InfoMan-Project/review_save.php">
                    <input type="hidden" name="product_id" value="<?= $rvProductId ?>">
                    <input type="hidden" name="token" value="<?= h($_SESSION['rv_token']) ?>">

                    <div class="mb-3">
                        <label class="form-label d-block">Your rating</label>
                        <?php foreach ($rvLabels as $n => $word): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rv_r<?= $n ?>"
                                    value="<?= $n ?>" <?= $rvRating === (string) $n ? 'checked' : '' ?>>
                                <label class="form-check-label" for="rv_r<?= $n ?>"><?= $n ?> - <?= $word ?></label>
                            </div>
                        <?php endforeach; ?>
                        <?php if (isset($rvErrors['rating'])): ?>
                            <div class="text-danger small mt-1"><?= h($rvErrors['rating']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="rv_comment">Your review</label>
                        <textarea class="form-control <?= isset($rvErrors['comment']) ? 'is-invalid' : '' ?>"
                            name="comment" id="rv_comment" rows="3"><?= h($rvComment) ?></textarea>
                        <?php if (isset($rvErrors['comment'])): ?>
                            <div class="text-danger small mt-1"><?= h($rvErrors['comment']) ?></div>
                        <?php else: ?>
                            <div class="form-text"><?= REVIEW_COMMENT_MIN ?> to <?= REVIEW_COMMENT_MAX ?> characters.</div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary"><?= $rvOwn ? 'Update review' : 'Post review' ?></button>
                </form>
            </div>
        </div>
    <?php elseif ($rvCustomerId > 0): ?>
        <p class="text-muted small">Only customers who bought and received this product can review it.</p>
    <?php elseif ($rvIsAdmin): ?>
        <p class="text-muted small">Reviews are written by customers.</p>
    <?php else: ?>
        <p class="text-muted small">
            <a href="/InfoMan-Project/user/login.php" class="text-reset">Log in</a>
            to review this product. Only customers who bought and received it can write a review.
        </p>
    <?php endif; ?>

    <!-- review list -->
    <?php foreach ($rvList as $r): ?>
        <div class="border-top py-3">
            <div>
                <span class="text-warning"><?= reviewStars((int) $r['rating']) ?></span>
                <strong class="ms-1"><?= h(reviewerName($r['first_name'], $r['last_name'])) ?></strong>
                <?php if ((int) $r['customer_id'] === $rvCustomerId): ?>
                    <span class="badge bg-secondary ms-1">You</span>
                <?php endif; ?>
            </div>
            <div class="text-muted small">
                <?= h(date('M j, Y', strtotime($r['created_at']))) ?>
                <?php if (!empty($r['updated_at'])): ?>
                    (edited <?= h(date('M j, Y', strtotime($r['updated_at']))) ?>)
                <?php endif; ?>
            </div>
            <p class="mb-0 mt-1"><?= reviewText($r['comment']) ?></p>
        </div>
    <?php endforeach; ?>
</div>