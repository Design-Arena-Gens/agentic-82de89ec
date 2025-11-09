<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = sanitize_input($_POST['role'] ?? '');
    $identifier = sanitize_input($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($role && $identifier && $password) {
        $role_list = ['admin', 'student', 'teacher', 'staff'];
        if (!in_array($role, $role_list, true)) {
            $error = 'Invalid role selected.';
        } else {
            $identifier_safe = mysqli_real_escape_string($conn, $identifier);
            if ($role === 'student') {
                $query = "SELECT * FROM users WHERE role='student' AND registration_number='{$identifier_safe}' LIMIT 1";
            } else {
                $query = "SELECT * FROM users WHERE role='{$role}' AND username='{$identifier_safe}' LIMIT 1";
            }

            $result = mysqli_query($conn, $query);
            $user = $result ? mysqli_fetch_assoc($result) : null;

            if ($user && password_verify($password, $user['password_hash'])) {
                if (!$user['is_active']) {
                    $error = 'Your account is currently inactive. Please contact the administrator.';
                } else {
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['role'] = $user['role'];
                    redirect('/dashboard.php');
                }
            } else {
                $error = 'Invalid credentials provided.';
            }
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

$inactive_notice = isset($_GET['inactive']) ? 'Your account is inactive. Please contact the administrator.' : '';
$title = 'iSCSS Login';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3 text-center">iSCSS Login</h1>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo sanitize_input($error); ?></div>
                <?php endif; ?>
                <?php if ($inactive_notice): ?>
                    <div class="alert alert-warning"><?php echo sanitize_input($inactive_notice); ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="staff">Office Staff</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="identifier" class="form-label" id="identifierLabel">Identifier</label>
                        <input type="text" class="form-control" id="identifier" name="identifier" placeholder="Username or Registration Number" required>
                        <div class="form-text">Students use Registration Number, others use Username.</div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
