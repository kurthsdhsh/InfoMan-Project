<?php
session_start();

// clear all session data
$_SESSION = [];

// remove the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

// go back to the login page
header("Location: login.php");
exit();