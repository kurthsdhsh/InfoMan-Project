<?php
session_start();
include("../includes/config.php");

$error = null;
$success = $_SESSION['success'] ?? null;   // set by store.php after registering
unset($_SESSION['success']);

if (isset($_POST['submit'])) {

    $email = trim($_POST['email']);
    $sql = "SELECT admin_id, admin_name, email, password_hash, account_status
            FROM tbl_admins WHERE email = ? LIMIT 1";
    $result = mysqli_execute_query($conn, $sql, [$email]);

    if ($result->num_rows === 1) {
        $row = mysqli_fetch_assoc($result);

        if (!password_verify(trim($_POST['password']), $row['password_hash'])) {
            $error = 'wrong email or password';
        } elseif (strtolower($row['account_status']) !== 'active') {
            $error = 'this account is not active';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']     = $row['admin_id'];     // header.php checks user_id
            $_SESSION['admin_id']    = $row['admin_id'];
            $_SESSION['admin_name']  = $row['admin_name'];
            $_SESSION['email']       = $row['email'];
            $_SESSION['role']        = 'admin';              // header.php reads role; tbl_admins has no role column
            header("Location: ../admin_item/index.php");
            exit();
        }
    } else {
        $error = 'wrong email or password';
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
            <input type="email" id="form2Example1" class="form-control" name="email" />
            <label class="form-label" for="form2Example1">Email address</label>
        </div>

        <!-- Password input -->
        <div class="form-outline mb-4">
            <input type="password" id="form2Example2" class="form-control" name="password" />
            <label class="form-label" for="form2Example2">Password</label>
        </div>

        <!-- Submit button -->
        <button type="submit" class="btn btn-primary btn-block mb-4" name="submit">Sign in</button>

        <!-- Register buttons -->
        <div class="text-center">
            <p>Not a member? <a href="registeradmin.php">Register</a></p>
        </div>

    </form>
</div>