<?php
session_start();
include("../includes/config.php");

// must be logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = (int) $_SESSION['customer_id'];
$error = null;
$success = $_SESSION['success'] ?? null;   // set after a successful save
unset($_SESSION['success']);

// ---------- save changes ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name     = trim(strip_tags($_POST['first_name'] ?? ''));
    $last_name      = trim(strip_tags($_POST['last_name'] ?? ''));
    $contact_number = trim($_POST['contact_number'] ?? '');

    if ($first_name === '' || $last_name === '') {
        $error = 'first name and last name are required';
    } elseif (!preg_match('/^(09\d{9}|\+639\d{9})$/', $contact_number)) {
        $error = 'contact number should look like 09123456789';
    } else {
        try {
            $sql = "UPDATE tbl_customers
                    SET first_name = ?, last_name = ?, contact_number = ?
                    WHERE customer_id = ?";
            mysqli_execute_query($conn, $sql, [$first_name, $last_name, $contact_number, $customer_id]);

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
$sql = "SELECT first_name, last_name, email, contact_number, account_status
        FROM tbl_customers WHERE customer_id = ? LIMIT 1";
$result = mysqli_execute_query($conn, $sql, [$customer_id]);
$customer = mysqli_fetch_assoc($result);

if (!$customer) {           // account no longer exists
    session_destroy();
    header("Location: login.php");
    exit();
}

// if validation failed, keep what the user typed instead of the saved values
if ($error) {
    $customer['first_name']     = $first_name;
    $customer['last_name']      = $last_name;
    $customer['contact_number'] = $contact_number;
}

// header.php outputs HTML, so it must come AFTER any header("Location: ...")
include("../includes/header.php");
?>

<div class="container-xl px-4 mt-4">
    <nav class="nav nav-borders">
        <a class="nav-link active ms-0" href="https://www.bootdey.com/snippets/view/bs5-edit-profile-account-details"
            target="__blank">Profile</a>
    </nav>
    <hr class="mt-0 mb-4">

    <div class="row">
        <div class="col-xl-4">
            <!-- Profile picture card-->
            <div class="card mb-4 mb-xl-0">
                <div class="card-header">Profile Picture</div>
                <div class="card-body text-center">
                    <!-- Profile picture image-->
                    <img class="img-account-profile rounded-circle mb-2"
                        src="http://bootdey.com/img/Content/avatar/avatar1.png" alt="">
                    <!-- Profile picture help block-->
                    <div class="small font-italic text-muted mb-4">JPG or PNG no larger than 5 MB</div>
                    <!-- Profile picture upload button-->
                    <button class="btn btn-primary" type="button">Upload new image</button>
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

                    <form action="profile.php" method="POST">

                        <div class="row gx-3 mb-3">
                            <div class="col-md-6">
                                <label class="small mb-1" for="first_name">First name</label>
                                <input class="form-control" id="first_name" type="text" name="first_name"
                                    placeholder="Enter your first name"
                                    value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small mb-1" for="last_name">Last name</label>
                                <input class="form-control" id="last_name" type="text" name="last_name"
                                    placeholder="Enter your last name"
                                    value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="row gx-3 mb-3">
                            <div class="col-md-6">
                                <label class="small mb-1" for="email">Email</label>
                                <input class="form-control" id="email" type="email"
                                    value="<?php echo htmlspecialchars($customer['email']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="small mb-1" for="contact_number">Contact number</label>
                                <input class="form-control" id="contact_number" type="tel" name="contact_number"
                                    placeholder="09123456789"
                                    value="<?php echo htmlspecialchars($customer['contact_number']); ?>" required>
                            </div>
                        </div>

                        <div class="row gx-3 mb-3">
                            <div class="col-md-6">
                                <label class="small mb-1" for="status">Account status</label>
                                <input class="form-control" id="status" type="text"
                                    value="<?php echo htmlspecialchars($customer['account_status']); ?>" readonly>
                            </div>
                        </div>

                        <button class="btn btn-primary" type="submit">Save changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>