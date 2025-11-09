<?php
$title = 'Edit User';
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$user_id) {
    redirect('/admin/manage_users.php');
}

$user_query = "
    SELECT u.*, d.name AS department_name
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.id = {$user_id}
    LIMIT 1";
$user_result = mysqli_query($conn, $user_query);
$account = $user_result ? mysqli_fetch_assoc($user_result) : null;

if (!$account) {
    flash_message('error', 'User not found.');
    redirect('/admin/manage_users.php');
}

$departments_result = mysqli_query($conn, "SELECT * FROM departments ORDER BY name ASC");
$departments = $departments_result ? fetch_all($departments_result) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $department_id = isset($_POST['department_id']) ? (int) $_POST['department_id'] : 'NULL';
    $password = $_POST['password'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!$full_name) {
        flash_message('error', 'Full name is required.');
    } else {
        $email_value = $email ? "'" . mysqli_real_escape_string($conn, $email) . "'" : "NULL";
        $department_value = $department_id ? (string) $department_id : "NULL";
        $update_query = "
            UPDATE users
            SET full_name = '" . mysqli_real_escape_string($conn, $full_name) . "',
                email = {$email_value},
                department_id = {$department_value},
                is_active = {$is_active}
            WHERE id = {$user_id}
            LIMIT 1";
        mysqli_query($conn, $update_query);

        if ($password) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $password_query = "
                UPDATE users
                SET password_hash = '" . mysqli_real_escape_string($conn, $password_hash) . "'
                WHERE id = {$user_id}
                LIMIT 1";
            mysqli_query($conn, $password_query);
        }

        flash_message('success', 'User updated successfully.');
        redirect('/admin/manage_users.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Edit User</span>
                <a href="/admin/manage_users.php" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?php echo get_role_label($account['role']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" value="<?php echo sanitize_input($account['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Identifier</label>
                        <input type="text" class="form-control" value="<?php echo sanitize_input($account['role'] === 'student' ? $account['registration_number'] : $account['username']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo sanitize_input($account['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select name="department_id" id="department_id" class="form-select">
                            <option value="">Not assigned</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo (int) $dept['id']; ?>" <?php echo $account['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo sanitize_input($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Reset Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Leave blank to keep existing password">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" <?php echo $account['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Account Active</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
