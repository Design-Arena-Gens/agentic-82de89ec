<?php
$title = 'Conversation Details';
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$conversation_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$conversation_id) {
    redirect('/admin/conversations.php');
}

$conversation_query = "
    SELECT c.*, u.full_name AS student_name,
           u.registration_number,
           d.name AS department_name
    FROM conversations c
    JOIN users u ON u.id = c.created_by
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE c.id = {$conversation_id}
    LIMIT 1";
$conversation_result = mysqli_query($conn, $conversation_query);
$conversation = $conversation_result ? mysqli_fetch_assoc($conversation_result) : null;

if (!$conversation) {
    flash_message('error', 'Conversation not found.');
    redirect('/admin/conversations.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['status'])) {
        $status = sanitize_input($_POST['status']);
        $allowed = ['open', 'pending', 'closed'];
        if (in_array($status, $allowed, true)) {
            $status_query = "
                UPDATE conversations
                SET status = '" . mysqli_real_escape_string($conn, $status) . "', updated_at = NOW()
                WHERE id = {$conversation_id}
                LIMIT 1";
            mysqli_query($conn, $status_query);
            flash_message('success', 'Status updated.');
        }
        redirect("/admin/view_conversation.php?id={$conversation_id}");
    }

    if (isset($_POST['message_body'])) {
        $body = trim($_POST['message_body']);
        if ($body) {
            $escaped = mysqli_real_escape_string($conn, $body);
            $insert_query = "
                INSERT INTO messages (conversation_id, sender_id, body)
                VALUES ({$conversation_id}, {$_SESSION['user_id']}, '{$escaped}')";
            if (mysqli_query($conn, $insert_query)) {
                $participant_query = "
                    INSERT IGNORE INTO conversation_participants (conversation_id, user_id)
                    VALUES ({$conversation_id}, {$_SESSION['user_id']})";
                mysqli_query($conn, $participant_query);
                $update_conversation = "
                    UPDATE conversations
                    SET updated_at = NOW(), status = CASE WHEN status = 'closed' THEN 'pending' ELSE status END
                    WHERE id = {$conversation_id}
                    LIMIT 1";
                mysqli_query($conn, $update_conversation);
                flash_message('success', 'Message sent.');
            } else {
                flash_message('error', 'Failed to send message.');
            }
        }
        redirect("/admin/view_conversation.php?id={$conversation_id}");
    }
}

$participants_query = "
    SELECT cp.user_id, u.full_name, u.role
    FROM conversation_participants cp
    JOIN users u ON u.id = cp.user_id
    WHERE cp.conversation_id = {$conversation_id}";
$participants_result = mysqli_query($conn, $participants_query);
$participants = $participants_result ? fetch_all($participants_result) : [];

$messages_query = "
    SELECT m.*, u.full_name, u.role
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.conversation_id = {$conversation_id}
    ORDER BY m.created_at ASC";
$messages_result = mysqli_query($conn, $messages_query);
$messages = $messages_result ? fetch_all($messages_result) : [];

include __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                Conversation Summary
            </div>
            <div class="card-body">
                <h5><?php echo sanitize_input($conversation['subject']); ?></h5>
                <p class="mb-1"><strong>Type:</strong> <?php echo ucfirst($conversation['type']); ?></p>
                <p class="mb-1"><strong>Status:</strong> <?php echo conversation_status_badge($conversation['status']); ?></p>
                <p class="mb-1"><strong>Student:</strong> <?php echo sanitize_input($conversation['student_name']); ?></p>
                <p class="mb-1"><strong>Reg. No:</strong> <?php echo sanitize_input($conversation['registration_number']); ?></p>
                <p class="mb-1"><strong>Department:</strong> <?php echo sanitize_input($conversation['department_name'] ?? 'N/A'); ?></p>
                <p class="mb-1"><strong>Created:</strong> <?php echo format_datetime($conversation['created_at']); ?></p>
                <form method="post" class="mt-3">
                    <label class="form-label">Update Status</label>
                    <select name="status" class="form-select mb-2">
                        <option value="open" <?php echo $conversation['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="pending" <?php echo $conversation['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="closed" <?php echo $conversation['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                    <button type="submit" class="btn btn-primary w-100">Save Status</button>
                </form>
            </div>
        </div>
        <div class="card shadow-sm mt-4">
            <div class="card-header">Participants</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($participants as $participant): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo sanitize_input($participant['full_name']); ?></span>
                            <span class="badge text-bg-secondary"><?php echo get_role_label($participant['role']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                Conversation Thread
            </div>
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                <?php if (!$messages): ?>
                    <p class="text-muted text-center">No messages yet.</p>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <?php $is_admin = $message['role'] === 'admin'; ?>
                        <div class="message-bubble <?php echo $is_admin ? 'sent' : 'received'; ?>">
                            <div class="small fw-bold"><?php echo sanitize_input($message['full_name']); ?> (<?php echo get_role_label($message['role']); ?>)</div>
                            <div><?php echo nl2br(sanitize_input($message['body'])); ?></div>
                            <div class="small text-end mt-2"><?php echo format_datetime($message['created_at']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="message_body" class="form-label">Send Message</label>
                        <textarea name="message_body" id="message_body" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
