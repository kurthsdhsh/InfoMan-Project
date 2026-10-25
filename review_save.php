<?php

session_start();
include(__DIR__ . '/includes/config.php');
require_once __DIR__ . '/includes/review_helpers.php';

$productId = (int) ($_POST['product_id'] ?? 0);
$back      = '/InfoMan-Project/product.php?id=' . $productId . '#reviews';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $productId < 1) {
    header('Location: /InfoMan-Project/index.php');
    exit();
}

if (!isset($_SESSION['customer_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    $_SESSION['success']   = 'Please sign in as a customer to write a review.';   // login.php shows this one
    $_SESSION['return_to'] = '/InfoMan-Project/product.php?id=' . $productId;
    header('Location: /InfoMan-Project/user/login.php');
    exit();
}
$customerId = (int) $_SESSION['customer_id'];

$saved = $_SESSION['rv_token'] ?? '';
unset($_SESSION['rv_token']);
if ($saved === '' || !hash_equals($saved, (string) ($_POST['token'] ?? ''))) {
    $_SESSION['rv_flash'] = ['product' => $productId, 'errors' => ['general' => 'That page was out of date. Please try again.'], 'old' => []];
    header('Location: ' . $back);
    exit();
}

$ratingRaw = trim((string) ($_POST['rating'] ?? ''));
$comment   = trim((string) ($_POST['comment'] ?? ''));
$errors    = [];

$res = mysqli_execute_query($conn, "SELECT product_id FROM tbl_products WHERE product_id = ? LIMIT 1", [$productId]);
if (!mysqli_fetch_assoc($res)) {
    $_SESSION['error'] = 'That product was not found.';
    header('Location: /InfoMan-Project/index.php');
    exit();
}

if (!customerCanReview($conn, $customerId, $productId)) {
    $errors['general'] = 'Only customers who received this product can review it.';
}

if (!ctype_digit($ratingRaw) || (int) $ratingRaw < 1 || (int) $ratingRaw > 5) {
    $errors['rating'] = 'Please choose a rating from 1 to 5.';
}
$len = mb_strlen($comment);
if ($len < REVIEW_COMMENT_MIN) {
    $errors['comment'] = 'Please write at least ' . REVIEW_COMMENT_MIN . ' characters about the product.';
} elseif ($len > REVIEW_COMMENT_MAX) {
    $errors['comment'] = 'Your review is too long (' . $len . ' characters). The most is ' . REVIEW_COMMENT_MAX . '.';
}

if ($errors) {
    $_SESSION['rv_flash'] = ['product' => $productId, 'errors' => $errors, 'old' => ['rating' => $ratingRaw, 'comment' => $comment]];
    header('Location: ' . $back);
    exit();
}

try {
    $comment  = maskBadWords($comment);  
    $rating   = (int) $ratingRaw;
    $existing = getOwnReview($conn, $customerId, $productId);

    if ($existing) {
        mysqli_execute_query(
            $conn,
            "UPDATE tbl_reviews SET rating = ?, comment = ?, updated_at = NOW()
             WHERE review_id = ? AND customer_id = ?",          
            [$rating, $comment, (int) $existing['review_id'], $customerId]
        );
        $_SESSION['rv_message'] = 'Your review was updated.';
    } else {
        mysqli_execute_query(
            $conn,
            "INSERT INTO tbl_reviews (product_id, customer_id, rating, comment) VALUES (?, ?, ?, ?)",
            [$productId, $customerId, $rating, $comment]
        );
        $_SESSION['rv_message'] = 'Thank you! Your review was posted.';
    }
} catch (Throwable $e) {
    error_log('review_save failed: ' . $e->getMessage());
    $_SESSION['rv_flash'] = ['product' => $productId, 'errors' => ['general' => 'Something went wrong and your review was not saved. Please try again.'], 'old' => ['rating' => $ratingRaw, 'comment' => $comment]];
}

header('Location: ' . $back);
exit();