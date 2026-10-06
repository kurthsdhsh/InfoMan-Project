<?php
session_start();
include("../includes/header.php");

// Messages come from store.php (errors) via the session
$error = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
?>
<!-- loads after everything else; ?v=time() stops the browser from using a cached copy -->
<link rel="stylesheet" href="/InfoMan-Project/includes/style/loginstyle.css?v=<?php echo time(); ?>">
<div class="container-fluid container-lg">
    <form action="store.php" method="POST">

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="first_name" class="form-label">first name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" required>
        </div>

        <div class="mb-3">
            <label for="last_name" class="form-label">last name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>

        <div class="mb-3">
            <label for="contact_number" class="form-label">contact number</label>
            <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>

        <div class="mb-3">
            <label for="password2" class="form-label">confirm password</label>
            <input type="password" class="form-control" id="password2" name="confirmPass" required>
        </div>

        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</div>