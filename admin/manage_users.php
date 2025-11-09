<?php
$title = 'Manage Users';
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$user = current_user($conn);
if (!$user) {
    redirect('/logout.php');
}

if (isset($_GET['toggle'], $_GET['id'])) {
    $user_id = (int) $_GET['id'];
    $toggle = $_GET['toggle'] === 'activate' ? 1 : 0;
    $query = "UPDATE users SET is_active={$toggle} WHERE id={$user_id} AND id <> {$_SESSION['user_id']} LIMIT 1";
    mysqli_query($conn, $query);
    flash_message('success', 'Account status updated.');
    redirect('/admin/manage_users.php');
}

$departments_result = mysqli_query($conn, "SELECT * FROM departments ORDER BY name ASC");
$departments = $departments_result ? fetch_all($departments_result) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = sanitize_input($_POST['role'] ?? '');
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $username = sanitize_input($_POST['username'] ?? '');
    $registration_number = sanitize_input($_POST['registration_number'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $department_id = isset($_POST['department_id']) ? (int) $_POST['department_id'] : null;
    $password = $_POST['password'] ?? '';

    $roles = ['student', 'teacher', 'staff'];

    if (!in_array($role, $roles, true) || !$full_name || !$password) {
        flash_message('error', 'Please provide all required information.');
    } else {
        if ($role === 'student' && !$registration_number) {
            flash_message('error', 'Registration number is required for students.');
        } elseif ($role !== 'student' && !$username) {
            flash_message('error', 'Username is required for teachers and staff.');
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $columns = ['role', 'full_name', 'email', 'password_hash', 'is_active'];
            $values = [
                "'" . mysqli_real_escape_string($conn, $role) . "'",
                "'" . mysqli_real_escape_string($conn, $full_name) . "'",
                $email ? "'" . mysqli_real_escape_string($conn, $email) . "'" : "NULL",
                "'" . mysqli_real_escape_string($conn, $password_hash) . "'",
                "1"
            ];

            if ($role === 'student') {
                $columns[] = 'registration_number';
                $values[] = "'" . mysqli_real_escape_string($conn, $registration_number) . "'";
            } else {
                $columns[] = 'username';
                $values[] = "'" . mysqli_real_escape_string($conn, $username) . "'";
            }

            if ($department_id) {
                $columns[] = 'department_id';
                $values[] = (string) $department_id;
            }

            $columns_list = implode(', ', $columns);
            $values_list = implode(', ', $values);
            $insert_query = "INSERT INTO users ({$columns_list}) VALUES ({$values_list})";

            if (mysqli_query($conn, $insert_query)) {
                flash_message('success', 'User created successfully.');
            } else {
                flash_message('error', 'Failed to create user: ' . mysqli_error($conn));
            }
        }
    }
    redirect('/admin/manage_users.php');
}

$users_query = "
    SELECT u.*, d.name AS department_name
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    ORDER BY FIELD(u.role, 'admin', 'student', 'teacher', 'staff'), u.full_name ASC";
$users_result = mysqli_query($conn, $users_query);
$users = $users_result ? fetch_all($users_result) : [];

include __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">Add User</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="" selected disabled>Select role</option>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="staff">Office Staff</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="registration_number" class="form-label">Registration Number (Students)</label>
                        <input type="text" class="form-control" id="registration_number" name="registration_number">
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username (Teachers & Staff)</label>
                        <input type="text" class="form-control" id="username" name="username">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select name="department_id" id="department_id" class="form-select">
                            <option value="">Not assigned</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo (int) $dept['id']; ?>"><?php echo sanitize_input($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Temporary Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create User</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>All Users</span>
                <a href="/admin/export_users.php" class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">Export (Coming Soon)</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Identifier</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $account): ?>
                            <tr>
                                <td><?php echo sanitize_input($account['full_name']); ?></td>
                                <td><?php echo get_role_label($account['role']); ?></td>
                                <td>
                                    <?php
                                    if ($account['role'] === 'student') {
                                        echo sanitize_input($account['registration_number'] ?? '');
                                    } else {
                                        echo sanitize_input($account['username'] ?? '');
                                    }
                                    ?>
                                </td>
                                <td><?php echo sanitize_input($account['department_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge text-bg-<?php echo $account['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $account['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/admin/edit_user.php?id=<?php echo (int) $account['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <?php if ($account['id'] !== $_SESSION['user_id']): ?>
                                        <?php if ($account['is_active']): ?>
                                            <a href="/admin/manage_users.php?toggle=deactivate&id=<?php echo (int) $account['id']; ?>" class="btn btn-sm btn-outline-warning">Deactivate</a>
                                        <?php else: ?>
                                            <a href="/admin/manage_users.php?toggle=activate&id=<?php echo (int) $account['id']; ?>" class="btn btn-sm btn-outline-success">Activate</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
