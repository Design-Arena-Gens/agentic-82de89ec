<?php
$title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$user = current_user($conn);
if (!$user) {
    redirect('/logout.php');
}

$user_counts = [
    'students' => 0,
    'teachers' => 0,
    'staff' => 0,
];

$count_query = "SELECT role, COUNT(*) AS total FROM users GROUP BY role";
$count_result = mysqli_query($conn, $count_query);
if ($count_result) {
    while ($row = mysqli_fetch_assoc($count_result)) {
        $role = $row['role'];
        if ($role === 'student') {
            $user_counts['students'] = (int) $row['total'];
        } elseif ($role === 'teacher') {
            $user_counts['teachers'] = (int) $row['total'];
        } elseif ($role === 'staff') {
            $user_counts['staff'] = (int) $row['total'];
        }
    }
}

$conversation_count_query = "SELECT status, COUNT(*) AS total FROM conversations GROUP BY status";
$conversation_counts = ['open' => 0, 'pending' => 0, 'closed' => 0];
$conv_result = mysqli_query($conn, $conversation_count_query);
if ($conv_result) {
    while ($row = mysqli_fetch_assoc($conv_result)) {
        $conversation_counts[$row['status']] = (int) $row['total'];
    }
}

$recent_conversations_query = "
    SELECT c.id, c.subject, c.type, c.status, c.created_at,
           creator.full_name AS creator_name,
           d.name AS department_name
    FROM conversations c
    JOIN users creator ON creator.id = c.created_by
    LEFT JOIN departments d ON creator.department_id = d.id
    ORDER BY c.created_at DESC
    LIMIT 10";
$recent_conversations_result = mysqli_query($conn, $recent_conversations_query);
$recent_conversations = $recent_conversations_result ? fetch_all($recent_conversations_result) : [];

include __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h5 class="card-title text-white">Students</h5>
                <p class="display-6"><?php echo (int) $user_counts['students']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-success">
            <div class="card-body">
                <h5 class="card-title text-white">Teachers</h5>
                <p class="display-6"><?php echo (int) $user_counts['teachers']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-info">
            <div class="card-body">
                <h5 class="card-title text-white">Office Staff</h5>
                <p class="display-6"><?php echo (int) $user_counts['staff']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-uppercase text-muted mb-2">Open Conversations</h6>
                <p class="h4 mb-0"><?php echo (int) $conversation_counts['open']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-uppercase text-muted mb-2">Pending</h6>
                <p class="h4 mb-0"><?php echo (int) $conversation_counts['pending']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-uppercase text-muted mb-2">Closed</h6>
                <p class="h4 mb-0"><?php echo (int) $conversation_counts['closed']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Recent Conversations</span>
        <a href="/admin/conversations.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
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
                <?php if (!$recent_conversations): ?>
                    <tr><td colspan="6" class="text-center text-muted">No conversations available.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_conversations as $conversation): ?>
                        <tr>
                            <td>
                                <a href="/admin/view_conversation.php?id=<?php echo (int) $conversation['id']; ?>">
                                    <?php echo sanitize_input($conversation['subject']); ?>
                                </a>
                            </td>
                            <td><?php echo ucfirst($conversation['type']); ?></td>
                            <td><?php echo conversation_status_badge($conversation['status']); ?></td>
                            <td><?php echo sanitize_input($conversation['creator_name']); ?></td>
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
