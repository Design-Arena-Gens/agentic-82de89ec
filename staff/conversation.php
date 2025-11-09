<?php
$title = 'Conversation';
require_once __DIR__ . '/../includes/functions.php';
require_roles(['teacher', 'staff']);

$conversation_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$conversation_id) {
    redirect('/staff/dashboard.php');
}

$user = current_user($conn);
if (!$user) {
    redirect('/logout.php');
}

$conversation_query = "
    SELECT c.*, creator.full_name AS student_name, creator.registration_number
    FROM conversations c
    JOIN users creator ON creator.id = c.created_by
    WHERE c.id = {$conversation_id}
    LIMIT 1";
$conversation_result = mysqli_query($conn, $conversation_query);
$conversation = $conversation_result ? mysqli_fetch_assoc($conversation_result) : null;

if (!$conversation) {
    flash_message('error', 'Conversation not found.');
    redirect('/staff/dashboard.php');
}

$participant_check = "
    SELECT 1 FROM conversation_participants
    WHERE conversation_id = {$conversation_id} AND user_id = {$user['id']}
    LIMIT 1";
$participant_result = mysqli_query($conn, $participant_check);
if (!$participant_result || mysqli_num_rows($participant_result) === 0) {
    flash_message('error', 'You are not assigned to this conversation.');
    redirect('/staff/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['message_body'])) {
        $body = trim($_POST['message_body']);
        if ($body) {
            $body_db = mysqli_real_escape_string($conn, $body);
            $insert_query = "
                INSERT INTO messages (conversation_id, sender_id, body)
                VALUES ({$conversation_id}, {$user['id']}, '{$body_db}')";
            mysqli_query($conn, $insert_query);
            $update_status = "
                UPDATE conversations
                SET updated_at = NOW(),
                    status = CASE WHEN status = 'open' THEN 'pending' ELSE status END
                WHERE id = {$conversation_id}
                LIMIT 1";
            mysqli_query($conn, $update_status);
            flash_message('success', 'Message sent.');
        }
        redirect('/staff/conversation.php?id=' . $conversation_id);
    }

    if (isset($_POST['status'])) {
        $status = sanitize_input($_POST['status']);
        if (in_array($status, ['open', 'pending', 'closed'], true)) {
            $status_query = "
                UPDATE conversations
                SET status = '" . mysqli_real_escape_string($conn, $status) . "', updated_at = NOW()
                WHERE id = {$conversation_id}
                LIMIT 1";
            mysqli_query($conn, $status_query);
            flash_message('success', 'Status updated.');
        }
        redirect('/staff/conversation.php?id=' . $conversation_id);
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
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1"><?php echo sanitize_input($conversation['subject']); ?></h1>
        <div>
            <span class="badge text-bg-primary me-2"><?php echo ucfirst($conversation['type']); ?></span>
            <?php echo conversation_status_badge($conversation['status']); ?>
        </div>
    </div>
    <div>
        <a href="/staff/dashboard.php" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header">Student</div>
    <div class="card-body">
        <p class="mb-1"><strong><?php echo sanitize_input($conversation['student_name']); ?></strong></p>
        <p class="small text-muted mb-0">Registration: <?php echo sanitize_input($conversation['registration_number'] ?? ''); ?></p>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Participants</span>
        <form method="post" class="d-flex align-items-center">
            <select name="status" class="form-select form-select-sm me-2">
                <option value="open" <?php echo $conversation['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                <option value="pending" <?php echo $conversation['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="closed" <?php echo $conversation['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Update Status</button>
        </form>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            <?php foreach ($participants as $participant): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?php echo sanitize_input($participant['full_name']); ?></span>
                    <span class="badge text-bg-light"><?php echo get_role_label($participant['role']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Conversation Thread</div>
    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
        <?php if (!$messages): ?>
            <p class="text-muted text-center">No messages yet.</p>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <?php $is_staff = $message['role'] !== 'student'; ?>
                <div class="message-bubble <?php echo $is_staff ? 'sent' : 'received'; ?>">
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
                <label for="message_body" class="form-label">Reply</label>
                <textarea name="message_body" id="message_body" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
