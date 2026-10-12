<?php
include('../auth.php');
include('../../includes/config.php');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$customer_id = (int)($_GET['id'] ?? $_POST['customer_id'] ?? 0);

// ---------- load the customer ----------
$result = mysqli_execute_query(
    $conn,
    "SELECT customer_id, first_name, last_name, email, contact_number, account_status
     FROM tbl_customers WHERE customer_id = ? LIMIT 1",
    [$customer_id]
);
$customer = mysqli_fetch_assoc($result);

if (!$customer) {
    $_SESSION['error'] = 'Customer not found.';
    header("Location: user.php");
    exit();
}

$error = null;

// ---------- save changes (runs BEFORE any HTML is printed) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name     = trim(strip_tags($_POST['first_name'] ?? ''));
    $last_name      = trim(strip_tags($_POST['last_name'] ?? ''));
    $email          = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $account_status = $_POST['account_status'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif ($first_name === '' || $last_name === '') {
        $error = 'First name and last name are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalid format.';
    } elseif (!preg_match('/^(09\d{9}|\+639\d{9})$/', $contact_number)) {
        $error = 'Contact number should look like 09123456789.';
    } elseif (!in_array($account_status, ['Active', 'Inactive'], true)) {
        $error = 'Invalid account status.';
    } else {
        try {
            // email already used by another customer?
            $check = mysqli_execute_query(
                $conn,
                "SELECT customer_id FROM tbl_customers WHERE email = ? AND customer_id <> ? LIMIT 1",
                [$email, $customer_id]
            );

            if (mysqli_num_rows($check) > 0) {
                $error = 'That email is already used by another customer.';
            } else {
                mysqli_execute_query(
                    $conn,
                    "UPDATE tbl_customers
                     SET first_name = ?, last_name = ?, email = ?, contact_number = ?, account_status = ?
                     WHERE customer_id = ?",
                    [$first_name, $last_name, $email, $contact_number, $account_status, $customer_id]
                );

                $_SESSION['success'] = 'Customer details updated.';
                header("Location: user.php");
                exit();
            }
        } catch (mysqli_sql_exception $e) {
            $error = ($_SERVER['SERVER_NAME'] === 'localhost')
                ? 'DB error: ' . $e->getMessage()
                : 'Something went wrong, please try again.';
        }
    }

    // something failed: show what was typed instead of the saved values
    $customer['first_name']     = $first_name;
    $customer['last_name']      = $last_name;
    $customer['email']          = $email;
    $customer['contact_number'] = $contact_number;
    $customer['account_status'] = $account_status;
}

$pageTitle = 'Edit customer';
include('../../includes/adminSidebar.php');
?>

<div class="admin-container">

    <div class="admin-toolbar">
        <h2>Edit customer #<?= (int)$customer['customer_id'] ?></h2>
    </div>

    <div class="table-card" style="padding: 30px;">

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="customer_form.php?id=<?= (int)$customer['customer_id'] ?>" method="POST">

            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="customer_id" value="<?= (int)$customer['customer_id'] ?>">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">First name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name"
                        value="<?= htmlspecialchars($customer['first_name']) ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name"
                        value="<?= htmlspecialchars($customer['last_name']) ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email"
                    value="<?= htmlspecialchars($customer['email']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="contact_number" class="form-label">Contact number</label>
                <input type="tel" class="form-control" id="contact_number" name="contact_number"
                    placeholder="09123456789"
                    value="<?= htmlspecialchars($customer['contact_number'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label for="account_status" class="form-label">Account status</label>
                <select class="form-select" id="account_status" name="account_status" required>
                    <?php foreach (['Active', 'Inactive'] as $option): ?>
                        <option value="<?= $option ?>"
                            <?= strtolower($customer['account_status']) === strtolower($option) ? 'selected' : '' ?>>
                            <?= $option ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Inactive customers cannot sign in.</div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn-add" style="border: none; cursor: pointer;">
                    Update customer
                </button>

                <a href="user.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>

    </div>

</div>

</main>
</body>

</html>