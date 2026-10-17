<?php
session_start();


include("../includes/config.php");
include("../includes/upload_helper.php");

// must be logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['success'] = 'Please sign in to continue.';
    header("Location: login.php");
    exit();
}

$customer_id = (int) $_SESSION['user_id'];
$error = null;
$errors = [];
$success = $_SESSION['success'] ?? null;   // set after a successful save
unset($_SESSION['success']);

// ---------- save changes ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name     = trim(strip_tags($_POST['first_name'] ?? ''));
    $last_name      = trim(strip_tags($_POST['last_name'] ?? ''));
    $contact_number = trim($_POST['contact_number'] ?? '');

    if ($first_name === '') {
        $errors['first_name'] = 'First name is required.';
    }
    if ($last_name === '') {
        $errors['last_name'] = 'Last name is required.';
    }
    if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $contact_number)) {
        $errors['contact_number'] = 'Contact number should look like 09123456789.';
    }

    // save the new photo only when the other fields are fine
        $newPhoto = '';
    if (empty($errors)) {
        $newPhoto = saveImage($_FILES['photo'] ?? [], 'uploads/profiles', $photoError);
        if ($photoError) {
            $errors['photo'] = $photoError;
        }
    }

     if (empty($errors)) {
        try {
            // remember the old photo so it can be deleted after a new one is saved
            $oldRow = mysqli_fetch_assoc(mysqli_execute_query($conn,
                "SELECT profile_photo FROM tbl_customers WHERE customer_id = ?", [$customer_id]));

            // COALESCE keeps the old photo when no new one was uploaded
            $photoValue = ($newPhoto !== '') ? $newPhoto : null;
            $sql = "UPDATE tbl_customers
                    SET first_name = ?, last_name = ?, contact_number = ?,
                        profile_photo = COALESCE(?, profile_photo)
                    WHERE customer_id = ?";
            mysqli_execute_query($conn, $sql, [$first_name, $last_name, $contact_number, $photoValue, $customer_id]);

            if ($newPhoto !== '' && !empty($oldRow['profile_photo'])) {
                @unlink(__DIR__ . '/../' . $oldRow['profile_photo']);
            }

            $_SESSION['first_name'] = $first_name;
            $_SESSION['success'] = 'profile saved';
            header("Location: profile.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            $error = 'something went wrong, please try again';
        }
    }
}

// ---------- load the customer ----------
$sql = "SELECT first_name, last_name, email, contact_number, account_status, profile_photo
        FROM tbl_customers WHERE customer_id = ? LIMIT 1";
$result = mysqli_execute_query($conn, $sql, [$customer_id]);
$customer = mysqli_fetch_assoc($result);

if (!$customer) {           // account no longer exists
    session_destroy();
    header("Location: login.php");
    exit();
}

// if validation failed, keep what the user typed instead of the saved values
if ($error || !empty($errors)) {
    $customer['first_name']     = $first_name;
    $customer['last_name']      = $last_name;
    $customer['contact_number'] = $contact_number;
}

// header.php outputs HTML, so it must come AFTER any header("Location: ...")
include("../includes/header.php");
?>

<div class="container-xl px-4 mt-4">
    
    <hr class="mt-0 mb-4">

    <div class="row">
        <div class="col-xl-4">
            <!-- Profile picture card-->
            <div class="card mb-4 mb-xl-0">
                <div class="card-header">Profile Picture</div>
                <div class="card-body text-center">
                    <?php $hasPhoto = !empty($customer['profile_photo']); ?>
                    <img id="photoPreview" class="rounded-circle mb-2" alt="Profile photo"
                        src="<?php echo $hasPhoto ? '/InfoMan-Project/' . htmlspecialchars($customer['profile_photo']) : ''; ?>"
                        style="width:150px;height:150px;object-fit:cover;<?php echo $hasPhoto ? '' : 'display:none;'; ?>">
                    <i id="photoIcon" class="fa-solid fa-circle-user fa-8x text-secondary mb-2"
                        style="<?php echo $hasPhoto ? 'display:none;' : ''; ?>"></i>
                    <div class="small text-muted">Pick a photo, then click Save Changes.</div>
                    <button class="btn btn-primary" type="button" onclick="document.getElementById('photo').click()">Upload New Image</button>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <!-- Account details card-->
            <div class="card mb-4">
                <div class="card-header">Account Details</div>
                <div class="card-body">

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST" enctype="multipart/form-data">

                        <div class="row gx-3 mb-3">
                            <div class="col-md-6">
                                <label class="small mb-1" for="first_name">First name</label>
                                <input class="form-control" id="first_name" type="text" name="first_name"
                                    placeholder="Enter your first name"
                                    value="<?php echo htmlspecialchars($customer['first_name']); ?>" >
                                <?php if (isset($errors['first_name'])): ?><div class="text-danger small"><?php echo $errors['first_name']; ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="small mb-1" for="last_name">Last name</label>
                                <input class="form-control" id="last_name" type="text" name="last_name"
                                    placeholder="Enter your last name"
                                    value="<?php echo htmlspecialchars($customer['last_name']); ?>" >
                                <?php if (isset($errors['last_name'])): ?><div class="text-danger small"><?php echo $errors['last_name']; ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="row gx-3 mb-3">
                            <div class="col-md-6">
                                <label class="small mb-1" for="email">Email</label>
                                <input class="form-control" id="email" type="text"
                                    value="<?php echo htmlspecialchars($customer['email']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="small mb-1" for="contact_number">Contact number</label>
                                <input class="form-control" id="contact_number" type="text" name="contact_number"
                                    placeholder="09123456789"
                                    value="<?php echo htmlspecialchars($customer['contact_number']); ?>" >
                                <?php if (isset($errors['contact_number'])): ?><div class="text-danger small"><?php echo $errors['contact_number']; ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="row gx-3 mb-3">
                            <div class="col-md-6">
                                <label class="small mb-1" for="status">Account status</label>
                                <input class="form-control" id="status" type="text"
                                    value="<?php echo htmlspecialchars($customer['account_status']); ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <input id="photo" type="file" name="photo" class="d-none" onchange="showPhoto(this)">
                            <div class="small text-muted">Profile photo: JPG, PNG or WEBP, max 2 MB.
                                Chosen: <span id="photoName">none</span></div>
                            <?php if (isset($errors['photo'])): ?><div class="text-danger small"><?php echo $errors['photo']; ?></div><?php endif; ?>
                        </div>

                    <button class="btn btn-primary" type="submit">Save Changes</button>   
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showPhoto(input) {
    var file = input.files[0];
    document.getElementById('photoName').textContent = file ? file.name : 'none';
    if (file) {
        var img = document.getElementById('photoPreview');
        img.src = URL.createObjectURL(file);
        img.style.display = '';
        document.getElementById('photoIcon').style.display = 'none';
    }
}
</script>