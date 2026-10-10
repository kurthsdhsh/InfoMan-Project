<?php

date_default_timezone_set('Asia/Manila');

// shop rules
const STORE_OPEN_HOUR    = 9;   // first pickup/delivery slot: 9:00 AM
const STORE_CLOSE_HOUR   = 18;  // last slot starts before 6:00 PM
const SLOT_MINUTES       = 30;  // one slot every 30 minutes
const MIN_LEAD_HOURS     = 1;   // earliest choice = now + 2 hours (shop needs time to prepare)
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

function orderNumber(int $orderId): string
{
    return 'BP-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);
}

function requireCustomer(): void
{
    if (!isset($_SESSION['customer_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
        $_SESSION['success'] = 'Please sign in to place an order.';   // login.php shows this one
        header('Location: /InfoMan-Project/user/login.php');
        exit();
    }
}

// cart 
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
            continue; 
        }
        $lines[] = [
            'product_id' => (int) $p['product_id'],
            'name'       => $p['product_name'],
            'unit_price' => (float) $p['unit_price'],
            'qty'        => $qty,
            'stock'      => (int) $p['stock_quantity'],
            'status'     => $p['product_status'],
            'line_total' => (float) $p['unit_price'] * $qty,   
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

function timeSlots(): array
{
    $slots = [];
    for ($m = STORE_OPEN_HOUR * 60; $m < STORE_CLOSE_HOUR * 60; $m += SLOT_MINUTES) {
        $key = sprintf('%02d:%02d:00', intdiv($m, 60), $m % 60);
        $slots[$key] = date('g:i A', strtotime($key));
    }
    return $slots;
}

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

const ASAP_MINUTES = 20;   

function asapText(): string
{
    return (ASAP_MINUTES % 20 === 0)
        ? (ASAP_MINUTES / 20) . ((ASAP_MINUTES / 20) === 1 ? ' hour' : ' hours')
        : ASAP_MINUTES . ' minutes';
}

function asapAvailable(): bool
{
    $now   = new DateTime();
    $open  = new DateTime('today ' . sprintf('%02d:00', STORE_OPEN_HOUR));
    $close = new DateTime('today ' . sprintf('%02d:00', STORE_CLOSE_HOUR));
    $ready = (clone $now)->modify('+' . ASAP_MINUTES . ' minutes');
    return $now >= $open && $ready <= $close;
}

function availableSlots(string $date): array
{
    $out = [];
    foreach (timeSlots() as $key => $label) {
        [$when, $error] = validateSchedule($date, $key);
        if (!$error) {
            $out[$key] = $label;
        }
    }
    return $out;
}

function availableDays(): array
{
    $days = [];
    for ($i = 0; $i <= MAX_DAYS_AHEAD; $i++) {
        $date = date('Y-m-d', strtotime("+$i days"));
        if (!availableSlots($date)) {
            continue;
        }
        $name = ($i === 0) ? 'Today' : (($i === 1) ? 'Tomorrow' : date('D', strtotime($date)));
        $days[$date] = $name . ', ' . date('M j', strtotime($date));
    }
    return $days;
}

function validateChoice(string $date, string $time): array
{
    if ($time === 'ASAP') {
        if (!asapAvailable()) {
            return [null, 'ASAP is not available right now. Please choose a day and time.'];
        }
        return [date('Y-m-d H:i:s', strtotime('+' . ASAP_MINUTES . ' minutes')), null];
    }
    return validateSchedule($date, $time);
}

function scheduleText(string $date, string $time): string
{
    if ($time === 'ASAP') {
        return 'ASAP (ready in about ' . asapText() . ')';
    }
    return date('D, M j \a\t g:i A', strtotime($date . ' ' . $time));
}

//delivery
function areaList(mysqli $conn): array
{
    $list = [];
    $result = mysqli_query($conn, "SELECT area_id, area_name, delivery_fee FROM tbl_delivery_areas ORDER BY delivery_fee, area_name");
    while ($row = mysqli_fetch_assoc($result)) {
        $list[(int) $row['area_id']] = ['name' => $row['area_name'], 'fee' => (float) $row['delivery_fee']];
    }
    return $list;
}

