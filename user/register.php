<?php
session_start();
include("../includes/header.php");

$error  = $_SESSION['message'] ?? null;
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['message'], $_SESSION['errors'], $_SESSION['old']);
?>

<link rel="stylesheet" href="/InfoMan-Project/includes/style/loginstyle.css?v=<?php echo time(); ?>">
<div class="container-fluid container-lg">
    <form action="store.php" method="POST" enctype="multipart/form-data">

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="first_name" class="form-label">first name</label>
            <input type="text" class="form-control" id="first_name" name="first_name"
                value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>">
            <?php if (isset($errors['first_name'])): ?><div class="text-danger small"><?php echo $errors['first_name']; ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="last_name" class="form-label">last name</label>
            <input type="text" class="form-control" id="last_name" name="last_name"
                value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>">
            <?php if (isset($errors['last_name'])): ?><div class="text-danger small"><?php echo $errors['last_name']; ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">email</label>
            <input type="text" class="form-control" id="email" name="email"
                value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
            <?php if (isset($errors['email'])): ?><div class="text-danger small"><?php echo $errors['email']; ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="contact_number" class="form-label">contact number</label>
            <input type="text" class="form-control" id="contact_number" name="contact_number"
                value="<?php echo htmlspecialchars($old['contact_number'] ?? ''); ?>">
            <?php if (isset($errors['contact_number'])): ?><div class="text-danger small"><?php echo $errors['contact_number']; ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">password</label>
            <input type="password" class="form-control" id="password" name="password">
            <?php if (isset($errors['password'])): ?><div class="text-danger small"><?php echo $errors['password']; ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="password2" class="form-label">confirm password</label>
            <input type="password" class="form-control" id="password2" name="confirmPass">
            <?php if (isset($errors['confirmPass'])): ?><div class="text-danger small"><?php echo $errors['confirmPass']; ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="photo" class="form-label">profile photo (optional, JPG/PNG/WEBP, max 2 MB)</label>
            <input type="file" class="form-control" id="photo" name="photo">
            <?php if (isset($errors['photo'])): ?><div class="text-danger small"><?php echo $errors['photo']; ?></div><?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</div>