<?php
$title = 'New Inquiry / Claim';
require_once __DIR__ . '/../includes/functions.php';
require_login('student');

$user = current_user($conn);
if (!$user) {
    redirect('/logout.php');
}

$teachers_result = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='teacher' AND is_active=1 ORDER BY full_name ASC");
$teachers = $teachers_result ? fetch_all($teachers_result) : [];
$staff_result = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='staff' AND is_active=1 ORDER BY full_name ASC");
$staff = $staff_result ? fetch_all($staff_result) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = sanitize_input($_POST['type'] ?? '');
    $subject = sanitize_input($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $recipients = $_POST['recipients'] ?? [];

    if (!in_array($type, ['inquiry', 'claim'], true) || !$subject || !$body) {
        flash_message('error', 'Subject, message, and type are required.');
    } elseif (!is_array($recipients) || count($recipients) === 0) {
        flash_message('error', 'Please select at least one teacher or staff member.');
    } else {
        $subject_db = mysqli_real_escape_string($conn, $subject);
        $type_db = mysqli_real_escape_string($conn, $type);
        $insert_conversation = "
            INSERT INTO conversations (subject, type, status, created_by, created_at, updated_at)
            VALUES ('{$subject_db}', '{$type_db}', 'open', {$user['id']}, NOW(), NOW())";
        if (mysqli_query($conn, $insert_conversation)) {
            $conversation_id = mysqli_insert_id($conn);
            $body_db = mysqli_real_escape_string($conn, $body);
            $message_query = "
                INSERT INTO messages (conversation_id, sender_id, body)
                VALUES ({$conversation_id}, {$user['id']}, '{$body_db}')";
            mysqli_query($conn, $message_query);

            $participant_values = [];
            $participant_values[] = "({$conversation_id}, {$user['id']})";
            foreach ($recipients as $recipient_id) {
                $recipient_id = (int) $recipient_id;
                if ($recipient_id > 0) {
                    $participant_values[] = "({$conversation_id}, {$recipient_id})";
                }
            }
            $participants_sql = "
                INSERT IGNORE INTO conversation_participants (conversation_id, user_id)
                VALUES " . implode(', ', $participant_values);
            mysqli_query($conn, $participants_sql);

            flash_message('success', 'Conversation created successfully.');
            redirect('/student/conversation.php?id=' . $conversation_id);
        } else {
            flash_message('error', 'Failed to create conversation. Please try again.');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>New Inquiry / Claim</span>
                <a href="/student/dashboard.php" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="">Select type</option>
                            <option value="inquiry">Inquiry</option>
                            <option value="claim">Claim</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="body" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Recipients</label>
                        <select name="recipients[]" class="form-select" multiple required size="6">
                            <optgroup label="Teachers">
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo (int) $teacher['id']; ?>"><?php echo sanitize_input($teacher['full_name']); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Office Staff">
                                <?php foreach ($staff as $member): ?>
                                    <option value="<?php echo (int) $member['id']; ?>"><?php echo sanitize_input($member['full_name']); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                        <div class="form-text">Hold Ctrl or Cmd to select multiple recipients.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Conversation</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
