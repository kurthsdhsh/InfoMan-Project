<?php
session_start();

// para yung admin lang makaka-access sa admin pages and redirected agad sa login page kung di pa nakakalogin
// bawal din direct access sa url (marredirect ulit sa admin login page)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
     $_SESSION['message'] = 'Please sign in as an admin to continue.';
    header("Location: /InfoMan-Project/admin/adminlogin.php");
    exit;
}
?>