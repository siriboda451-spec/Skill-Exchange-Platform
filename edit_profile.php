<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors['csrf'] = 'Security token expired. Please try again.';
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if ($fullName === '') {
        $errors['full_name'] = 'Full name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (mb_strlen($bio) > 500) {
        $errors['bio'] = 'Bio must be under 500 characters.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, bio = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$fullName, $email, $phone ?: null, $bio ?: null, $user['id']]);
        set_flash('success', 'Profile updated successfully.');
        redirect('profile.php?id=' . (int)$user['id']);
    }
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <p class="eyebrow">Edit profile</p>
        <h1>Update your account information</h1>
    </div>
</section>

<section class="section">
    <div class="container auth-wrap">
        <div class="card form-card">
            <form method="POST" class="form-grid" novalidate>
                <?php echo csrf_field(); ?>

                <div class="field">
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" type="text" value="<?php echo e($user['full_name']); ?>" required>
                    <?php if (!empty($errors['full_name'])): ?><small class="error"><?php echo e($errors['full_name']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="<?php echo e($user['email']); ?>" required>
                    <?php if (!empty($errors['email'])): ?><small class="error"><?php echo e($errors['email']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="phone">Phone number</label>
                    <input id="phone" name="phone" type="tel" value="<?php echo e($user['phone'] ?? ''); ?>">
                </div>

                <div class="field full-width">
                    <label for="bio">Short bio</label>
                    <textarea id="bio" name="bio" rows="5"><?php echo e($user['bio'] ?? ''); ?></textarea>
                    <?php if (!empty($errors['bio'])): ?><small class="error"><?php echo e($errors['bio']); ?></small><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Save changes</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
