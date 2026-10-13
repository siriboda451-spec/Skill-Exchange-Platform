<?php
require_once __DIR__ . '/includes/auth.php';

$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($userId <= 0) {
    redirect('explore.php');
}

$user = get_user_by_id($userId);
if (!$user) {
    set_flash('error', 'User profile not found.');
    redirect('explore.php');
}

$skillsOffered = $pdo->prepare('SELECT s.*, c.name AS category_name FROM skills s LEFT JOIN categories c ON c.id = s.category_id WHERE s.user_id = ? AND s.is_active = 1 ORDER BY s.created_at DESC');
$skillsOffered->execute([$userId]);
$skillsOffered = $skillsOffered->fetchAll();

$learningGoals = $pdo->prepare('SELECT * FROM user_learning_goals WHERE user_id = ? ORDER BY created_at DESC');
$learningGoals->execute([$userId]);
$learningGoals = $learningGoals->fetchAll();

$reviews = $pdo->prepare('SELECT r.*, u.full_name AS reviewer_name FROM reviews r JOIN users u ON u.id = r.reviewer_id WHERE r.reviewee_id = ? ORDER BY r.created_at DESC LIMIT 5');
$reviews->execute([$userId]);
$reviews = $reviews->fetchAll();

$avg = get_user_reviews_summary($userId);
$currentUser = current_user();
$isOwner = $currentUser && (int) $currentUser['id'] === $userId;
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container profile-header">
        <div class="profile-intro">
            <div class="avatar large"><?php echo e(get_user_initials($user['full_name'])); ?></div>
            <div>
                <p class="eyebrow">Profile</p>
                <h1><?php echo e($user['full_name']); ?></h1>
                <p><?php echo e($user['bio'] ?: 'This user has not added a bio yet.'); ?></p>
            </div>
        </div>
        <?php if ($isOwner): ?>
            <a href="edit_profile.php" class="btn btn-primary">Edit profile</a>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container profile-layout">
        <div class="card"> 
            <h3>Skills offered</h3>
            <?php if (empty($skillsOffered)): ?>
                <p class="empty-state-text">No skills offered yet.</p>
            <?php else: ?>
                <ul class="list-stack">
                    <?php foreach ($skillsOffered as $skill): ?>
                        <li>
                            <strong><?php echo e($skill['name']); ?></strong>
                            <span><?php echo e($skill['level']); ?> • <?php echo e($skill['category_name']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Skills wanted</h3>
            <?php if (empty($learningGoals)): ?>
                <p class="empty-state-text">No learning goals registered.</p>
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
            <h3>Ratings and reviews</h3>
            <p><strong><?php echo number_format((float) ($avg['average_rating'] ?: 0), 1); ?> / 5</strong> from <?php echo (int) ($avg['total_reviews'] ?: 0); ?> reviews</p>
            <?php if (empty($reviews)): ?>
                <p class="empty-state-text">No reviews yet.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <strong><?php echo e($review['reviewer_name']); ?></strong>
                        <span><?php echo str_repeat('★', (int)$review['rating']); ?></span>
                        <p><?php echo e($review['comment']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (!$isOwner): ?>
<section class="section alt-section">
    <div class="container">
        <div class="quick-actions">
            <a href="requests.php?receiver_id=<?php echo (int)$user['id']; ?>" class="btn btn-primary">Request a skill exchange</a>
            <a href="messages.php?user_id=<?php echo (int)$user['id']; ?>" class="btn btn-secondary">Message</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
