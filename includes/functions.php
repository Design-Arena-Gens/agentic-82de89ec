<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function require_login(string $role = ''): void
{
    if (!is_logged_in()) {
        header('Location: /index.php');
        exit;
    }

    if ($role && $_SESSION['role'] !== $role) {
        header('Location: /index.php');
        exit;
    }
}

function sanitize_input(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header("Location: {$path}");
    exit;
}

function fetch_all(mysqli_result $result): array
{
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function get_role_label(string $role): string
{
    $labels = [
        'admin' => 'Admin',
        'student' => 'Student',
        'teacher' => 'Teacher',
        'staff' => 'Office Staff',
    ];

    return $labels[$role] ?? ucfirst($role);
}

function require_roles(array $roles): void
{
    if (!is_logged_in() || !in_array($_SESSION['role'], $roles, true)) {
        header('Location: /index.php');
        exit;
    }
}

function flash_message(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function render_flash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];

    return '<div class="alert alert-' . sanitize_input($type) . ' alert-dismissible fade show" role="alert">'
        . sanitize_input($flash['message'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
        . '</div>';
}

function current_user(mysqli $conn): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    $user_id = (int) $_SESSION['user_id'];
    $query = "SELECT u.*, d.name AS department_name
              FROM users u
              LEFT JOIN departments d ON u.department_id = d.id
              WHERE u.id = {$user_id}";

    $result = mysqli_query($conn, $query);
    return $result ? mysqli_fetch_assoc($result) : null;
}

function ensure_active_account(array $user): void
{
    if (!$user['is_active']) {
        session_destroy();
        redirect('/index.php?inactive=1');
    }
}

function conversation_status_badge(string $status): string
{
    $map = [
        'open' => 'success',
        'pending' => 'warning',
        'closed' => 'secondary',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $class . '">' . ucfirst($status) . '</span>';
}

function format_datetime(?string $timestamp): string
{
    if (!$timestamp) {
        return '';
    }
    return date('M d, Y h:i A', strtotime($timestamp));
}
