<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors['csrf'] = 'Security token expired. Please try again.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = get_user_by_email($email);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $errors['login'] = 'Invalid email or password.';
    } elseif ((int) $user['is_active'] !== 1) {
        $errors['login'] = 'Your account is inactive. Contact the administrator.';
    }

    if (empty($errors)) {
        create_session_for_user($user);
        set_flash('success', 'Welcome back, ' . e($user['full_name']) . '!');
        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
    }
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="auth-page">
    <div class="container auth-wrap">
        <div class="auth-card card">
            <p class="eyebrow">Welcome back</p>
            <h1>Login to your account</h1>
            <form method="POST" class="form-grid" novalidate>
                <?php echo csrf_field(); ?>

                <div class="field full-width">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" required>
                </div>

                <div class="field full-width password-field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                    <button type="button" class="toggle-password" aria-label="Show password">Show</button>
                </div>

                <?php if (!empty($errors['login'])): ?>
                    <div class="error-box"><?php echo e($errors['login']); ?></div>
                <?php endif; ?>

                <label class="checkbox-row">
                    <input type="checkbox" name="remember" value="1">
                    <span>Remember me</span>
                </label>

                <button type="submit" class="btn btn-primary full-width">Login</button>
                <p class="auth-link">New here? <a href="register.php">Create an account</a></p>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
