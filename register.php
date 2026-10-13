<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$form = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'password' => '',
    'confirm_password' => '',
    'skills_offered' => '',
    'skills_to_learn' => '',
    'bio' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors['csrf'] = 'Security token expired. Please try again.';
    }

    $form['full_name'] = trim($_POST['full_name'] ?? '');
    $form['email'] = trim($_POST['email'] ?? '');
    $form['phone'] = trim($_POST['phone'] ?? '');
    $form['password'] = $_POST['password'] ?? '';
    $form['confirm_password'] = $_POST['confirm_password'] ?? '';
    $form['skills_offered'] = trim($_POST['skills_offered'] ?? '');
    $form['skills_to_learn'] = trim($_POST['skills_to_learn'] ?? '');
    $form['bio'] = trim($_POST['bio'] ?? '');

    if ($form['full_name'] === '') {
        $errors['full_name'] = 'Full name is required.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (get_user_by_email($form['email'])) {
        $errors['email'] = 'This email is already registered.';
    }
    if (strlen($form['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if ($form['password'] !== $form['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
    if ($form['skills_offered'] === '') {
        $errors['skills_offered'] = 'Please mention at least one skill you can teach.';
    }
    if ($form['skills_to_learn'] === '') {
        $errors['skills_to_learn'] = 'Please mention at least one skill you want to learn.';
    }
    if (mb_strlen($form['bio']) > 500) {
        $errors['bio'] = 'Bio must be shorter than 500 characters.';
    }

    if (empty($errors)) {
        $passwordHash = password_hash($form['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, password_hash, bio, role, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, "user", 1, NOW(), NOW())');
        $stmt->execute([$form['full_name'], $form['email'], $form['phone'] ?: null, $passwordHash, $form['bio'] ?: null]);

        $userId = $pdo->lastInsertId();
        if ($form['skills_offered'] !== '') {
            $stmt = $pdo->prepare('INSERT INTO skills (user_id, category_id, name, level, description, learning_method, is_active, created_at, updated_at) VALUES (?, 1, ?, "Beginner", ?, "Online", 1, NOW(), NOW())');
            $stmt->execute([$userId, trim($form['skills_offered']), 'User skill offered during registration.']);
        }
        if ($form['skills_to_learn'] !== '') {
            $stmt = $pdo->prepare('INSERT INTO user_learning_goals (user_id, category_id, skill_name, goal_description, created_at, updated_at) VALUES (?, 1, ?, ?, NOW(), NOW())');
            $stmt->execute([$userId, trim($form['skills_to_learn']), 'Learning goal added during registration.']);
        }

        set_flash('success', 'Account created successfully. Please log in.');
        redirect('login.php');
    }
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="auth-page">
    <div class="container auth-wrap">
        <div class="auth-card card">
            <p class="eyebrow">Join today</p>
            <h1>Create your account</h1>
            <form method="POST" class="form-grid" novalidate>
                <?php echo csrf_field(); ?>

                <div class="field">
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" type="text" value="<?php echo e($form['full_name']); ?>" required>
                    <?php if (!empty($errors['full_name'])): ?><small class="error"><?php echo e($errors['full_name']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="<?php echo e($form['email']); ?>" required>
                    <?php if (!empty($errors['email'])): ?><small class="error"><?php echo e($errors['email']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="phone">Phone number (optional)</label>
                    <input id="phone" name="phone" type="tel" value="<?php echo e($form['phone']); ?>">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                    <?php if (!empty($errors['password'])): ?><small class="error"><?php echo e($errors['password']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="confirm_password">Confirm password</label>
                    <input id="confirm_password" name="confirm_password" type="password" required>
                    <?php if (!empty($errors['confirm_password'])): ?><small class="error"><?php echo e($errors['confirm_password']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="skills_offered">Skills you can teach</label>
                    <input id="skills_offered" name="skills_offered" type="text" value="<?php echo e($form['skills_offered']); ?>" required>
                    <?php if (!empty($errors['skills_offered'])): ?><small class="error"><?php echo e($errors['skills_offered']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="skills_to_learn">Skills you want to learn</label>
                    <input id="skills_to_learn" name="skills_to_learn" type="text" value="<?php echo e($form['skills_to_learn']); ?>" required>
                    <?php if (!empty($errors['skills_to_learn'])): ?><small class="error"><?php echo e($errors['skills_to_learn']); ?></small><?php endif; ?>
                </div>

                <div class="field full-width">
                    <label for="bio">Short bio</label>
                    <textarea id="bio" name="bio" rows="4"><?php echo e($form['bio']); ?></textarea>
                    <?php if (!empty($errors['bio'])): ?><small class="error"><?php echo e($errors['bio']); ?></small><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary full-width">Submit</button>
                <p class="auth-link">Already have an account? <a href="login.php">Login</a></p>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
