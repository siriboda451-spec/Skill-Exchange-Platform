<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$currentUserId = (int) $_SESSION['user_id'];
$otherUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

$partners = $pdo->prepare('SELECT DISTINCT u.id, u.full_name FROM users u JOIN exchange_requests r ON (u.id = r.sender_id OR u.id = r.receiver_id) WHERE (r.sender_id = ? OR r.receiver_id = ?) AND r.status = "Accepted" AND u.id != ? ORDER BY u.full_name ASC');
$partners->execute([$currentUserId, $currentUserId, $currentUserId]);
$partners = $partners->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (!verify_csrf()) {
        set_flash('error', 'Security token expired. Please try again.');
        redirect('messages.php');
    }

    $receiver = (int) ($_POST['receiver_id'] ?? 0);
    $messageText = trim($_POST['message'] ?? '');
    if ($receiver <= 0 || !user_can_access_conversation($receiver, $currentUserId) || $messageText === '') {
        set_flash('error', 'You are not allowed to send a message here.');
        redirect('messages.php');
    }

    $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$currentUserId, $receiver, $messageText]);
    create_notification($receiver, 'Message', 'You have a new message from another user.', null);
    set_flash('success', 'Message sent successfully.');
    redirect('messages.php?user_id=' . $receiver);
}

$messages = [];
if ($otherUserId > 0) {
    if (!user_can_access_conversation($otherUserId, $currentUserId)) {
        set_flash('error', 'You can only message users you have an accepted exchange with.');
        redirect('messages.php');
    }

    $stmt = $pdo->prepare('SELECT m.*, u.full_name AS sender_name FROM messages m JOIN users u ON u.id = m.sender_id WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) ORDER BY m.created_at ASC');
    $stmt->execute([$currentUserId, $otherUserId, $otherUserId, $currentUserId]);
    $messages = $stmt->fetchAll();
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <p class="eyebrow">Messages</p>
        <h1>Private conversations</h1>
    </div>
</section>

<section class="section">
    <div class="container messages-layout">
        <aside class="card conversation-list">
            <h3>Conversations</h3>
            <?php if (empty($partners)): ?>
                <p class="empty-state-text">No accepted conversations yet.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($partners as $partner): ?>
                        <li>
                            <a href="messages.php?user_id=<?php echo (int)$partner['id']; ?>" class="conversation-item <?php echo ($otherUserId === (int)$partner['id']) ? 'active' : ''; ?>">
                                <?php echo e($partner['full_name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </aside>

        <div class="card chat-panel">
            <?php if ($otherUserId > 0): ?>
                <?php $partner = get_user_by_id($otherUserId); ?>
                <h3>Chat with <?php echo e($partner['full_name']); ?></h3>
                <div class="chat-history">
                    <?php if (empty($messages)): ?>
                        <p class="empty-state-text">No messages yet. Start the conversation.</p>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="chat-bubble <?php echo ($message['sender_id'] == $currentUserId) ? 'mine' : 'theirs'; ?>">
                                <p><?php echo e($message['message']); ?></p>
                                <small><?php echo e($message['sender_name']); ?> • <?php echo e($message['created_at']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="POST" class="message-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="send_message" value="1">
                    <input type="hidden" name="receiver_id" value="<?php echo (int)$otherUserId; ?>">
                    <textarea name="message" rows="3" placeholder="Type your message..." required></textarea>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            <?php else: ?>
                <h3>Select a conversation</h3>
                <p class="empty-state-text">Choose a user from the left panel to start messaging.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
