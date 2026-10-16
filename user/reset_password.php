<?php
session_start();
include('../includes/config.php');

$token = $_GET['token'] ?? '';
$hash  = hash('sha256', $token);

$res = mysqli_execute_query($conn,
    "SELECT customer_id FROM tbl_customers
     WHERE reset_token_hash = ? AND reset_expires > NOW() LIMIT 1", [$hash]);
$user = mysqli_fetch_assoc($res);

$error = '';
if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw  = $_POST['password'] ?? '';
    $pw2 = $_POST['password2'] ?? '';
    if (strlen($pw) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pw !== $pw2) {
        $error = 'Passwords do not match.';
    } else {
        mysqli_execute_query($conn,
            "UPDATE tbl_customers
             SET password_hash = ?, reset_token_hash = NULL, reset_expires = NULL
             WHERE customer_id = ?",
            [password_hash($pw, PASSWORD_DEFAULT), $user['customer_id']]);
        $_SESSION['success'] = 'Password changed. You can log in now.';
        header('Location: login.php');
        exit();
    }
}

include('../includes/header.php');
?>
<div class="row col-md-8 mx-auto">
<?php if (!$user): ?>
    <div class="alert alert-danger">This reset link is invalid or has expired.</div>
    <p><a href="forgot_password.php">Request a new link</a></p>
<?php else: ?>
    <form method="POST">
        <h4 class="mb-3">Set a new password</h4>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <div class="form-outline mb-4">
            <input type="password" name="password" class="form-control" required />
            <label class="form-label">New password</label>
        </div>
        <div class="form-outline mb-4">
            <input type="password" name="password2" class="form-control" required />
            <label class="form-label">Confirm password</label>
        </div>
        <button type="submit" class="btn btn-primary">Change password</button>
    </form>
<?php endif; ?>
</div>