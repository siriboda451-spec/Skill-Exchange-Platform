<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$currentUserId = (int) $_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!verify_csrf()) {
        set_flash('error', 'Security token expired. Please try again.');
        redirect('reviews.php');
    }

    $requestId = (int) ($_POST['request_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM exchange_requests WHERE id = ? AND status = "Accepted" LIMIT 1');
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if (!$request || !((int)$request['sender_id'] === $currentUserId || (int)$request['receiver_id'] === $currentUserId)) {
        set_flash('error', 'You are not eligible to review this exchange.');
        redirect('reviews.php');
    }

    $revieweeId = (int) $request['sender_id'] === $currentUserId ? (int) $request['receiver_id'] : (int) $request['sender_id'];
    $existing = $pdo->prepare('SELECT id FROM reviews WHERE request_id = ? AND reviewer_id = ? LIMIT 1');
    $existing->execute([$requestId, $currentUserId]);
    if ($existing->fetch()) {
        set_flash('error', 'You have already reviewed this exchange.');
        redirect('reviews.php');
    }

    if ($rating < 1 || $rating > 5) {
        $errors['rating'] = 'Rating must be between 1 and 5.';
    }
    if ($comment === '') {
        $errors['comment'] = 'Please add a short feedback comment.';
    }

    if (empty($errors)) {
        $insert = $pdo->prepare('INSERT INTO reviews (request_id, reviewer_id, reviewee_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $insert->execute([$requestId, $currentUserId, $revieweeId, $rating, $comment]);
        create_notification($revieweeId, 'Review', 'You received a new review from a completed exchange.', $requestId);
        set_flash('success', 'Your review has been submitted successfully.');
        redirect('reviews.php');
    }
}

$eligible = $pdo->prepare('SELECT r.id, r.status, u.full_name AS partner_name, CASE WHEN r.sender_id = ? THEN r.receiver_id ELSE r.sender_id END AS partner_id FROM exchange_requests r JOIN users u ON u.id = CASE WHEN r.sender_id = ? THEN r.receiver_id ELSE r.sender_id END WHERE r.status = "Accepted" AND (r.sender_id = ? OR r.receiver_id = ?) AND r.id NOT IN (SELECT request_id FROM reviews WHERE reviewer_id = ?) ORDER BY r.created_at DESC');
$eligible->execute([$currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId]);
$eligible = $eligible->fetchAll();

$received = $pdo->prepare('SELECT r.*, u.full_name AS reviewer_name FROM reviews r JOIN users u ON u.id = r.reviewer_id WHERE r.reviewee_id = ? ORDER BY r.created_at DESC');
$received->execute([$currentUserId]);
$received = $received->fetchAll();
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <p class="eyebrow">Reviews</p>
        <h1>Feedback and ratings</h1>
    </div>
</section>

<section class="section">
    <div class="container request-layout">
        <div class="card">
            <h3>Write a review</h3>
            <?php if (empty($eligible)): ?>
                <p class="empty-state-text">No eligible exchanges to review yet.</p>
            <?php else: ?>
                <form method="POST" class="form-grid">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="submit_review" value="1">
                    <div class="field">
                        <label for="request_id">Exchange</label>
                        <select id="request_id" name="request_id" required>
                            <option value="">Select a completed exchange</option>
                            <?php foreach ($eligible as $item): ?>
                                <option value="<?php echo (int)$item['id']; ?>"><?php echo e($item['partner_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="rating">Rating</label>
                        <select id="rating" name="rating" required>
                            <option value="">Choose rating</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?></option>
                            <?php endfor; ?>
                        </select>
                        <?php if (!empty($errors['rating'])): ?><small class="error"><?php echo e($errors['rating']); ?></small><?php endif; ?>
                    </div>
                    <div class="field full-width">
                        <label for="comment">Review</label>
                        <textarea id="comment" name="comment" rows="4" placeholder="Share your feedback from the exchange."></textarea>
                        <?php if (!empty($errors['comment'])): ?><small class="error"><?php echo e($errors['comment']); ?></small><?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit review</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Reviews received</h3>
            <?php if (empty($received)): ?>
                <p class="empty-state-text">No reviews received yet.</p>
            <?php else: ?>
                <?php foreach ($received as $review): ?>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
