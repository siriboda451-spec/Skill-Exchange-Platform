<?php
require_once __DIR__ . '/includes/header.php';

$stats = [
    'users' => $pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn(),
    'skills' => $pdo->query('SELECT COUNT(*) FROM skills WHERE is_active = 1')->fetchColumn(),
    'requests' => $pdo->query('SELECT COUNT(*) FROM exchange_requests')->fetchColumn(),
];

$categories = get_categories();
$featuredSkills = $pdo->query('SELECT s.*, u.full_name AS provider_name, c.name AS category_name FROM skills s JOIN users u ON u.id = s.user_id JOIN categories c ON c.id = s.category_id WHERE s.is_active = 1 ORDER BY s.created_at DESC LIMIT 3')->fetchAll();
?>

<section class="hero">
    <div class="container hero-grid">
        <div class="hero-content">
            <span class="eyebrow">Mutual learning community</span>
            <h1>Share Your Skills, Learn Something New</h1>
            <p>Find mentors, exchange knowledge, and build real connections through practical skill-sharing sessions.</p>
            <div class="hero-actions">
                <a href="explore.php" class="btn btn-primary">Explore Skills</a>
                <a href="register.php" class="btn btn-secondary">Start Sharing</a>
            </div>
            <form class="search-box" method="GET" action="explore.php">
                <input type="text" name="q" placeholder="Search for a skill or keyword" aria-label="Search skills">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="hero-card card">
            <h3>Popular categories</h3>
            <div class="tag-list">
                <?php foreach ($categories as $category): ?>
                    <span class="tag"><?php echo e($category['name']); ?></span>
                <?php endforeach; ?>
            </div>
            <div class="mini-stats">
                <div>
                    <strong><?php echo $stats['users']; ?></strong>
                    <span>Active users</span>
                </div>
                <div>
                    <strong><?php echo $stats['skills']; ?></strong>
                    <span>Skills listed</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow">Explore</p>
            <h2>Featured skill providers</h2>
        </div>
        <div class="card-grid three-col">
            <?php foreach ($featuredSkills as $skill): ?>
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
                    <p><?php echo e(substr($skill['description'], 0, 110)); ?><?php echo strlen($skill['description']) > 110 ? '...' : ''; ?></p>
                    <div class="card-actions">
                        <a href="profile.php?id=<?php echo (int)$skill['user_id']; ?>" class="btn btn-secondary">View profile</a>
                        <a href="requests.php?receiver_id=<?php echo (int)$skill['user_id']; ?>&skill_id=<?php echo (int)$skill['id']; ?>" class="btn btn-primary">Request exchange</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section alt-section">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow">How it works</p>
            <h2>Three simple steps to start learning</h2>
        </div>
        <div class="steps-grid">
            <div class="step-card card">
                <span class="step-number">1</span>
                <h3>Create an Account</h3>
                <p>Sign up in minutes and build your learning profile with the skills you offer and want to learn.</p>
            </div>
            <div class="step-card card">
                <span class="step-number">2</span>
                <h3>Share or Find Skills</h3>
                <p>Browse through teachers, categories, and skill listings that match your goals and interests.</p>
            </div>
            <div class="step-card card">
                <span class="step-number">3</span>
                <h3>Start Learning</h3>
                <p>Send a request, schedule a session, and begin a collaborative exchange with your peer.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container stats-grid">
        <div class="stat-card card">
            <strong>1800+</strong>
            <span>Skill exchanges</span>
        </div>
        <div class="stat-card card">
            <strong>94%</strong>
            <span>Member satisfaction</span>
        </div>
        <div class="stat-card card">
            <strong>24/7</strong>
            <span>Learning support</span>
        </div>
        <div class="stat-card card">
            <strong>12k</strong>
            <span>Session hours</span>
        </div>
    </div>
</section>

<section class="section alt-section">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow">Testimonials</p>
            <h2>Students love learning together</h2>
        </div>
        <div class="testimonial-grid">
            <div class="card testimonial">
                <p>“I exchanged design feedback with a fellow student and improved my portfolio dramatically.”</p>
                <h4>Riya S.</h4>
            </div>
            <div class="card testimonial">
                <p>“I learned Python from a peer and later helped them with presentation and communication skills.”</p>
                <h4>Aman K.</h4>
            </div>
            <div class="card testimonial">
                <p>“The platform makes knowledge sharing easy, practical, and genuinely motivating.”</p>
                <h4>Meera T.</h4>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
