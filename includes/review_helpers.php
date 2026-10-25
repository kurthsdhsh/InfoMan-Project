<?php

require_once __DIR__ . '/order_helpers.php';   // h()
require_once __DIR__ . '/review_filter.php';   // maskBadWords()

const REVIEW_COMMENT_MIN = 5;
const REVIEW_COMMENT_MAX = 500;


function customerCanReview(mysqli $conn, int $customerId, int $productId): bool
{
    $res = mysqli_execute_query(
        $conn,
        "SELECT 1 FROM v_order_details
         WHERE customer_id = ? AND product_id = ? AND order_status = 'Completed' LIMIT 1",
        [$customerId, $productId]
    );
    return (bool) mysqli_fetch_row($res);
}

function getOwnReview(mysqli $conn, int $customerId, int $productId): ?array
{
    $res = mysqli_execute_query(
        $conn,
        "SELECT * FROM tbl_reviews WHERE customer_id = ? AND product_id = ? LIMIT 1",
        [$customerId, $productId]
    );
    return mysqli_fetch_assoc($res) ?: null;
}

function reviewText(string $text): string
{
    return nl2br(h(maskBadWords($text)));
}

function reviewStars(int $rating): string
{
    $rating = max(0, min(5, $rating));
    return str_repeat('&#9733;', $rating) . str_repeat('&#9734;', 5 - $rating);
}

function reviewerName(string $first, string $last): string
{
    $last = trim($last);
    return trim($first . ($last !== '' ? ' ' . mb_strtoupper(mb_substr($last, 0, 1)) . '.' : ''));
}