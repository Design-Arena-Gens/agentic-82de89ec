<?php
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    redirect('/index.php');
}

$role = $_SESSION['role'];

switch ($role) {
    case 'admin':
        redirect('/admin/dashboard.php');
    case 'student':
        redirect('/student/dashboard.php');
    case 'teacher':
    case 'staff':
        redirect('/staff/dashboard.php');
    default:
        session_destroy();
        redirect('/index.php');
}
