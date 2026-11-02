<?php
session_start();
include("../includes/config.php");

$error = null;
$errors = [];          // one message per field
$email = '';           // keeps what the user typed
$success = $_SESSION['success'] ?? null;   // set by store.php after registering
unset($_SESSION['success']);

if (isset($_POST['submit'])) {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ---- PHP validation ----
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email format is not valid.';
    }
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    if (empty($errors)) {
        $sql = "SELECT customer_id, first_name, email, password_hash, account_status, role
        FROM tbl_customers WHERE email = ? LIMIT 1";
        $result = mysqli_execute_query($conn, $sql, [$email]);

        if ($result->num_rows === 1) {
            $row = mysqli_fetch_assoc($result);

            if (!password_verify($password, $row['password_hash'])) {
                $error = 'wrong email or password';
            } elseif (strtolower($row['account_status']) !== 'active') {
                $error = 'this account is not active';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $row['customer_id'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['email']      = $row['email'];

                if ($row['role'] === 'admin') {
                    // admin: same session names the admin pages already use
                    unset($_SESSION['customer_id'], $_SESSION['cart_products']);
                    $_SESSION['admin_id']   = $row['customer_id'];
                    $_SESSION['admin_name'] = $row['first_name'];
                    $_SESSION['role']       = 'admin';
                    header("Location: ../admin/home.php");
                    exit();
                }

                // customer
                $_SESSION['customer_id'] = $row['customer_id'];
                $_SESSION['role']        = 'customer';
                $goTo = $_SESSION['return_to'] ?? '../index.php';
                unset($_SESSION['return_to']);
                header("Location: " . $goTo);
                exit();
            }
        } else {
            $error = 'wrong email or password';
        }
    }
}

// header.php outputs HTML, so it must come AFTER any header("Location: ...")
include("../includes/head.php");

?>
<link rel="stylesheet" href="/InfoMan-Project/includes/style/auth.css?v=<?php echo time(); ?>">

<div class="auth-page">

    <a href="/InfoMan-Project/index.php" class="auth-back">
        <i class="fa-solid fa-arrow-left"></i> Back to home
    </a>

    <div class="auth-card">

        <div class="auth-form-wrap">
            <h1>Sign in</h1>
            <p class="auth-sub">Welcome back! Please enter your details.</p>

            <?php if ($success): ?>
                <div class="auth-alert success" role="alert"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="auth-alert danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="" method="POST">

                <!-- Email input -->
                <div class="auth-field <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
                    <label for="form2Example1">Email address</label>
                    <div class="auth-input">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="text" id="form2Example1" name="email" placeholder="you@example.com"
                            value="<?php echo htmlspecialchars($email); ?>" />
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <div class="auth-error"><?php echo htmlspecialchars($errors['email']); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Password input -->
                <div class="auth-field <?php echo isset($errors['password']) ? 'has-error' : ''; ?>">
                    <label for="form2Example2">Password</label>
                    <div class="auth-input">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="form2Example2" name="password" placeholder="Enter your password" />
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="auth-error"><?php echo htmlspecialchars($errors['password']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="auth-forgot">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>

                <!-- Submit button -->
                <button type="submit" class="auth-btn" name="submit">Sign in</button>

                <p class="auth-switch">Not a member? <a href="register.php">Register</a></p>

            </form>
        </div>

        <div class="auth-brand">
            <div class="auth-logo"><i class="fa-solid fa-store"></i></div>
            <div class="auth-store">Kurdiella's Baguio Treasures</div>
            <h2>Welcome back!</h2>
            <p>Good to see you again. Sign in to order your favorite Baguio pasalubong and track your orders.</p>
            <ul>
                <li><i class="fa-solid fa-basket-shopping"></i> Easy ordering</li>
                <li><i class="fa-solid fa-truck-fast"></i> Pick-up or delivery</li>
                <li><i class="fa-solid fa-receipt"></i> Receipts sent to your email</li>
            </ul>
        </div>

    </div>
</div>