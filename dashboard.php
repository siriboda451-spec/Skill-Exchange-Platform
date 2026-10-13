<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
$userId = (int) $user['id'];

$offeredSkills = $pdo->prepare('SELECT s.*, c.name AS category_name FROM skills s LEFT JOIN categories c ON c.id = s.category_id WHERE s.user_id = ? ORDER BY s.created_at DESC');
$offeredSkills->execute([$userId]);
$offeredSkills = $offeredSkills->fetchAll();

$learningGoals = $pdo->prepare('SELECT * FROM user_learning_goals WHERE user_id = ? ORDER BY created_at DESC');
$learningGoals->execute([$userId]);
$learningGoals = $learningGoals->fetchAll();

$incoming = $pdo->prepare('SELECT r.*, u.full_name AS sender_name FROM exchange_requests r JOIN users u ON u.id = r.sender_id WHERE r.receiver_id = ? ORDER BY r.created_at DESC LIMIT 5');
$incoming->execute([$userId]);
$incoming = $incoming->fetchAll();

$outgoing = $pdo->prepare('SELECT r.*, u.full_name AS receiver_name FROM exchange_requests r JOIN users u ON u.id = r.receiver_id WHERE r.sender_id = ? ORDER BY r.created_at DESC LIMIT 5');
$outgoing->execute([$userId]);
$outgoing = $outgoing->fetchAll();

$sessions = $pdo->prepare('SELECT s.*, u.full_name AS other_name FROM sessions s JOIN users u ON u.id = CASE WHEN s.sender_id = ? THEN s.receiver_id ELSE s.sender_id END WHERE s.sender_id = ? OR s.receiver_id = ? ORDER BY s.session_date DESC LIMIT 5');
$sessions->execute([$userId, $userId, $userId]);
$sessions = $sessions->fetchAll();

$notifications = get_notifications_for_user($userId);
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container dashboard-header">
        <div>
            <p class="eyebrow">Dashboard</p>
            <h1>Welcome, <?php echo e($user['full_name']); ?></h1>
        </div>
        <a href="profile.php?id=<?php echo (int)$user['id']; ?>" class="btn btn-primary">View profile</a>
    </div>
</section>

<section class="section">
    <div class="container profile-summary card">
        <div class="profile-summary-top">
            <div class="avatar large"><?php echo e(get_user_initials($user['full_name'])); ?></div>
            <div>
                <h2><?php echo e($user['full_name']); ?></h2>
                <p><?php echo e($user['bio'] ?: 'No bio added yet.'); ?></p>
            </div>
        </div>
        <div class="stats-grid compact">
            <div class="stat-card card">
                <strong><?php echo count($offeredSkills); ?></strong>
                <span>Skills offered</span>
            </div>
            <div class="stat-card card">
                <strong><?php echo count($learningGoals); ?></strong>
                <span>Learning goals</span>
            </div>
            <div class="stat-card card">
                <strong><?php echo count($incoming); ?></strong>
                <span>Incoming requests</span>
            </div>
            <div class="stat-card card">
                <strong><?php echo count($outgoing); ?></strong>
                <span>Sent requests</span>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container dashboard-grid">
        <div class="card">
            <h3>Skills offered</h3>
            <?php if (empty($offeredSkills)): ?>
                <p class="empty-state-text">No skills added yet.</p>
            <?php else: ?>
                <ul class="list-stack">
                    <?php foreach ($offeredSkills as $skill): ?>
                        <li>
                            <strong><?php echo e($skill['name']); ?></strong>
                            <span><?php echo e($skill['level']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Skills wanted</h3>
            <?php if (empty($learningGoals)): ?>
                <p class="empty-state-text">No learning goals added yet.</p>
            <?php else: ?>
                <ul class="list-stack">
                    <?php foreach ($learningGoals as $goal): ?>
                        <li>
                            <strong><?php echo e($goal['skill_name']); ?></strong>
                            <span><?php echo e($goal['goal_description']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Incoming requests</h3>
            <?php if (empty($incoming)): ?>
                <p class="empty-state-text">No incoming requests.</p>
            <?php else: ?>
                <ul class="list-stack">
                    <?php foreach ($incoming as $request): ?>
                        <li>
                            <strong><?php echo e($request['sender_name']); ?></strong>
                            <span><?php echo e($request['status']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Sent requests</h3>
            <?php if (empty($outgoing)): ?>
                <p class="empty-state-text">No outgoing requests.</p>
            <?php else: ?>
                <ul class="list-stack">
                    <?php foreach ($outgoing as $request): ?>
                        <li>
                            <strong><?php echo e($request['receiver_name']); ?></strong>
                            <span><?php echo e($request['status']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Upcoming sessions</h3>
            <?php if (empty($sessions)): ?>
                <p class="empty-state-text">No sessions scheduled.</p>
            <?php else: ?>
                <ul class="list-stack">
                    <?php foreach ($sessions as $session): ?>
                        <li>
                            <strong><?php echo e($session['other_name']); ?></strong>
                            <span><?php echo e($session['status']); ?> • <?php echo e($session['mode']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Recent notifications</h3>
            <?php if (empty($notifications)): ?>
                <p class="empty-state-text">No new notifications.</p>
            <?php else: ?>
                <ul class="list-stack">
                    <?php foreach ($notifications as $notification): ?>
                        <li>
                            <strong><?php echo e($notification['type']); ?></strong>
                            <span><?php echo e($notification['message']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section alt-section">
    <div class="container">
        <div class="quick-actions">
            <a href="add_skill.php" class="btn btn-primary">Add a skill</a>
            <a href="explore.php" class="btn btn-secondary">Explore skills</a>
            <a href="requests.php" class="btn btn-secondary">Manage requests</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
