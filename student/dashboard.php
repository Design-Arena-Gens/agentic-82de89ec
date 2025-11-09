<?php
$title = 'Student Dashboard';
require_once __DIR__ . '/../includes/functions.php';
require_login('student');

$user = current_user($conn);
if (!$user) {
    redirect('/logout.php');
}

$conditions = ["c.created_by = {$user['id']}"];
$filters = [];

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

$where_clause = 'WHERE ' . implode(' AND ', $conditions);

$query = "
    SELECT c.*, GROUP_CONCAT(u.full_name SEPARATOR ', ') AS recipients
    FROM conversations c
    LEFT JOIN conversation_participants cp ON cp.conversation_id = c.id
    LEFT JOIN users u ON u.id = cp.user_id AND u.id <> c.created_by
    {$where_clause}
    GROUP BY c.id
    ORDER BY c.updated_at DESC, c.created_at DESC";
$result = mysqli_query($conn, $query);
$conversations = $result ? fetch_all($result) : [];

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">My Inquiries & Claims</h1>
    <a href="/student/new_conversation.php" class="btn btn-primary">New Inquiry / Claim</a>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <form method="get" class="row g-2">
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
                <button type="submit" class="btn btn-outline-primary w-100">Apply</button>
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
                    <th>Recipients</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$conversations): ?>
                    <tr><td colspan="6" class="text-center text-muted">No conversations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($conversations as $conversation): ?>
                        <tr>
                            <td><?php echo sanitize_input($conversation['subject']); ?></td>
                            <td><?php echo ucfirst($conversation['type']); ?></td>
                            <td><?php echo sanitize_input($conversation['recipients'] ?: 'No recipients yet'); ?></td>
                            <td><?php echo conversation_status_badge($conversation['status']); ?></td>
                            <td><?php echo format_datetime($conversation['updated_at']); ?></td>
                            <td>
                                <a href="/student/conversation.php?id=<?php echo (int) $conversation['id']; ?>" class="btn btn-sm btn-outline-primary">Open</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
