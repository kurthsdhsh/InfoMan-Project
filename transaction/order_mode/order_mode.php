<?php

require_once __DIR__ . '/../../includes/order_helpers.php';

$omText = [
    'title'          => 'Choose Order Type',
    'subtitle'       => 'How would you like to receive your order?',
    'pickup_title'   => 'PICK-UP',
    'pickup_desc'    => 'Get it at our store and pay cash there.',
    'delivery_title' => 'DELIVERY',
    'delivery_desc'  => 'Our driver brings it to you. Pay cash on delivery.',
];


$omHome    = $omHome ?? '/InfoMan-Project/index.php';
$omReturn  = $omReturn ?? '';
$omHideBar = $omHideBar ?? false;
$omSave = '/InfoMan-Project/transaction/order_mode/order_mode_save.php';

if (isset($_GET['edit'])) {
    unset($_SESSION['pending_add']);
}

if (!isset($conn)) {
    include __DIR__ . '/../../includes/config.php';
}
$omAreas = areaList($conn);   

$om       = $_SESSION['checkout'] ?? null;
$omChosen = $om && in_array($om['method'] ?? '', ['Pickup', 'Delivery'], true);
$omOld    = false;   
$omOldMsg = 'This time can no longer be used. Please press Edit and choose a new one.';
if ($omChosen) {
    [$omWhen, $omErr] = validateChoice($om['date'] ?? '', $om['time'] ?? '');
    $omOld = (bool) $omErr;
    
    if (!$omOld && $om['method'] === 'Delivery' && !isset($omAreas[(int) ($om['area_id'] ?? 0)])) {
        $omOld    = true;
        $omOldMsg = 'Please press Edit and choose your delivery city.';
    }
}

$omStep   = 0;
$omMethod = '';
if (isset($_GET['om'])) {
    $omStep = 1;
    if ($_GET['om'] === '2' && in_array($_GET['method'] ?? '', ['Pickup', 'Delivery'], true)) {
        $omStep   = 2;
        $omMethod = $_GET['method'];
    }
}

$omErrors = $_SESSION['om_errors'] ?? [];
$omForm   = $_SESSION['om_form'] ?? null;
unset($_SESSION['om_errors'], $_SESSION['om_form']);

$omVal = ['address' => '', 'area_id' => '', 'date' => '', 'time' => ''];
if ($omForm) {
    $omVal = array_merge($omVal, $omForm);
} elseif ($omChosen && $om['method'] === $omMethod) {
    $omVal = ['address' => $om['address'] ?? '', 'area_id' => $om['area_id'] ?? '', 'date' => $om['date'] ?? '', 'time' => $om['time'] ?? ''];
}

$omDays = availableDays();                       
$omDay  = $omVal['date'];                       
if (!isset($omDays[$omDay])) {
    $omDay = (string) array_key_first($omDays);  
}
$omSlots    = availableSlots($omDay);            
$omAsap     = ($omDay === date('Y-m-d')) && asapAvailable();   
$omTimeSel  = $omVal['time'];                    
if (!isset($omSlots[$omTimeSel]) && !($omTimeSel === 'ASAP' && $omAsap)) {
    $omTimeSel = $omAsap ? 'ASAP' : '';          
}
?>

<?php if ($omChosen && !$omHideBar): ?>
    <div id="omBar">
        <p>
            <strong><?= $om['method'] === 'Delivery' ? 'Delivery' : 'Pick-up' ?></strong>
            - <?= h(scheduleText($om['date'] ?? '', $om['time'] ?? '')) ?>
            <?php if ($om['method'] === 'Delivery' && ($om['address'] ?? '') !== ''): ?>
                <br>Address: <?= h($om['address']) ?><?= isset($omAreas[(int) ($om['area_id'] ?? 0)]) ? ', ' . h($omAreas[(int) $om['area_id']]['name']) : '' ?>
            <?php endif; ?>
            <?php if ($omOld): ?>
                <br><strong><?= h($omOldMsg) ?></strong>
            <?php endif; ?>
            <br>
            <a id="omEdit" href="<?= $omHome ?>?om=2&amp;method=<?= h($om['method']) ?>&amp;edit=1">Edit</a>
        </p>
    </div>
<?php endif; ?>

<?php if ($omStep > 0): ?>
    <div id="omOverlay">
    <a id="omBackdrop" href="<?= $omSave ?>?cancel=1&amp;return=<?= h($omReturn) ?>" aria-label="Close"></a>
    <div id="omPopup">
        <p><a id="omClose" href="<?= $omSave ?>?cancel=1&amp;return=<?= h($omReturn) ?>">X</a></p>

        <?php if ($omStep === 1): ?>
            <h3><?= h($omText['title']) ?></h3>
            <p class="omSub"><?= h($omText['subtitle']) ?></p>

            <a id="omPickup" class="omOption <?= ($omChosen && $om['method'] === 'Pickup') ? 'omOn' : '' ?>" href="<?= $omHome ?>?om=2&amp;method=Pickup">
                <b><?= h($omText['pickup_title']) ?><?= ($omChosen && $om['method'] === 'Pickup') ? '<em>(current choice)</em>' : '' ?></b>
                <span><?= h($omText['pickup_desc']) ?></span>
            </a>
            <a id="omDelivery" class="omOption <?= ($omChosen && $om['method'] === 'Delivery') ? 'omOn' : '' ?>" href="<?= $omHome ?>?om=2&amp;method=Delivery">
                <b><?= h($omText['delivery_title']) ?><?= ($omChosen && $om['method'] === 'Delivery') ? '<em>(current choice)</em>' : '' ?></b>
                <span><?= h($omText['delivery_desc']) ?></span>
            </a>

        <?php else: ?>
            <form method="POST" action="<?= $omSave ?>">
                <input type="hidden" name="method" value="<?= h($omMethod) ?>">
                <input type="hidden" name="return" value="<?= h($omReturn) ?>">
                <h3><?= $omMethod === 'Delivery' ? 'DELIVERY' : 'PICK-UP' ?></h3>

                <?php if ($omMethod === 'Delivery'): ?>
                    <p>
                        <label for="omArea">City (we deliver within Metro Manila only)</label><br>
                        <select id="omArea" name="area_id" required>
                            <option value="">Choose your city</option>
                            <?php foreach ($omAreas as $aid => $a): ?>
                                <option value="<?= $aid ?>" <?= (string) $omVal['area_id'] === (string) $aid ? 'selected' : '' ?>><?= h($a['name']) ?> - delivery fee <?= peso($a['fee']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label for="omAddress">Street address</label><br>
                        <textarea id="omAddress" name="address" rows="2" cols="40" maxlength="255" required
                            placeholder="House/Unit no., Street, Barangay"><?= h($omVal['address']) ?></textarea>
                    </p>
                <?php endif; ?>

                <p><span class="omLabel"><?= $omMethod === 'Delivery' ? 'Preferred day' : 'Day' ?></span></p>
                <?php if ($omDays): ?>
                    <p class="omDays">
                        <?php foreach ($omDays as $v => $l): ?>
                            <button type="submit" name="day" value="<?= $v ?>" formaction="<?= $omSave ?>" formnovalidate
                                class="omDay <?= $v === $omDay ? 'omOn' : '' ?>"><?= h($l) ?></button>
                        <?php endforeach; ?>
                    </p>
                    <input type="hidden" name="date" value="<?= h($omDay) ?>">
                    <p>
                        <label for="omTime"><?= $omMethod === 'Delivery' ? 'Preferred time' : 'Time' ?></label><br>
                        <select id="omTime" name="time" required>
                            <?php if ($omAsap): ?>
                                <option value="ASAP" <?= $omTimeSel === 'ASAP' ? 'selected' : '' ?>>ASAP - ready in about <?= h(asapText()) ?></option>
                            <?php endif; ?>
                            <option value="" <?= $omTimeSel === '' ? 'selected' : '' ?>>Choose a time</option>
                            <?php foreach ($omSlots as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $omTimeSel === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                <?php else: ?>
                    <p id="omErrors"><strong>Sorry, there are no free times right now. Please try again later.</strong></p>
                <?php endif; ?>

                <?php if ($omErrors): ?>
                    <p id="omErrors">
                        <?php foreach ($omErrors as $e): ?><strong><?= h($e) ?></strong><br><?php endforeach; ?>
                    </p>
                <?php endif; ?>

                <p><button type="submit" id="omConfirm"><?= $omMethod === 'Delivery' ? 'CONFIRM' : 'CONFIRM PICK-UP DATE AND TIME' ?></button></p>
                <p><a id="omBack" href="<?= $omHome ?>?om=1">Change order type</a></p>
            </form>
        <?php endif; ?>
    </div>
    </div>
<?php endif; ?>
