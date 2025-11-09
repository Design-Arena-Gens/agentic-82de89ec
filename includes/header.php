<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$user = current_user($conn);
if ($user) {
    ensure_active_account($user);
}

$title = $title ?? 'iSCSS';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo sanitize_input($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="/assets/css/styles.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/dashboard.php">iSCSS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($user): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">Dashboard</a>
                    </li>
                    <?php if ($user['role'] === 'student'): ?>
                        <li class="nav-item"><a class="nav-link" href="/student/new_conversation.php">New Inquiry/Claim</a></li>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="/admin/manage_users.php">Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="/admin/departments.php">Departments</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <span class="navbar-text">
                <?php if ($user): ?>
                    <?php echo sanitize_input($user['full_name'] ?: $user['username'] ?: $user['registration_number']); ?> (<?php echo get_role_label($user['role']); ?>)
                <?php endif; ?>
            </span>
            <ul class="navbar-nav ms-3">
                <?php if ($user): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
    <?php echo render_flash(); ?>
