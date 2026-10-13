<?php
require_once __DIR__ . '/includes/auth.php';

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$level = trim($_GET['level'] ?? '');

$sql = 'SELECT s.*, u.full_name AS provider_name, c.name AS category_name FROM skills s JOIN users u ON u.id = s.user_id JOIN categories c ON c.id = s.category_id WHERE s.is_active = 1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (s.name LIKE ? OR s.description LIKE ? OR u.full_name LIKE ?)';
    $keyword = '%' . $q . '%';
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}
if ($category !== '') {
    $sql .= ' AND c.id = ?';
    $params[] = $category;
}
if ($level !== '') {
    $sql .= ' AND s.level = ?';
    $params[] = $level;
}

$sql .= ' ORDER BY s.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$skills = $stmt->fetchAll();

$categories = get_categories();
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <p class="eyebrow">Discover</p>
        <h1>Explore skill listings</h1>
    </div>
</section>

<section class="section">
    <div class="container filter-panel card">
        <form method="GET" class="filter-form">
            <div class="field">
                <label for="q">Keyword</label>
                <input id="q" name="q" type="text" value="<?php echo e($q); ?>" placeholder="Search by skill or provider">
            </div>
            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($category == (string)$cat['id']) ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="level">Skill level</label>
                <select id="level" name="level">
                    <option value="">Any level</option>
                    <option value="Beginner" <?php echo ($level === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                    <option value="Intermediate" <?php echo ($level === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                    <option value="Advanced" <?php echo ($level === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Apply</button>
        </form>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="card-grid three-col">
            <?php if (empty($skills)): ?>
                <div class="empty-state card">
                    <h3>No results found</h3>
                    <p>Try another keyword or category filter.</p>
                </div>
            <?php else: ?>
                <?php foreach ($skills as $skill): ?>
                    <article class="card provider-card">
                        <div class="provider-top">
                            <div class="avatar"><?php echo e(get_user_initials($skill['provider_name'])); ?></div>
                            <div>
                                <h3><?php echo e($skill['provider_name']); ?></h3>
                                <p><?php echo e($skill['category_name']); ?></p>
                            </div>
                        </div>
                        <div class="skill-meta">
                            <span class="badge"><?php echo e($skill['level']); ?></span>
                            <span class="muted"><?php echo e($skill['learning_method']); ?></span>
                        </div>
                        <h4><?php echo e($skill['name']); ?></h4>
                        <p><?php echo e($skill['description']); ?></p>
                        <div class="card-actions">
                            <a href="profile.php?id=<?php echo (int)$skill['user_id']; ?>" class="btn btn-secondary">View profile</a>
                            <a href="requests.php?receiver_id=<?php echo (int)$skill['user_id']; ?>&skill_id=<?php echo (int)$skill['id']; ?>" class="btn btn-primary">Request</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
