<?php
$title = 'Conversation';
require_once __DIR__ . '/../includes/functions.php';
require_login('student');

$conversation_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$conversation_id) {
    redirect('/student/dashboard.php');
}

$user = current_user($conn);
if (!$user) {
    redirect('/logout.php');
}

$conversation_query = "
    SELECT c.*
    FROM conversations c
    WHERE c.id = {$conversation_id} AND c.created_by = {$user['id']}
    LIMIT 1";
$conversation_result = mysqli_query($conn, $conversation_query);
$conversation = $conversation_result ? mysqli_fetch_assoc($conversation_result) : null;

if (!$conversation) {
    flash_message('error', 'Conversation not found.');
    redirect('/student/dashboard.php');
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
            $update_query = "
                UPDATE conversations
                SET updated_at = NOW(),
                    status = CASE WHEN status = 'closed' THEN 'pending' ELSE status END
                WHERE id = {$conversation_id}
                LIMIT 1";
            mysqli_query($conn, $update_query);
            flash_message('success', 'Message sent.');
        }
        redirect('/student/conversation.php?id=' . $conversation_id);
    }

    if (isset($_POST['close_conversation'])) {
        $close_query = "
            UPDATE conversations
            SET status = 'closed', updated_at = NOW()
            WHERE id = {$conversation_id}
            LIMIT 1";
        mysqli_query($conn, $close_query);
        flash_message('success', 'Conversation closed.');
        redirect('/student/conversation.php?id=' . $conversation_id);
    }
}

$participants_query = "
    SELECT cp.user_id, u.full_name, u.role
    FROM conversation_participants cp
    JOIN users u ON u.id = cp.user_id
    WHERE cp.conversation_id = {$conversation_id} AND u.id <> {$user['id']}";
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
        <form method="post" class="d-inline">
            <button type="submit" name="close_conversation" class="btn btn-outline-secondary" <?php echo $conversation['status'] === 'closed' ? 'disabled' : ''; ?>>
                Close Conversation
            </button>
        </form>
        <a href="/student/dashboard.php" class="btn btn-outline-primary ms-2">Back</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header">Recipients</div>
    <div class="card-body">
        <?php if (!$participants): ?>
            <p class="text-muted mb-0">No recipients assigned.</p>
        <?php else: ?>
            <ul class="list-inline mb-0">
                <?php foreach ($participants as $participant): ?>
                    <li class="list-inline-item badge text-bg-light">
                        <?php echo sanitize_input($participant['full_name']); ?> (<?php echo get_role_label($participant['role']); ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Conversation Thread</div>
    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
        <?php if (!$messages): ?>
            <p class="text-muted text-center">No messages yet.</p>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <?php $is_student = $message['role'] === 'student'; ?>
                <div class="message-bubble <?php echo $is_student ? 'sent' : 'received'; ?>">
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
                <label for="message_body" class="form-label">Add Message</label>
                <textarea name="message_body" id="message_body" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
