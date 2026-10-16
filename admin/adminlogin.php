<?php
session_start();
include("../includes/config.php");

$error = $_SESSION['message'] ?? null;   // message from auth.php
unset($_SESSION['message']);
$errors = [];
$email = '';
$success = $_SESSION['success'] ?? null;   // set by store.php after registering
unset($_SESSION['success']);

if (isset($_POST['submit'])) {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email format is not valid.';
    }
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

   
    if (empty($errors)) {

        $sql = "SELECT customer_id AS admin_id, first_name AS admin_name, email, password_hash, account_status
        FROM tbl_customers WHERE email = ? AND role = 'admin' LIMIT 1";
        $result = mysqli_execute_query($conn, $sql, [$email]);

        if ($result->num_rows === 1) {
            $row = mysqli_fetch_assoc($result);

            if (!password_verify($password, $row['password_hash'])) {
                $error = 'wrong email or password';
            } elseif (strtolower($row['account_status']) !== 'active') {
                $error = 'this account is not active';
            } else {
                session_regenerate_id(true);
                // remove any customer data left in the session from a customer login
                unset($_SESSION['customer_id'], $_SESSION['first_name'], $_SESSION['cart_products']);

                $_SESSION['user_id']     = $row['admin_id'];
                $_SESSION['admin_id']    = $row['admin_id'];
                $_SESSION['admin_name']  = $row['admin_name'];
                $_SESSION['email']       = $row['email'];
                $_SESSION['role']        = 'admin';
                header("Location: ../admin/home.php");
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
    <form action="" method="POST">

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

        <!-- Submit button -->
        <button type="submit" class="btn btn-primary btn-block mb-4" name="submit">Sign in</button>

    </form>
</div>