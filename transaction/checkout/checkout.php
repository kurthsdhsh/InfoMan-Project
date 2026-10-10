<?php

session_start();
include('../../includes/config.php');
require_once __DIR__ . '/../../includes/order_helpers.php';

$coPage = '/InfoMan-Project/transaction/checkout/checkout.php';
$coSave  = '/InfoMan-Project/transaction/checkout/checkout_save.php';
$coPlace = '/InfoMan-Project/transaction/place_order/place_order.php';

// login muna sila
if (!isset($_SESSION['customer_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    $_SESSION['return_to'] = $coPage;
    requireCustomer();   // login page
}

$notices = [];
foreach (($_SESSION['cart_products'] ?? []) as $id => $item) {
    $r = mysqli_execute_query($conn, "SELECT product_name, stock_quantity, product_status FROM tbl_products WHERE product_id = ? LIMIT 1", [(int) $id]);
    $p = mysqli_fetch_assoc($r);
    if (!$p || $p['product_status'] !== 'Active' || (int) $p['stock_quantity'] < 1) {
        $notices[] = ($p['product_name'] ?? 'A product') . ' is no longer available and was removed from your basket.';
        unset($_SESSION['cart_products'][$id]);
    } elseif ((int) $item['item_qty'] > (int) $p['stock_quantity']) {
        $notices[] = 'Only ' . (int) $p['stock_quantity'] . ' left of ' . $p['product_name'] . '. The quantity was changed.';
        $_SESSION['cart_products'][$id]['item_qty'] = (int) $p['stock_quantity'];
    }
}

// basket not empty
$lines = getCartLines($conn);
if (!$lines) {
    $_SESSION['message'] = 'Your basket is empty. Add a product to check out.';
    header('Location: /InfoMan-Project/index.php');
    exit();
}

$res = mysqli_execute_query(
    $conn,
    "SELECT first_name, last_name, email, contact_number FROM tbl_customers WHERE customer_id = ? LIMIT 1",
    [(int) $_SESSION['customer_id']]
);
$me = mysqli_fetch_assoc($res);

$draft = $_SESSION['checkout'] ?? [];
$same  = $draft['same'] ?? true;   
$rName = $draft['recipient_name'] ?? '';
$rPhone = $draft['recipient_phone'] ?? '';
$note  = $draft['note'] ?? '';

$coTotal = cartTotal($lines);

$coErrors = $_SESSION['co_errors'] ?? [];
unset($_SESSION['co_errors']);
$_SESSION['place_token'] = bin2hex(random_bytes(16));

$omHome    = $coPage;       
$omReturn  = 'checkout';   
$omHideBar = true;          
include('../order_mode/order_mode.php');   

$coDelivery = $omChosen && $om['method'] === 'Delivery';
$coFee      = ($coDelivery && isset($omAreas[(int) ($om['area_id'] ?? 0)])) ? $omAreas[(int) $om['area_id']]['fee'] : 0.0;
$coGrand    = $coTotal + $coFee;   
$coCanPay   = $omChosen && !$omOld;   

include('../../includes/header.php');
?>

<style>
#coWrap { max-width: 1100px; margin: 0 auto; padding: 0 12px 40px; display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start; color: #4a3b30; }
#coLeft { flex: 1 1 480px; min-width: 0; }
#coRight { flex: 1 1 340px; min-width: 0; border: 3px solid #4a3b30; border-radius: 14px; padding: 20px; background: #fff; }
#coWrap h2 { font-size: 20px; font-weight: 800; letter-spacing: .5px; margin: 0 0 12px; padding-bottom: 8px; border-bottom: 3px solid #E2B59A; }
.coBox { margin-bottom: 28px; }
.coBox p { margin: 0 0 8px; }
.coRow { display: flex; flex-wrap: wrap; gap: 12px; }
.coRow > div { flex: 1 1 180px; }
.coLabel { display: block; font-weight: 600; margin-bottom: 4px; }
.coText { padding: 8px 12px; background: #FFF3DC; border: 1px solid #E2B59A; border-radius: 10px; }
#coWrap input[type=text], #coWrap textarea { width: 100%; padding: 8px 10px; border: 2px solid #E2B59A; border-radius: 10px; font: inherit; }
#coWrap input:focus, #coWrap textarea:focus { outline: none; border-color: #B77466; }
.coLink { color: #B77466; font-weight: 600; }
.coWarn { color: #B3382A; font-weight: 700; }
#coSame:checked ~ #coRecipient { display: none; }
#coRecipient { margin-top: 10px; }
#coCart { width: 100%; border-collapse: collapse; }
#coCart th { text-align: left; font-size: 13px; letter-spacing: .5px; color: #957C62; padding: 6px 4px; border-bottom: 3px solid #E2B59A; }
#coCart td { padding: 8px 4px; border-bottom: 1px solid #FFE3B0; vertical-align: middle; }
#coCart { table-layout: fixed; }
#coCart th:nth-child(2), #coCart td:nth-child(2) { width: 112px; }
#coCart th:nth-child(3), #coCart td:nth-child(3) { width: 84px; }
#coCart th:nth-child(4), #coCart td:nth-child(4) { width: 32px; }
#coCart .coQty { white-space: nowrap; }
#coCart .coQty span { display: inline-block; min-width: 26px; text-align: center; font-weight: 700; }
#coCart .coQty button { width: 28px; min-width: 0; height: 28px; min-height: 0; padding: 0; border: 2px solid #E2B59A; border-radius: 50%; background: #fff; color: #B77466; font: 700 16px/1 Arial, sans-serif; text-shadow: none; cursor: pointer; }
#coCart .coQty button:hover:not(:disabled) { background: #FFF3DC; }
#coCart .coQty button:disabled { opacity: .35; cursor: not-allowed; }
#coCart .coTrash { width: 28px; min-width: 0; min-height: 0; padding: 0; border: 0; background: none; color: #957C62; font: 24px/1 Arial, sans-serif; text-shadow: none; cursor: pointer; }
#coCart .coTrash:hover { background: none; color: #B3382A; }
.coHidden { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
.coRight { text-align: right; }
#coTotals { margin: 14px 0; }
#coTotals div { display: flex; justify-content: space-between; padding: 4px 0; }
#coTotals .coGrand { font-size: 20px; font-weight: 800; border-top: 3px solid #E2B59A; margin-top: 6px; padding-top: 8px; }
#coPay { display: block; width: 100%; margin-top: 14px; padding: 12px; border: 0; border-radius: 30px; background: #B77466; color: #fff; font-weight: 700; letter-spacing: .5px; }
#coPay:disabled { background: #cfc3bd; cursor: not-allowed; }
.coSmall { font-size: 13px; color: #957C62; }
</style>

<h1 class="text-center my-4">Checkout</h1>

<?php foreach (array_merge($coErrors, $notices) as $n): ?>
    <p class="coWarn" style="max-width:1100px;margin:0 auto 10px;padding:0 12px"><?= h($n) ?></p>
<?php endforeach; ?>

<form method="POST" action="<?= $coSave ?>">
<button type="submit" name="cart" value="none" class="coHidden" tabindex="-1" aria-hidden="true">Save</button>
<input type="hidden" name="token" value="<?= h($_SESSION['place_token']) ?>">
<div id="coWrap">

    <!-- LEFT SIDE -->
    <div id="coLeft">

        <!-- ORDER MODE -->
        <div class="coBox" id="coMode">
            <h2>1. Order type</h2>
            <?php if ($omChosen): ?>
                <p class="coText">
                    <strong><?= $om['method'] === 'Delivery' ? 'Delivery' : 'Pick-up' ?></strong>
                    - <?= h(scheduleText($om['date'] ?? '', $om['time'] ?? '')) ?>
                    <?php if ($om['method'] === 'Delivery'): ?>
                        <br>Address: <?= h($om['address'] ?? '') ?><?= isset($omAreas[(int) ($om['area_id'] ?? 0)]) ? ', ' . h($omAreas[(int) $om['area_id']]['name']) : '' ?>
                    <?php endif; ?>
                </p>
                <?php if ($omOld): ?>
                    <p class="coWarn"><?= h($omOldMsg) ?></p>
                <?php endif; ?>
                <p><a id="coModeEdit" class="coLink" href="<?= $coPage ?>?om=2&amp;method=<?= h($om['method']) ?>&amp;edit=1">Edit</a></p>
            <?php else: ?>
                <p class="coWarn">You have not chosen Pick-up or Delivery yet.</p>
                <p><a id="coModeEdit" class="coLink" href="<?= $coPage ?>?om=1&amp;edit=1">Choose Pick-up or Delivery</a></p>
            <?php endif; ?>
        </div>

        <!-- BILLING DETAILS  -->
        <div class="coBox" id="coBilling">
            <h2>2. Billing details</h2>
            <div class="coRow">
                <div><span class="coLabel">Name</span><div class="coText"><?= h($me['first_name'] . ' ' . $me['last_name']) ?></div></div>
                <div><span class="coLabel">Phone number</span><div class="coText"><?= h($me['contact_number']) ?></div></div>
            </div>
            <p style="margin-top:10px"><span class="coLabel">Email address</span><span class="coText" style="display:block"><?= h($me['email']) ?></span></p>
            <p class="coSmall">To change these, go to your profile. <a id="coProfile" class="coLink" href="/InfoMan-Project/user/profile.php">Edit profile</a></p>
        </div>

        <!-- RECIPIENT / PERSON WHO WILL PICK UP -->
        <div class="coBox" id="coRecipientBox">
            <h2>3. <?= ($om['method'] ?? '') === 'Delivery' ? 'Recipient' : 'Person who will pick up' ?></h2>
            <input type="checkbox" id="coSame" name="same" value="1" <?= $same ? 'checked' : '' ?>>
            <label for="coSame">Same as customer information</label>

            <div id="coRecipient">
                <p class="coSmall">Someone else will receive this order? Fill in their details.</p>
                <p><label class="coLabel" for="coRName">Full name</label>
                    <input type="text" id="coRName" name="recipient_name" maxlength="120" value="<?= h($rName) ?>"></p>
                <p><label class="coLabel" for="coRPhone">Phone number</label>
                    <input type="text" id="coRPhone" name="recipient_phone" maxlength="25" placeholder="09123456789" value="<?= h($rPhone) ?>"></p>
            </div>
        </div>

        <!-- NOTE TO THE STORE -->
        <div class="coBox" id="coNoteBox">
            <h2>4. Note to the store (optional)</h2>
            <textarea id="coNote" name="note" rows="3" maxlength="500" placeholder="Anything the store should know?"><?= h($note) ?></textarea>
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div id="coRight">
        <h2>Your order</h2>
        <table id="coCart">
            <thead><tr><th>Product</th><th>Qty</th><th class="coRight">Total</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($lines as $l): ?>
                <tr>
                    <td>
                        <?= h($l['name']) ?><br><span class="coSmall"><?= peso($l['unit_price']) ?> each</span>
                        <?php if ($l['qty'] > $l['stock']): ?><br><span class="coWarn coSmall">Only <?= (int) $l['stock'] ?> left</span><?php endif; ?>
                    </td>
                    <td class="coQty">
                        <button type="submit" name="cart" value="minus:<?= $l['product_id'] ?>" <?= $l['qty'] <= 1 ? 'disabled' : '' ?> aria-label="Less">&minus;</button>
                        <span><?= $l['qty'] ?></span>
                        <button type="submit" name="cart" value="plus:<?= $l['product_id'] ?>" <?= $l['qty'] >= $l['stock'] ? 'disabled' : '' ?> aria-label="More">+</button>
                    </td>
                    <td class="coRight"><?= peso($l['line_total']) ?></td>
                    <td><button type="submit" name="cart" value="remove:<?= $l['product_id'] ?>" class="coTrash" aria-label="Remove <?= h($l['name']) ?>">&times;</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div id="coTotals">
            <div><span>Subtotal</span><span><?= peso($coTotal) ?></span></div>
            <?php if ($coDelivery): ?>
                <div><span>Delivery fee</span><span><?= peso($coFee) ?></span></div>
            <?php endif; ?>
            <div class="coGrand"><span>Total</span><span><?= peso($coGrand) ?></span></div>
            <div class="coSmall"><span>Payment</span><span>Cash (on pick-up or delivery)</span></div>
        </div>

        <button type="submit" id="coPay" formaction="<?= $coPlace ?>" <?= $coCanPay ? '' : 'disabled' ?>>Place order</button>
        <p class="coSmall" style="text-align:center;margin-top:8px"><?= $coCanPay ? 'You pay in cash when you pick up or when it is delivered.' : 'Please complete your order type first.' ?></p>
        <p style="text-align:center"><a id="coBackCart" class="coLink" href="/InfoMan-Project/index.php">Add more items</a></p>
    </div>

</div>
</form>

<?php include('../../includes/footer.php'); ?>
