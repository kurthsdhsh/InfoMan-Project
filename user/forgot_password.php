<?php
session_start();
include('../includes/config.php');
include('../includes/mailer.php');

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    $row = null;
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $res = mysqli_execute_query($conn,
            "SELECT customer_id FROM tbl_customers WHERE email = ? LIMIT 1", [$email]);
        $row = mysqli_fetch_assoc($res);
    }

    if ($row) {
        $token = bin2hex(random_bytes(32));
        mysqli_execute_query($conn,
            "UPDATE tbl_customers
             SET reset_token_hash = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
             WHERE customer_id = ?",
            [hash('sha256', $token), $row['customer_id']]);

        $link = BASE_URL . '/user/reset_password.php?token=' . $token;
            $body = "
            <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:8px;color:#333'>
              <h2 style='margin-top:0;color:#b9736a'>Baguio Pasalubong Shop</h2>
              <p>Dear Customer,</p>
              <p>We received a request to reset your password. Click the button below to choose a new one. This link is valid for 30 minutes.</p>
              <p style='text-align:center;margin:28px 0'>
                <a href='$link' style='background:#b9736a;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold'>Reset password</a>
              </p>
              <p style='font-size:12px;color:#888'>If the button doesn't work, copy this link into your browser:<br>
                <a href='$link' style='color:#888;word-break:break-all'>$link</a></p>
              <p style='font-size:12px;color:#888'>If you didn't ask for this, you can ignore this email. Your password won't change.</p>
            </div>";
            sendMail($email, 'Reset your password', $body);
            $message = 'Reset password link has been sent.';
    } else {
        $message = 'Please use Registered Email.';
        $messageType = 'danger';
    }
}

include('../includes/header.php');
?>
<div class="row col-md-8 mx-auto">
    <form method="POST">
        <h4 class="mb-4 x">Forgot Password</h4>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
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