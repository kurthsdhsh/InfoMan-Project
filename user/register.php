<?php
session_start();
include("../includes/head.php");

$error  = $_SESSION['message'] ?? null;
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['message'], $_SESSION['errors'], $_SESSION['old']);
?>

<link rel="stylesheet" href="/InfoMan-Project/includes/style/auth.css?v=<?php echo time(); ?>">

<div class="auth-page">

    <a href="/InfoMan-Project/index.php" class="auth-back">
        <i class="fa-solid fa-arrow-left"></i> Back to home
    </a>

    <div class="auth-card">

        <div class="auth-form-wrap">
            <h1>Create account</h1>
            <p class="auth-sub">Join us and start ordering Baguio pasalubong.</p>

            <?php if ($error): ?>
                <div class="auth-alert danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="store.php" method="POST" enctype="multipart/form-data">

                <div class="auth-row">
                    <div class="auth-field <?php echo isset($errors['first_name']) ? 'has-error' : ''; ?>">
                        <label for="first_name">First name</label>
                        <div class="auth-input">
                            <i class="fa-regular fa-user"></i>
                            <input type="text" id="first_name" name="first_name" placeholder="Juan"
                                value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>">
                        </div>
                        <?php if (isset($errors['first_name'])): ?>
                            <div class="auth-error"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="auth-field <?php echo isset($errors['last_name']) ? 'has-error' : ''; ?>">
                        <label for="last_name">Last name</label>
                        <div class="auth-input">
                            <i class="fa-regular fa-user"></i>
                            <input type="text" id="last_name" name="last_name" placeholder="Dela Cruz"
                                value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>">
                        </div>
                        <?php if (isset($errors['last_name'])): ?>
                            <div class="auth-error"><?php echo htmlspecialchars($errors['last_name']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="auth-field <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
                    <label for="email">Email</label>
                    <div class="auth-input">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="text" id="email" name="email" placeholder="you@example.com"
                            value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <div class="auth-error"><?php echo htmlspecialchars($errors['email']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="auth-field <?php echo isset($errors['contact_number']) ? 'has-error' : ''; ?>">
                    <label for="contact_number">Contact number</label>
                    <div class="auth-input">
                        <i class="fa-solid fa-phone"></i>
                        <input type="text" id="contact_number" name="contact_number" placeholder="09123456789"
                            value="<?php echo htmlspecialchars($old['contact_number'] ?? ''); ?>">
                    </div>
                    <?php if (isset($errors['contact_number'])): ?>
                        <div class="auth-error"><?php echo htmlspecialchars($errors['contact_number']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="auth-row">
                    <div class="auth-field <?php echo isset($errors['password']) ? 'has-error' : ''; ?>">
                        <label for="password">Password</label>
                        <div class="auth-input">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Password">
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="auth-error"><?php echo htmlspecialchars($errors['password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="auth-field <?php echo isset($errors['confirmPass']) ? 'has-error' : ''; ?>">
                        <label for="password2">Confirm password</label>
                        <div class="auth-input">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" id="password2" name="confirmPass" placeholder="Repeat password">
                        </div>
                        <?php if (isset($errors['confirmPass'])): ?>
                            <div class="auth-error"><?php echo htmlspecialchars($errors['confirmPass']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="auth-field auth-file <?php echo isset($errors['photo']) ? 'has-error' : ''; ?>">
                    <label for="photo">Profile photo (optional)</label>
                    <input type="file" id="photo" name="photo">
                    <div class="auth-hint">JPG, PNG or WEBP, up to 2 MB.</div>
                    <?php if (isset($errors['photo'])): ?>
                        <div class="auth-error"><?php echo htmlspecialchars($errors['photo']); ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="auth-btn">Create account</button>

                <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>

            </form>
        </div>

        <div class="auth-brand">
            <div class="auth-logo"><i class="fa-solid fa-store"></i></div>
            <div class="auth-store">Kurdiella's Baguio Treasures</div>
            <h2>Join us!</h2>
            <p>Create your account in a minute and start ordering fresh Baguio pasalubong, strawberry jam and more.</p>
            <ul>
                <li><i class="fa-solid fa-star"></i> Review the products you bought</li>
                <li><i class="fa-solid fa-clock-rotate-left"></i> Track all your orders</li>
                <li><i class="fa-solid fa-gift"></i> Great gifts from Baguio</li>
            </ul>
        </div>

    </div>
</div>