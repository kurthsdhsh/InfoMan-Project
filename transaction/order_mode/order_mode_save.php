<?php

session_start();
require_once __DIR__ . '/../../includes/order_helpers.php';

$home = '/InfoMan-Project/index.php';

$isCheckout = (($_REQUEST['return'] ?? '') === 'checkout');
$back = $isCheckout ? '/InfoMan-Project/transaction/checkout/checkout.php' : $home;

if (isset($_GET['cancel'])) {
    unset($_SESSION['pending_add'], $_SESSION['om_errors'], $_SESSION['om_form']);
    header('Location: ' . $back);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $back);
    exit();
}

$method  = $_POST['method'] ?? '';
$address = trim($_POST['address'] ?? '');
$date    = trim($_POST['date'] ?? '');
$time    = trim($_POST['time'] ?? '');

if (!in_array($method, ['Pickup', 'Delivery'], true)) {
    header('Location: ' . $back . '?om=1');
    exit();
}

if (isset($_POST['day'])) {
    $_SESSION['om_form'] = ['address' => $address, 'date' => trim($_POST['day']), 'time' => ''];
    header('Location: ' . $back . '?om=2&method=' . $method);
    exit();
}

if ($time === 'ASAP') {
    $date = date('Y-m-d');
}

$errors = [];
if ($method === 'Delivery') {
    if (strlen($address) < 10) {
        $errors[] = 'Please enter your full delivery address (house/street, barangay, city).';
    } elseif (strlen($address) > 255) {
        $errors[] = 'The delivery address is too long (255 characters max).';
    }
} else {
    $address = '';   // pick-up has no address
}

[$when, $scheduleError] = validateChoice($date, $time);
if ($scheduleError) {
    $errors[] = $scheduleError;
}

if ($errors) {
    $_SESSION['om_errors'] = $errors;
    $_SESSION['om_form']   = ['address' => $address, 'date' => $date, 'time' => $time];
    header('Location: ' . $back . '?om=2&method=' . $method);
    exit();
}

$_SESSION['checkout'] = [
    'method'  => $method,
    'address' => $address,
    'date'    => $date,
    'time'    => $time,
    'note'    => $_SESSION['checkout']['note'] ?? '',  
];

if (!$isCheckout && !empty($_SESSION['pending_add'])) {
    header('Location: /InfoMan-Project/cart_update.php?resume=1');
} else {
    header('Location: ' . $back);
}
exit();
