<?php
$title = 'All Conversations';
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$filters = [];
$conditions = [];

if (!empty($_GET['status'])) {
    $status = sanitize_input($_GET['status']);
    $conditions[] = "c.status = '" . mysqli_real_escape_string($conn, $status) . "'";
    $filters['status'] = $status;
}
if (!empty($_GET['type'])) {
    $type = sanitize_input($_GET['type']);
    $conditions[] = "c.type = '" . mysqli_real_escape_string($conn, $type) . "'";
    $filters['type'] = $type;
}

$where_clause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$query = "
    SELECT c.*, u.full_name AS student_name, d.name AS department_name
    FROM conversations c
    JOIN users u ON u.id = c.created_by
    LEFT JOIN departments d ON u.department_id = d.id
    {$where_clause}
    ORDER BY c.created_at DESC";
$result = mysqli_query($conn, $query);
$conversations = $result ? fetch_all($result) : [];

include __DIR__ . '/../includes/header.php';
?>
<div class="card shadow-sm">
    <div class="card-header">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="open" <?php echo (isset($filters['status']) && $filters['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                    <option value="pending" <?php echo (isset($filters['status']) && $filters['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="closed" <?php echo (isset($filters['status']) && $filters['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="">All</option>
                    <option value="inquiry" <?php echo (isset($filters['type']) && $filters['type'] === 'inquiry') ? 'selected' : ''; ?>>Inquiry</option>
                    <option value="claim" <?php echo (isset($filters['type']) && $filters['type'] === 'claim') ? 'selected' : ''; ?>>Claim</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Student</th>
                    <th>Department</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$conversations): ?>
                    <tr><td colspan="6" class="text-center text-muted">No conversations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($conversations as $conversation): ?>
                        <tr>
                            <td>
                                <a href="/admin/view_conversation.php?id=<?php echo (int) $conversation['id']; ?>">
                                    <?php echo sanitize_input($conversation['subject']); ?>
                                </a>
                            </td>
                            <td><?php echo ucfirst($conversation['type']); ?></td>
                            <td><?php echo conversation_status_badge($conversation['status']); ?></td>
                            <td><?php echo sanitize_input($conversation['student_name']); ?></td>
                            <td><?php echo sanitize_input($conversation['department_name'] ?? 'N/A'); ?></td>
                            <td><?php echo format_datetime($conversation['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
