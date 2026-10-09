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

$omHome = '/InfoMan-Project/index.php';
$omSave = '/InfoMan-Project/transaction/order_mode/order_mode_save.php';

if (isset($_GET['edit'])) {
    unset($_SESSION['pending_add']);
}

$om = $_SESSION['checkout'] ?? null;

$omChosen = $om && in_array(
    $om['method'] ?? '',
    ['Pickup', 'Delivery'],
    true
);

$omOld = false;

if ($omChosen) {
    [$omWhen, $omErr] = validateSchedule(
        $om['date'] ?? '',
        $om['time'] ?? ''
    );

    $omOld = (bool) $omErr;
}

$omStep = 0;
$omMethod = '';

if (isset($_GET['om'])) {

    $omStep = 1;

    if (
        $_GET['om'] === '2' &&
        in_array($_GET['method'] ?? '', ['Pickup', 'Delivery'], true)
    ) {
        $omStep = 2;
        $omMethod = $_GET['method'];
    }
}

$omErrors = $_SESSION['om_errors'] ?? [];
$omForm = $_SESSION['om_form'] ?? null;

unset($_SESSION['om_errors']);
unset($_SESSION['om_form']);

$omVal = [
    'address' => '',
    'date'    => '',
    'time'    => ''
];

if ($omForm) {

    $omVal = array_merge($omVal, $omForm);

} elseif ($omChosen && $om['method'] === $omMethod) {

    $omVal = [
        'address' => $om['address'] ?? '',
        'date'    => $om['date'] ?? '',
        'time'    => $om['time'] ?? ''
    ];
}

$omSlots = timeSlots();

$omMin = date('Y-m-d');

$omMax = date(
    'Y-m-d',
    strtotime('+' . MAX_DAYS_AHEAD . ' days')
);

?>

<style>

#omBar {
    max-width: 876px;
    margin: 0 auto 16px;
}

#omBar p {
    margin: 0;
    padding: 12px 16px;
    background: #FFF3DC;
    border: 1px solid #E2B59A;
    border-radius: 12px;
    color: #4a3b30;
    line-height: 1.5;
}

#omBar strong {
    color: #B77466;
}

#omEdit {
    display: inline-block;
    margin-top: 6px;
    padding: 4px 16px;
    background: #B77466;
    color: #fff;
    border-radius: 20px;
    text-decoration: none;
    font-weight: 600;
}

#omEdit:hover {
    background: #A97A70;
}

#omOverlay {
    position: fixed;
    inset: 0;
    z-index: 1060;
    display: flex;
    align-items: center;
    justify-content: center;
}

#omBackdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, .55);
}

#omPopup {
    position: relative;
    width: min(520px, calc(100% - 24px));
    max-height: 92vh;
    overflow-y: auto;
    background: #fff;
    border-radius: 18px;
    padding: 22px 24px;
    color: #4a3b30;
    box-shadow: 0 10px 40px rgba(0, 0, 0, .35);
}

#omClose {
    float: right;
    color: #957C62;
    font-size: 14px;
    text-decoration: none;
}

#omClose:hover {
    color: #B3382A;
}

#omPopup h3 {
    clear: both;
    margin: 0 0 6px;
    padding-bottom: 8px;
    border-bottom: 4px solid #000;
    font-weight: 800;
    letter-spacing: .5px;
}

#omPopup .omSub {
    color: #957C62;
    margin: 8px 0 14px;
}

.omOption {
    display: block;
    border: 3px solid #A8A8A8;
    border-radius: 40px;
    padding: 12px 20px;
    margin-bottom: 12px;
    color: #4a3b30;
    text-decoration: none;
}

.omOption:hover,
.omOption.omOn {
    border-color: #B77466;
    background: #FFF3DC;
}

.omOption b {
    display: block;
    font-size: 18px;
    letter-spacing: .5px;
}

.omOption span {
    font-size: 14px;
    color: #957C62;
}

.omOption em {
    font-style: normal;
    font-size: 12px;
    color: #B77466;
    margin-left: 6px;
}

#omPopup label {
    font-weight: 600;
    margin-bottom: 4px;
}

#omPopup textarea,
#omPopup input[type=date],
#omPopup select {
    width: 100%;
    padding: 8px 10px;
    border: 2px solid #E2B59A;
    border-radius: 10px;
    background: #fff;
    font: inherit;
}

#omPopup textarea:focus,
#omPopup input:focus,
#omPopup select:focus {
    outline: none;
    border-color: #B77466;
}

#omErrors {
    background: #FDE8E4;
    border: 1px solid #D98B7E;
    border-radius: 10px;
    padding: 8px 12px;
    color: #B3382A;
}

#omConfirm {
    width: 100%;
    padding: 12px;
    border: 0;
    border-radius: 30px;
    background: #B77466;
    color: #fff;
    font-weight: 700;
    letter-spacing: .5px;
    cursor: pointer;
}

#omConfirm:hover {
    background: #A97A70;
}

#omBack {
    display: block;
    text-align: center;
    color: #957C62;
}

</style>


<?php if ($omChosen): ?>

    <div id="omBar">

        <p>

            <strong>
                <?= $om['method'] === 'Delivery' ? 'Delivery' : 'Pick-up' ?>
            </strong>

            -
            <?= h(
                date(
                    'D, M j \a\t g:i A',
                    strtotime(
                        ($om['date'] ?? '') . ' ' . ($om['time'] ?? '')
                    )
                )
            ) ?>

            <?php if (
                $om['method'] === 'Delivery' &&
                ($om['address'] ?? '') !== ''
            ): ?>

                <br>
                Address:
                <?= h($om['address']) ?>

            <?php endif; ?>


            <?php if ($omOld): ?>

                <br>
                <strong>
                    This time can no longer be used.
                    Please press Edit and choose a new one.
                </strong>

            <?php endif; ?>


            <br>

            <a
                id="omEdit"
                href="<?= $omHome ?>?om=2&method=<?= h($om['method']) ?>&edit=1"
            >
                Edit
            </a>

        </p>

    </div>

<?php endif; ?>


<?php if ($omStep > 0): ?>

    <div id="omOverlay">

        <a
            id="omBackdrop"
            href="<?= $omSave ?>?cancel=1"
            aria-label="Close"
        ></a>


        <div id="omPopup">

            <p>
                <a
                    id="omClose"
                    href="<?= $omSave ?>?cancel=1"
                >
                    X
                </a>
            </p>


            <?php if ($omStep === 1): ?>

                <h3>
                    <?= h($omText['title']) ?>
                </h3>

                <p class="omSub">
                    <?= h($omText['subtitle']) ?>
                </p>


                <a
                    id="omPickup"
                    class="omOption <?= (
                        $omChosen &&
                        $om['method'] === 'Pickup'
                    ) ? 'omOn' : '' ?>"
                    href="<?= $omHome ?>?om=2&method=Pickup"
                >

                    <b>

                        <?= h($omText['pickup_title']) ?>

                        <?php if (
                            $omChosen &&
                            $om['method'] === 'Pickup'
                        ): ?>

                            <em>(current choice)</em>

                        <?php endif; ?>

                    </b>

                    <span>
                        <?= h($omText['pickup_desc']) ?>
                    </span>

                </a>


                <a
                    id="omDelivery"
                    class="omOption <?= (
                        $omChosen &&
                        $om['method'] === 'Delivery'
                    ) ? 'omOn' : '' ?>"
                    href="<?= $omHome ?>?om=2&method=Delivery"
                >

                    <b>

                        <?= h($omText['delivery_title']) ?>

                        <?php if (
                            $omChosen &&
                            $om['method'] === 'Delivery'
                        ): ?>

                            <em>(current choice)</em>

                        <?php endif; ?>

                    </b>

                    <span>
                        <?= h($omText['delivery_desc']) ?>
                    </span>

                </a>


            <?php else: ?>


                <form
                    method="POST"
                    action="<?= $omSave ?>"
                >

                    <input
                        type="hidden"
                        name="method"
                        value="<?= h($omMethod) ?>"
                    >


                    <h3>
                        <?= $omMethod === 'Delivery'
                            ? 'DELIVERY'
                            : 'PICK-UP'
                        ?>
                    </h3>


                    <?php if ($omMethod === 'Delivery'): ?>

                        <p>

                            <label for="omAddress">
                                Delivery address
                            </label>

                            <br>

                            <textarea
                                id="omAddress"
                                name="address"
                                rows="2"
                                maxlength="255"
                                required
                                placeholder="House/Unit no., Street, Barangay, City"
                            ><?= h($omVal['address']) ?></textarea>

                        </p>

                    <?php endif; ?>


                    <p>

                        <label for="omDate">

                            <?= $omMethod === 'Delivery'
                                ? 'Preferred date'
                                : 'Date'
                            ?>

                        </label>

                        <br>

                        <input
                            type="date"
                            id="omDate"
                            name="date"
                            required
                            min="<?= $omMin ?>"
                            max="<?= $omMax ?>"
                            value="<?= h($omVal['date']) ?>"
                        >

                    </p>


                    <p>

                        <label for="omTime">

                            <?= $omMethod === 'Delivery'
                                ? 'Preferred time'
                                : 'Time'
                            ?>

                        </label>

                        <br>

                        <select
                            id="omTime"
                            name="time"
                            required
                        >

                            <option value="">
                                Choose a time
                            </option>

                            <?php foreach ($omSlots as $v => $l): ?>

                                <option
                                    value="<?= $v ?>"
                                    <?= $omVal['time'] === $v
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    <?= $l ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </p>


                    <?php if ($omErrors): ?>

                        <p id="omErrors">

                            <?php foreach ($omErrors as $e): ?>

                                <strong>
                                    <?= h($e) ?>
                                </strong>

                                <br>

                            <?php endforeach; ?>

                        </p>

                    <?php endif; ?>


                    <p>

                        <button
                            type="submit"
                            id="omConfirm"
                        >

                            <?= $omMethod === 'Delivery'
                                ? 'CONFIRM'
                                : 'CONFIRM PICK-UP DATE AND TIME'
                            ?>

                        </button>

                    </p>


                    <p>

                        <a
                            id="omBack"
                            href="<?= $omHome ?>?om=1"
                        >
                            Change order type
                        </a>

                    </p>

                </form>

            <?php endif; ?>

        </div>

    </div>

<?php endif; ?>