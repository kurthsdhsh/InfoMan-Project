<?php

session_start();

require_once __DIR__ . '/../../includes/order_helpers.php';

$home = '/InfoMan-Project/index.php';


// CANCEL
if (isset($_GET['cancel'])) {

    unset(
        $_SESSION['pending_add'],
        $_SESSION['om_errors'],
        $_SESSION['om_form']
    );

    header('Location: ' . $home);
    exit();
}


// ONLY ALLOW POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: ' . $home);
    exit();
}


// GET FORM DATA
$method  = $_POST['method'] ?? '';
$address = trim($_POST['address'] ?? '');
$date    = trim($_POST['date'] ?? '');
$time    = trim($_POST['time'] ?? '');

$errors = [];


// CHECK ORDER TYPE
if (!in_array($method, ['Pickup', 'Delivery'], true)) {

    header('Location: ' . $home . '?om=1');
    exit();
}


// CHECK DELIVERY ADDRESS
if ($method === 'Delivery') {

    if (strlen($address) < 10) {

        $errors[] = 'Please enter your full delivery address (house/street, barangay, city).';

    } elseif (strlen($address) > 255) {

        $errors[] = 'The delivery address is too long (255 characters max).';
    }

} else {

    // Pickup does not need an address
    $address = '';
}


// CHECK DATE AND TIME
[$when, $scheduleError] = validateSchedule($date, $time);

if ($scheduleError) {

    $errors[] = $scheduleError;
}


// IF THERE ARE ERRORS
if (!empty($errors)) {

    $_SESSION['om_errors'] = $errors;

    $_SESSION['om_form'] = [
        'address' => $address,
        'date'    => $date,
        'time'    => $time
    ];

    header(
        'Location: ' .
        $home .
        '?om=2&method=' .
        urlencode($method)
    );

    exit();
}


// SAVE ORDER TYPE TO SESSION
$_SESSION['checkout'] = [
    'method'  => $method,
    'address' => $address,
    'date'    => $date,
    'time'    => $time,
    'note'    => $_SESSION['checkout']['note'] ?? ''
];


// AFTER SAVING
// If there is a pending product waiting to be added,
// continue to cart_update.php.

if (!empty($_SESSION['pending_add'])) {

    header(
        'Location: /InfoMan-Project/cart_update.php?resume=1'
    );

} else {

    header('Location: ' . $home);
}

exit();