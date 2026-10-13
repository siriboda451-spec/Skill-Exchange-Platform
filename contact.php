<?php
require_once __DIR__ . '/includes/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors['csrf'] = 'Security token expired. Please try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') {
        $errors['name'] = 'Please enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($subject === '') {
        $errors['subject'] = 'Please enter a subject.';
    }
    if ($message === '') {
        $errors['message'] = 'Please write your message.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$name, $email, $subject, $message]);
        set_flash('success', 'Your message has been sent successfully.');
        redirect('contact.php');
    }
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <p class="eyebrow">Contact</p>
        <h1>We would love to hear from you</h1>
    </div>
</section>

<section class="section">
    <div class="container contact-layout">
        <div class="card">
            <h3>Reach out</h3>
            <p>For questions, partnerships, or support, send us a message and we will get back to you soon.</p>
            <ul class="contact-list">
                <li>Email: hello@skillx.com</li>
                <li>Phone: +91 98765 43210</li>
                <li>Location: Campus Tech Hub</li>
            </ul>
        </div>
        <div class="card form-card">
            <form method="POST" class="form-grid" novalidate>
                <?php echo csrf_field(); ?>

                <div class="field">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" required>
                    <?php if (!empty($errors['name'])): ?><small class="error"><?php echo e($errors['name']); ?></small><?php endif; ?>
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required>
                    <?php if (!empty($errors['email'])): ?><small class="error"><?php echo e($errors['email']); ?></small><?php endif; ?>
                </div>

                <div class="field full-width">
                    <label for="subject">Subject</label>
                    <input id="subject" name="subject" type="text" required>
                    <?php if (!empty($errors['subject'])): ?><small class="error"><?php echo e($errors['subject']); ?></small><?php endif; ?>
                </div>

                <div class="field full-width">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                    <?php if (!empty($errors['message'])): ?><small class="error"><?php echo e($errors['message']); ?></small><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Send message</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
