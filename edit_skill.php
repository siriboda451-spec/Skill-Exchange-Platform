<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$skillId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$skill = $skillId ? get_skill_by_id($skillId) : null;

if (!$skill || !user_can_manage_skill($skillId, (int) $_SESSION['user_id'])) {
    set_flash('error', 'You are not allowed to edit this skill.');
    redirect('dashboard.php');
}

$categories = get_categories();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors['csrf'] = 'Security token expired. Please try again.';
    }

    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $level = trim($_POST['level'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $learningMethod = trim($_POST['learning_method'] ?? '');

    if ($name === '') {
        $errors['name'] = 'Skill name is required.';
    }
    if ($categoryId <= 0) {
        $errors['category'] = 'Category is required.';
    }
    if (!in_array($level, ['Beginner', 'Intermediate', 'Advanced'], true)) {
        $errors['level'] = 'Please select a valid level.';
    }
    if ($description === '') {
        $errors['description'] = 'Description is required.';
    }
    if (!in_array($learningMethod, ['Online', 'In-person', 'Hybrid'], true)) {
        $errors['learning_method'] = 'Please select a valid learning method.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE skills SET category_id = ?, name = ?, level = ?, description = ?, learning_method = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
        $stmt->execute([$categoryId, $name, $level, $description, $learningMethod, $skillId, $_SESSION['user_id']]);
        set_flash('success', 'Skill updated successfully.');
        redirect('dashboard.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_skill'])) {
    $stmt = $pdo->prepare('DELETE FROM skills WHERE id = ? AND user_id = ?');
    $stmt->execute([$skillId, $_SESSION['user_id']]);
    set_flash('success', 'Skill deleted successfully.');
    redirect('dashboard.php');
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <p class="eyebrow">Edit listing</p>
        <h1>Update your skill</h1>
    </div>
</section>

<section class="section">
    <div class="container auth-wrap">
        <div class="card form-card">
            <form method="POST" class="form-grid" novalidate>
                <?php echo csrf_field(); ?>

                <div class="field">
                    <label for="name">Skill name</label>
                    <input id="name" name="name" type="text" value="<?php echo e($skill['name']); ?>" required>
                    <?php if (!empty($errors['name'])): ?><small class="error"><?php echo e($errors['name']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int)$category['id']; ?>" <?php echo ($category['id'] == $skill['category_id']) ? 'selected' : ''; ?>><?php echo e($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['category'])): ?><small class="error"><?php echo e($errors['category']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="level">Proficiency level</label>
                    <select id="level" name="level" required>
                        <option value="Beginner" <?php echo ($skill['level'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                        <option value="Intermediate" <?php echo ($skill['level'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="Advanced" <?php echo ($skill['level'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                    </select>
                    <?php if (!empty($errors['level'])): ?><small class="error"><?php echo e($errors['level']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="learning_method">Preferred learning method</label>
                    <select id="learning_method" name="learning_method" required>
                        <option value="Online" <?php echo ($skill['learning_method'] === 'Online') ? 'selected' : ''; ?>>Online</option>
                        <option value="In-person" <?php echo ($skill['learning_method'] === 'In-person') ? 'selected' : ''; ?>>In-person</option>
                        <option value="Hybrid" <?php echo ($skill['learning_method'] === 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                    </select>
                    <?php if (!empty($errors['learning_method'])): ?><small class="error"><?php echo e($errors['learning_method']); ?></small><?php endif; ?>
                </div>

                <div class="field full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" required><?php echo e($skill['description']); ?></textarea>
                    <?php if (!empty($errors['description'])): ?><small class="error"><?php echo e($errors['description']); ?></small><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Update skill</button>
            </form>

            <form method="POST" class="delete-form" onsubmit="return confirm('Delete this skill listing?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="delete_skill" value="1">
                <button type="submit" class="btn btn-danger">Delete skill</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
