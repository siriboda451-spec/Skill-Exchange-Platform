<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$currentUser = current_user();
$currentUserId = (int) $currentUser['id'];
$errors = [];
$mySkills = $pdo->prepare('SELECT * FROM skills WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC');
$mySkills->execute([$currentUserId]);
$mySkills = $mySkills->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    if (!verify_csrf()) {
        $errors['csrf'] = 'Security token expired. Please try again.';
    }

    $receiverId = (int) ($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $offeredSkillId = (int) ($_POST['offered_skill_id'] ?? 0);
    $requestedSkillId = (int) ($_POST['requested_skill_id'] ?? 0);

    if ($receiverId <= 0 || $receiverId === $currentUserId) {
        $errors['receiver'] = 'Please select a valid recipient.';
    }
    if ($message === '') {
        $errors['message'] = 'Please describe what you want to learn.';
    }

    if (empty($errors)) {
        $offeredSkill = $offeredSkillId ? get_skill_by_id($offeredSkillId) : null;
        $requestedSkill = $requestedSkillId ? get_skill_by_id($requestedSkillId) : null;

        $stmt = $pdo->prepare('INSERT INTO exchange_requests (sender_id, receiver_id, offered_skill_id, requested_skill_id, offered_skill_name, requested_skill_name, message, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, "Pending", NOW(), NOW())');
        $stmt->execute([
            $currentUserId,
            $receiverId,
            $offeredSkillId ?: null,
            $requestedSkillId ?: null,
            $offeredSkill['name'] ?? null,
            $requestedSkill['name'] ?? null,
            $message,
        ]);

        $requestId = $pdo->lastInsertId();
        create_notification($receiverId, 'Exchange request', 'You have received a new skill exchange request.', $requestId);
        set_flash('success', 'Your request has been sent successfully.');
        redirect('requests.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM exchange_requests WHERE id = ? LIMIT 1');
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        set_flash('error', 'Request not found.');
        redirect('requests.php');
    }

    if ($request['receiver_id'] == $currentUserId && in_array($status, ['Accepted', 'Rejected'], true)) {
        $pdo->prepare('UPDATE exchange_requests SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $requestId]);
        create_notification($request['sender_id'], 'Request update', 'Your exchange request status has been updated to ' . $status . '.', $requestId);
        set_flash('success', 'The request status has been updated.');
        redirect('requests.php');
    }

    if ($request['sender_id'] == $currentUserId && $status === 'Cancelled') {
        $pdo->prepare('UPDATE exchange_requests SET status = ?, updated_at = NOW() WHERE id = ?')->execute(['Cancelled', $requestId]);
        create_notification($request['receiver_id'], 'Request cancelled', 'A skill exchange request has been cancelled by the sender.', $requestId);
        set_flash('success', 'The request has been cancelled.');
        redirect('requests.php');
    }

    set_flash('error', 'You are not authorized to change this request.');
    redirect('requests.php');
}

$incoming = $pdo->prepare('SELECT r.*, u.full_name AS sender_name, s.name AS requested_skill_name FROM exchange_requests r JOIN users u ON u.id = r.sender_id LEFT JOIN skills s ON s.id = r.requested_skill_id WHERE r.receiver_id = ? ORDER BY r.created_at DESC');
$incoming->execute([$currentUserId]);
$incoming = $incoming->fetchAll();

$outgoing = $pdo->prepare('SELECT r.*, u.full_name AS receiver_name, s.name AS requested_skill_name FROM exchange_requests r JOIN users u ON u.id = r.receiver_id LEFT JOIN skills s ON s.id = r.requested_skill_id WHERE r.sender_id = ? ORDER BY r.created_at DESC');
$outgoing->execute([$currentUserId]);
$outgoing = $outgoing->fetchAll();

$receiverId = isset($_GET['receiver_id']) ? (int) $_GET['receiver_id'] : 0;
$requestedSkillId = isset($_GET['skill_id']) ? (int) $_GET['skill_id'] : 0;
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <p class="eyebrow">Exchange requests</p>
        <h1>Manage skill exchange requests</h1>
    </div>
</section>

<section class="section">
    <div class="container request-layout">
        <div class="card">
            <h3>Send a skill exchange request</h3>
            <form method="POST" class="form-grid" novalidate>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="send">
                <div class="field">
                    <label for="receiver_id">Recipient</label>
                    <input id="receiver_id" name="receiver_id" type="number" min="1" value="<?php echo e((string)$receiverId); ?>" placeholder="User ID">
                    <?php if (!empty($errors['receiver'])): ?><small class="error"><?php echo e($errors['receiver']); ?></small><?php endif; ?>
                </div>
                <div class="field">
                    <label for="offered_skill_id">Your offered skill</label>
                    <select id="offered_skill_id" name="offered_skill_id">
                        <option value="">Select a skill</option>
                        <?php foreach ($mySkills as $skill): ?>
                            <option value="<?php echo (int)$skill['id']; ?>"><?php echo e($skill['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="requested_skill_id">Requested skill</label>
                    <input id="requested_skill_id" name="requested_skill_id" type="number" min="1" value="<?php echo e((string)$requestedSkillId); ?>" placeholder="Skill ID">
                </div>
                <div class="field full-width">
                    <label for="message">What do you want to learn?</label>
                    <textarea id="message" name="message" rows="4" placeholder="I want to learn X and can teach Y in exchange."></textarea>
                    <?php if (!empty($errors['message'])): ?><small class="error"><?php echo e($errors['message']); ?></small><?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">Send request</button>
            </form>
        </div>

        <div class="card">
            <h3>Incoming requests</h3>
            <?php if (empty($incoming)): ?>
                <p class="empty-state-text">No incoming requests.</p>
            <?php else: ?>
                <?php foreach ($incoming as $request): ?>
                    <div class="request-item">
                        <div class="request-head">
                            <strong><?php echo e($request['sender_name']); ?></strong>
                            <span class="badge"><?php echo e($request['status']); ?></span>
                        </div>
                        <p><?php echo e($request['message']); ?></p>
                        <div class="request-actions">
                            <?php if ($request['status'] === 'Pending'): ?>
                                <form method="POST" class="inline-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                    <input type="hidden" name="status" value="Accepted">
                                    <button type="submit" class="btn btn-primary">Accept</button>
                                </form>
                                <form method="POST" class="inline-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                    <input type="hidden" name="status" value="Rejected">
                                    <button type="submit" class="btn btn-danger">Reject</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Outgoing requests</h3>
            <?php if (empty($outgoing)): ?>
                <p class="empty-state-text">No outgoing requests.</p>
            <?php else: ?>
                <?php foreach ($outgoing as $request): ?>
                    <div class="request-item">
                        <div class="request-head">
                            <strong><?php echo e($request['receiver_name']); ?></strong>
                            <span class="badge"><?php echo e($request['status']); ?></span>
                        </div>
                        <p><?php echo e($request['message']); ?></p>
                        <?php if ($request['status'] === 'Pending'): ?>
                            <form method="POST" class="inline-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                <input type="hidden" name="status" value="Cancelled">
                                <button type="submit" class="btn btn-secondary">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
