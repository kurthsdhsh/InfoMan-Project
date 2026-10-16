<?php
include('../auth.php');
include('../../includes/config.php');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$errors = [];
$old = ['first_name' => '', 'last_name' => '', 'email' => '', 'contact_number' => '', 'role' => 'customer'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['first_name']     = trim(strip_tags($_POST['first_name'] ?? ''));
    $old['last_name']      = trim(strip_tags($_POST['last_name'] ?? ''));
    $old['email']          = trim($_POST['email'] ?? '');
    $old['contact_number'] = trim($_POST['contact_number'] ?? '');
    $old['role']           = $_POST['role'] ?? '';
    $password              = trim($_POST['password'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request. Please try again.';
    }
    if ($old['first_name'] === '') {
        $errors['first_name'] = 'First name is required.';
    }
    if ($old['last_name'] === '') {
        $errors['last_name'] = 'Last name is required.';
    }
    if ($old['email'] === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email format is not valid.';
    } else {
        $check = mysqli_execute_query($conn, "SELECT customer_id FROM tbl_customers WHERE email = ? LIMIT 1", [$old['email']]);
        if (mysqli_num_rows($check) > 0) {
            $errors['email'] = 'That email is already used.';
        }
    }
    if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $old['contact_number'])) {
        $errors['contact_number'] = 'Contact number should look like 09123456789.';
    }
    if (strlen($password) < 6) {
        $errors['password'] = 'Password should be at least 6 characters.';
    }
    if (!in_array($old['role'], ['customer', 'admin'], true)) {
        $errors['role'] = 'Invalid role.';
    }

    if (empty($errors)) {
        try {
            mysqli_execute_query(
                $conn,
                "INSERT INTO tbl_customers (first_name, last_name, email, password_hash, contact_number, account_status, role)
                 VALUES (?, ?, ?, ?, ?, 'Active', ?)",
                [$old['first_name'], $old['last_name'], $old['email'],
                 password_hash($password, PASSWORD_BCRYPT), $old['contact_number'], $old['role']]
            );
            $_SESSION['success'] = 'User added.';
            header("Location: user.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            $errors['general'] = 'Something went wrong, please try again.';
        }
    }
}

$pageTitle = 'Add user';
include('../../includes/adminSidebar.php');
?>

<div class="admin-container">

    <div class="admin-toolbar">
        <h2>Add user</h2>
    </div>

    <div class="table-card" style="padding: 30px;">

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form action="user_add.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">First name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name"
                        value="<?= htmlspecialchars($old['first_name']) ?>">
                    <?php if (isset($errors['first_name'])): ?><div class="text-danger small"><?= $errors['first_name'] ?></div><?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name"
                        value="<?= htmlspecialchars($old['last_name']) ?>">
                    <?php if (isset($errors['last_name'])): ?><div class="text-danger small"><?= $errors['last_name'] ?></div><?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="text" class="form-control" id="email" name="email"
                    value="<?= htmlspecialchars($old['email']) ?>">
                <?php if (isset($errors['email'])): ?><div class="text-danger small"><?= $errors['email'] ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="contact_number" class="form-label">Contact number</label>
                <input type="text" class="form-control" id="contact_number" name="contact_number"
                    placeholder="09123456789" value="<?= htmlspecialchars($old['contact_number']) ?>">
                <?php if (isset($errors['contact_number'])): ?><div class="text-danger small"><?= $errors['contact_number'] ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password">
                <?php if (isset($errors['password'])): ?><div class="text-danger small"><?= $errors['password'] ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role">
                    <?php foreach (['customer', 'admin'] as $option): ?>
                        <option value="<?= $option ?>" <?= $old['role'] === $option ? 'selected' : '' ?>>
                            <?= ucfirst($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['role'])): ?><div class="text-danger small"><?= $errors['role'] ?></div><?php endif; ?>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn-add" style="border: none; cursor: pointer;">Add user</button>
                <a href="user.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

    </div>

</div>

</main>
</body>

</html>