<?php
session_start();
include('../includes/config.php');
include('../includes/mailer.php');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $res = mysqli_execute_query($conn,
            "SELECT customer_id FROM tbl_customers WHERE email = ? LIMIT 1", [$email]);
        if ($row = mysqli_fetch_assoc($res)) {
            $token = bin2hex(random_bytes(32));
            mysqli_execute_query($conn,
                "UPDATE tbl_customers
                 SET reset_token_hash = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                 WHERE customer_id = ?",
                [hash('sha256', $token), $row['customer_id']]);

            $link = BASE_URL . '/user/reset_password.php?token=' . $token;
            sendMail($email, 'Reset your password',
                "<p>Click the link to reset your password (valid for 30 minutes):</p>
                 <p><a href='$link'>$link</a></p>");
        }
    }
    // same message whether or not the email exists
    $message = 'If that email is registered, a reset link has been sent.';
}

include('../includes/header.php');
?>
<div class="row col-md-8 mx-auto">
    <form method="POST">
        <h4 class="mb-4">Forgot Password</h4>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <div class="form-outline mb-4">
            <input type="email" name="email" class="form-control" required />
            <label class="form-label">Email address</label>
        </div>
        <button type="submit" class="btn btn-primary mb-4">Send reset link</button>

        <p class="text-end">
            <a href="login.php" >Back to login</a>
        </p>
    </form>
</div>