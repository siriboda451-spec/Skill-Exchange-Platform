<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$currentUserId = (int) $_SESSION['user_id'];
$partners = $pdo->prepare('SELECT DISTINCT u.id, u.full_name FROM users u JOIN exchange_requests r ON (u.id = r.sender_id OR u.id = r.receiver_id) WHERE (r.sender_id = ? OR r.receiver_id = ?) AND r.status = "Accepted" AND u.id != ? ORDER BY u.full_name ASC');
$partners->execute([$currentUserId, $currentUserId, $currentUserId]);
$partners = $partners->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_session'])) {
    if (!verify_csrf()) {
        set_flash('error', 'Security token expired. Please try again.');
        redirect('sessions.php');
    }

    $receiverId = (int) ($_POST['receiver_id'] ?? 0);
    $sessionDate = trim($_POST['session_date'] ?? '');
    $mode = trim($_POST['mode'] ?? '');
    if ($receiverId <= 0 || $sessionDate === '' || !in_array($mode, ['Online', 'In-person'], true) || !user_can_access_conversation($receiverId, $currentUserId)) {
        set_flash('error', 'Please provide valid session details.');
        redirect('sessions.php');
    }

    $stmt = $pdo->prepare('INSERT INTO sessions (request_id, sender_id, receiver_id, session_date, mode, status, notes, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, "Proposed", "Session proposed by user.", NOW(), NOW())');
    $stmt->execute([$currentUserId, $receiverId, $sessionDate, $mode]);
    create_notification($receiverId, 'Session request', 'A new skill exchange session has been proposed.', null);
    set_flash('success', 'Session proposal created successfully.');
    redirect('sessions.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_session'])) {
    $sessionId = (int) ($_POST['session_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM sessions WHERE id = ? LIMIT 1');
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();

    if (!$session || !($session['sender_id'] == $currentUserId || $session['receiver_id'] == $currentUserId)) {
        set_flash('error', 'You are not allowed to update this session.');
        redirect('sessions.php');
    }

    $validStatuses = ['Proposed', 'Confirmed', 'Rescheduled', 'Cancelled', 'Completed'];
    if (!in_array($status, $validStatuses, true)) {
        set_flash('error', 'Invalid session status.');
        redirect('sessions.php');
    }

    $pdo->prepare('UPDATE sessions SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $sessionId]);
    set_flash('success', 'Session status updated.');
    redirect('sessions.php');
}

$sessions = $pdo->prepare('SELECT s.*, u.full_name AS other_name FROM sessions s JOIN users u ON u.id = CASE WHEN s.sender_id = ? THEN s.receiver_id ELSE s.sender_id END WHERE s.sender_id = ? OR s.receiver_id = ? ORDER BY s.session_date DESC');
$sessions->execute([$currentUserId, $currentUserId, $currentUserId]);
$sessions = $sessions->fetchAll();
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <p class="eyebrow">Sessions</p>
        <h1>Schedule and manage sessions</h1>
    </div>
</section>

<section class="section">
    <div class="container request-layout">
        <div class="card">
            <h3>Propose a session</h3>
            <form method="POST" class="form-grid">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="add_session" value="1">
                <div class="field">
                    <label for="receiver_id">Participant</label>
                    <select id="receiver_id" name="receiver_id" required>
                        <option value="">Select a user</option>
                        <?php foreach ($partners as $partner): ?>
                            <option value="<?php echo (int)$partner['id']; ?>"><?php echo e($partner['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="session_date">Date & time</label>
                    <input id="session_date" name="session_date" type="datetime-local" required>
                </div>
                <div class="field">
                    <label for="mode">Meeting mode</label>
                    <select id="mode" name="mode" required>
                        <option value="">Select mode</option>
                        <option value="Online">Online</option>
                        <option value="In-person">In-person</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Create session</button>
            </form>
        </div>

        <div class="card">
            <h3>Scheduled sessions</h3>
            <?php if (empty($sessions)): ?>
                <p class="empty-state-text">No sessions scheduled.</p>
            <?php else: ?>
                <?php foreach ($sessions as $session): ?>
                    <div class="request-item">
                        <div class="request-head">
                            <strong><?php echo e($session['other_name']); ?></strong>
                            <span class="badge"><?php echo e($session['status']); ?></span>
                        </div>
                        <p><?php echo e($session['session_date']); ?> • <?php echo e($session['mode']); ?></p>
                        <div class="request-actions">
                            <form method="POST" class="inline-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="update_session" value="1">
                                <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">
                                <input type="hidden" name="status" value="Confirmed">
                                <button type="submit" class="btn btn-primary">Confirm</button>
                            </form>
                            <form method="POST" class="inline-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="update_session" value="1">
                                <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">
                                <input type="hidden" name="status" value="Cancelled">
                                <button type="submit" class="btn btn-danger">Cancel</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
