<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$errors = [];
$categories = get_categories();

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
        $errors['category'] = 'Please select a category.';
    }
    if (!in_array($level, ['Beginner', 'Intermediate', 'Advanced'], true)) {
        $errors['level'] = 'Please choose a valid proficiency level.';
    }
    if ($description === '') {
        $errors['description'] = 'Please describe your skill.';
    }
    if (!in_array($learningMethod, ['Online', 'In-person', 'Hybrid'], true)) {
        $errors['learning_method'] = 'Please choose a learning method.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO skills (user_id, category_id, name, level, description, learning_method, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())');
        $stmt->execute([$_SESSION['user_id'], $categoryId, $name, $level, $description, $learningMethod]);

        set_flash('success', 'Your skill has been added successfully.');
        redirect('dashboard.php');
    }
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <p class="eyebrow">Teach a skill</p>
        <h1>Add a new skill listing</h1>
    </div>
</section>

<section class="section">
    <div class="container auth-wrap">
        <div class="card form-card">
            <form method="POST" class="form-grid" novalidate>
                <?php echo csrf_field(); ?>

                <div class="field">
                    <label for="name">Skill name</label>
                    <input id="name" name="name" type="text" required>
                    <?php if (!empty($errors['name'])): ?><small class="error"><?php echo e($errors['name']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int)$category['id']; ?>"><?php echo e($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['category'])): ?><small class="error"><?php echo e($errors['category']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="level">Proficiency level</label>
                    <select id="level" name="level" required>
                        <option value="">Select level</option>
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                    <?php if (!empty($errors['level'])): ?><small class="error"><?php echo e($errors['level']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="learning_method">Preferred learning method</label>
                    <select id="learning_method" name="learning_method" required>
                        <option value="">Select method</option>
                        <option value="Online">Online</option>
                        <option value="In-person">In-person</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                    <?php if (!empty($errors['learning_method'])): ?><small class="error"><?php echo e($errors['learning_method']); ?></small><?php endif; ?>
                </div>

                <div class="field full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" required></textarea>
                    <?php if (!empty($errors['description'])): ?><small class="error"><?php echo e($errors['description']); ?></small><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Save skill</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
