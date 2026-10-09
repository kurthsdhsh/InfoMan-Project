<?php

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/order_helpers.php';

requireCustomer();

// 1) the cart must have something in it, and every item must still be available
$lines = getCartLines($conn);
if (!$lines) {
    $_SESSION['message'] = 'Your cart is empty. Add something first.';
    header('Location: view_cart.php');
    exit();
}
if ($problem = cartProblem($lines)) {
    $_SESSION['message'] = $problem;
    header('Location: view_cart.php');
    exit();
}

// 2) values to show in the form (what the customer typed before, or defaults)
$form   = $_SESSION['checkout'] ?? ['method' => 'Pickup', 'address' => '', 'date' => '', 'time' => '', 'note' => ''];
$errors = [];

// 3) the form was submitted: validate on the server
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['method']  = $_POST['method'] ?? '';
    $form['address'] = trim($_POST['address'] ?? '');
    $form['date']    = trim($_POST['date'] ?? '');
    $form['time']    = trim($_POST['time'] ?? '');
    $form['note']    = trim($_POST['note'] ?? '');

    if (!in_array($form['method'], ['Pickup', 'Delivery'], true)) {
        $errors[] = 'Please choose Pickup or Delivery.';
    }
    if ($form['method'] === 'Delivery') {
        if (strlen($form['address']) < 10) {
            $errors[] = 'Please enter your full delivery address (house/street, barangay, city).';
        } elseif (strlen($form['address']) > 255) {
            $errors[] = 'The delivery address is too long (255 characters max).';
        }
    } else {
        $form['address'] = '';          // pickup has no address
    }

    [$when, $scheduleError] = validateSchedule($form['date'], $form['time']);
    if ($scheduleError) {
        $errors[] = $scheduleError;
    }
    if (strlen($form['note']) > 500) {
        $errors[] = 'The note is too long (500 characters max).';
    }

    if (!$errors) {
        $_SESSION['checkout'] = $form;
        header('Location: order_summary.php');
        exit();
    }
}

$slots   = timeSlots();
$minDate = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+' . MAX_DAYS_AHEAD . ' days'));

// header.php prints HTML, so it comes AFTER every header("Location: ...") above
include __DIR__ . '/../includes/header.php';
?>
<style>
    .method-card { border: 2px solid #E4E4E4; border-radius: 8px; padding: 14px; cursor: pointer; display: block; height: 100%; }
    .method-card:has(input:checked) { border-color: #B77466; background: #FFF3DC; }
</style>

<h1 align="center">Checkout</h1>

<div class="cart-view-table-back" style="max-width:700px;">
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <div><?= h($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="checkout.php" id="checkoutForm" novalidate>

        <h5>1. How do you want to get your order?</h5>
        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="method-card">
                    <input type="radio" name="method" value="Pickup" <?= $form['method'] !== 'Delivery' ? 'checked' : '' ?>>
                    <strong>Pickup</strong><br>
                    <small>Get it at our store and pay cash there.</small>
                </label>
            </div>
            <div class="col-6">
                <label class="method-card">
                    <input type="radio" name="method" value="Delivery" <?= $form['method'] === 'Delivery' ? 'checked' : '' ?>>
                    <strong>Delivery</strong><br>
                    <small>Our own driver brings it to you. Pay cash on delivery.</small>
                </label>
            </div>
        </div>

        <div id="addressBox" class="mb-3">
            <label for="address" class="form-label">Delivery address</label>
            <textarea class="form-control" id="address" name="address" rows="2" maxlength="255"
                placeholder="House/Unit no., Street, Barangay, City"><?= h($form['address']) ?></textarea>
        </div>

        <h5>2. <span id="whenTitle">Pickup</span> date and time</h5>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date"
                    min="<?= $minDate ?>" max="<?= $maxDate ?>" value="<?= h($form['date']) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="time" class="form-label">Time</label>
                <select class="form-select" id="time" name="time" required>
                    <option value="">Choose a time</option>
                    <?php foreach ($slots as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $form['time'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <p class="small text-muted">
            Open <?= date('g A', strtotime(STORE_OPEN_HOUR . ':00')) ?> to <?= date('g A', strtotime(STORE_CLOSE_HOUR . ':00')) ?>.
            Choose a time at least <?= MIN_LEAD_HOURS ?> hours from now, within the next <?= MAX_DAYS_AHEAD ?> days.
        </p>

        <h5>3. Note for the shop (optional)</h5>
        <textarea class="form-control mb-3" name="note" rows="2" maxlength="500"
            placeholder="Example: please wrap as a gift"><?= h($form['note']) ?></textarea>

        <div class="d-flex justify-content-between">
            <a href="view_cart.php" class="button" style="float:none;">Back to cart</a>
            <button type="submit" style="float:none;">Continue to order summary</button>
        </div>
    </form>
</div>

<script>
    // show the address box only for Delivery, and rename the labels to match
    function updateMethod() {
        var delivery = document.querySelector('input[name="method"]:checked').value === 'Delivery';
        document.getElementById('addressBox').style.display = delivery ? 'block' : 'none';
        document.getElementById('whenTitle').textContent = delivery ? 'Delivery' : 'Pickup';
    }
    document.querySelectorAll('input[name="method"]').forEach(function (r) {
        r.addEventListener('change', updateMethod);
    });
    updateMethod();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
