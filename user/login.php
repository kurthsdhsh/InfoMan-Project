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
include("../includes/header.php");

?>
<div class="row col-md-8 mx-auto ">
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Email input -->
        <div class="form-outline mb-4">
           <input type="text" id="form2Example1" class="form-control" name="email"
            value="<?php echo htmlspecialchars($email); ?>" />
            <?php if (isset($errors['email'])): ?><div class="text-danger small"><?php echo $errors['email']; ?></div><?php endif; ?>
            <label class="form-label" for="form2Example1">Email address</label>
        </div>

        <!-- Password input -->
        <div class="form-outline mb-4">
            <input type="password" id="form2Example2" class="form-control" name="password" />
            <?php if (isset($errors['password'])): ?><div class="text-danger small"><?php echo $errors['password']; ?></div><?php endif; ?>
            <label class="form-label" for="form2Example2">Password</label>
        </div>

        
            <p class="form-outline mb-4">
                <a href="forgot_password.php" style="font-size: 12px;">Forgot Password?</a>
            </p>

        

        <!-- Submit button -->
        <button type="submit" class="btn btn-primary btn-block mb-4" name="submit">Sign in</button>

        <!-- Register buttons -->
        <div class="text-center">
            <p>Not a member? <a href="register.php">Register</a></p>
        </div>

    </form>
</div>