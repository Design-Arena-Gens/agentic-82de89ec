<?php
$title = 'Departments';
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    if ($name) {
        $name_value = mysqli_real_escape_string($conn, $name);
        $insert_query = "INSERT INTO departments (name) VALUES ('{$name_value}')";
        if (mysqli_query($conn, $insert_query)) {
            flash_message('success', 'Department added.');
        } else {
            flash_message('error', 'Failed to add department.');
        }
    } else {
        flash_message('error', 'Department name is required.');
    }
    redirect('/admin/departments.php');
}

if (isset($_GET['delete'])) {
    $dept_id = (int) $_GET['delete'];
    $delete_query = "DELETE FROM departments WHERE id={$dept_id} LIMIT 1";
    if (mysqli_query($conn, $delete_query)) {
        flash_message('success', 'Department removed.');
    } else {
        flash_message('error', 'Unable to delete department. Ensure it is not assigned to users.');
    }
    redirect('/admin/departments.php');
}

$departments_result = mysqli_query($conn, "SELECT * FROM departments ORDER BY name ASC");
$departments = $departments_result ? fetch_all($departments_result) : [];

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Departments</span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">Add</button>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php if (!$departments): ?>
                        <li class="list-group-item text-muted text-center">No departments found.</li>
                    <?php else: ?>
                        <?php foreach ($departments as $dept): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo sanitize_input($dept['name']); ?></span>
                                <a href="/admin/departments.php?delete=<?php echo (int) $dept['id']; ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDepartmentLabel">Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Department Name</label>
                        <input type="text" class="form-control" name="name" id="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
