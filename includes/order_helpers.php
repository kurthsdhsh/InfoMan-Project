<?php

date_default_timezone_set('Asia/Manila');

// shop rules
const STORE_OPEN_HOUR    = 9;   // first pickup/delivery slot: 9:00 AM
const STORE_CLOSE_HOUR   = 18;  // last slot starts before 6:00 PM
const SLOT_MINUTES       = 30;  // one slot every 30 minutes
const MIN_LEAD_HOURS     = 2;   // earliest choice = now + 2 hours (shop needs time to prepare)
const MAX_DAYS_AHEAD     = 7;   // latest choice = today + 7 days
const MAX_PENDING_ORDERS = 3;   // fake-order guard: max Pending orders per customer


function h($text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function peso($amount): string
{
    return '₱' . number_format((float) $amount, 2);
}

// Order number shown to the customer. It is just order_id made pretty, so nothing extra is stored.
function orderNumber(int $orderId): string
{
    return 'BP-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);
}

// Only a signed-in CUSTOMER may use the checkout pages (an admin session is refused).
function requireCustomer(): void
{
    if (!isset($_SESSION['customer_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
        $_SESSION['success'] = 'Please sign in to place an order.';   // login.php shows this one
        header('Location: /InfoMan-Project/user/login.php');
        exit();
    }
}

// ---------- cart ----------
// Reads the session cart (Partner A's cart_update.php fills it) but takes the REAL
// name, price and stock from the database, so the customer is never charged a stale price.
function getCartLines(mysqli $conn): array
{
    $lines = [];
    foreach (($_SESSION['cart_products'] ?? []) as $id => $cartItem) {
        $qty = (int) ($cartItem['item_qty'] ?? 0);
        if ($qty < 1) {
            continue;
        }
        $result = mysqli_execute_query(
            $conn,
            "SELECT product_id, product_name, unit_price, stock_quantity, product_status
             FROM tbl_products WHERE product_id = ? LIMIT 1",
            [(int) $id]
        );
        $p = mysqli_fetch_assoc($result);
        if (!$p) {
            continue; // product was deleted by the admin
        }
        $lines[] = [
            'product_id' => (int) $p['product_id'],
            'name'       => $p['product_name'],
            'unit_price' => (float) $p['unit_price'],
            'qty'        => $qty,
            'stock'      => (int) $p['stock_quantity'],
            'status'     => $p['product_status'],
            'line_total' => (float) $p['unit_price'] * $qty,   // calculated, never stored
        ];
    }
    return $lines;
}

function cartTotal(array $lines): float
{
    $sum = 0.0;
    foreach ($lines as $l) {
        $sum += $l['line_total'];
    }
    return $sum;
}

// Returns an error message if some product cannot be sold in that quantity, otherwise null.
function cartProblem(array $lines): ?string
{
    foreach ($lines as $l) {
        if ($l['status'] !== 'Active') {
            return $l['name'] . ' is no longer available. Please remove it from your cart.';
        }
        if ($l['qty'] > $l['stock']) {
            return 'Only ' . $l['stock'] . ' left of ' . $l['name'] . '. Please lower the quantity.';
        }
    }
    return null;
}

// ---------- pickup / delivery schedule ----------
// ['09:00:00' => '9:00 AM', '09:30:00' => '9:30 AM', ...]
function timeSlots(): array
{
    $slots = [];
    for ($m = STORE_OPEN_HOUR * 60; $m < STORE_CLOSE_HOUR * 60; $m += SLOT_MINUTES) {
        $key = sprintf('%02d:%02d:00', intdiv($m, 60), $m % 60);
        $slots[$key] = date('g:i A', strtotime($key));
    }
    return $slots;
}

// Returns [ 'Y-m-d H:i:s' or null, error message or null ]
function validateSchedule(string $date, string $time): array
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return [null, 'Please choose a valid date.'];
    }
    if (!isset(timeSlots()[$time])) {
        return [null, 'Please choose a time from the list.'];
    }
    $when = new DateTime("$date $time");
    $now  = new DateTime();
    if ($when < (clone $now)->modify('+' . MIN_LEAD_HOURS . ' hours')) {
        return [null, 'Please choose a time at least ' . MIN_LEAD_HOURS . ' hours from now.'];
    }
    if ($when > (clone $now)->modify('+' . MAX_DAYS_AHEAD . ' days')) {
        return [null, 'Please choose a date within the next ' . MAX_DAYS_AHEAD . ' days.'];
    }
    return [$when->format('Y-m-d H:i:s'), null];
}
