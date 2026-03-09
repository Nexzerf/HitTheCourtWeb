<?php
require_once '../config.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email    = sanitize($_POST['email']    ?? '');
    $phone    = sanitize($_POST['phone']    ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username)) $errors['username'] = 'Username is required';
    if (empty($email))    $errors['email']    = 'Email is required';
    if (empty($phone))    $errors['phone']    = 'Phone number is required';
    if (empty($password))            $errors['password'] = 'Password is required';
    elseif (strlen($password) < 8)  $errors['password'] = 'Password must be at least 8 characters';

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            $errors['general'] = 'Username or Email already exists';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $phone, $hashedPassword])) {
                $success = 'Registration successful! You can now login.';
                $_POST = [];
            } else {
                $errors['general'] = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — Hit The Court</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../auth.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
</head>
<body>

<div class="auth-wrapper">

    <!-- ========== LEFT: FORM ========== -->
    <div class="auth-form-container">
        <div class="auth-form-box">

            <div class="auth-header">
                <p class="auth-eyebrow">HIT THE COURT</p>
                <h2 class="auth-title">SIGN UP</h2>
                <p class="auth-subtitle">Create your account and book courts today</p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert-error">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?= htmlspecialchars($errors['general']) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-success">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
                    <?= htmlspecialchars($success) ?> <a href="login">Login here →</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>

                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="username" name="username"
                               class="form-input <?= isset($errors['username']) ? 'error' : '' ?>"
                               placeholder="Choose a username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                    <?php if (isset($errors['username'])): ?>
                        <span class="form-error"><?= htmlspecialchars($errors['username']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password" name="password"
                               class="form-input <?= isset($errors['password']) ? 'error' : '' ?>"
                               placeholder="At least 8 characters"
                               minlength="8">
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <span class="form-error"><?= htmlspecialchars($errors['password']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <input type="email" id="email" name="email"
                               class="form-input <?= isset($errors['email']) ? 'error' : '' ?>"
                               placeholder="Enter your email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <span class="form-error"><?= htmlspecialchars($errors['email']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.21h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.86a16 16 0 0 0 6.13 6.13l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <input type="tel" id="phone" name="phone"
                               class="form-input <?= isset($errors['phone']) ? 'error' : '' ?>"
                               placeholder="Enter your phone number"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <?php if (isset($errors['phone'])): ?>
                        <span class="form-error"><?= htmlspecialchars($errors['phone']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-primary">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    SIGN UP
                </button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="<?= SITE_URL ?>/login">Login here →</a></p>
            </div>

        </div>
    </div>

    <!-- ========== RIGHT: HERO ========== -->
    <div class="auth-hero">

        <!-- Court grid art (SVG lines overlay) -->
        <div class="auth-hero-court-art">
            <svg viewBox="0 0 600 800" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                <!-- Outer court -->
                <rect x="60" y="80"  width="480" height="640" stroke="white" stroke-width="2"/>
                <!-- Center line -->
                <line x1="60"  y1="400" x2="540" y2="400" stroke="white" stroke-width="2"/>
                <!-- Service boxes -->
                <line x1="300" y1="80"  x2="300" y2="720" stroke="white" stroke-width="1.5"/>
                <line x1="60"  y1="220" x2="540" y2="220" stroke="white" stroke-width="1.5"/>
                <line x1="60"  y1="580" x2="540" y2="580" stroke="white" stroke-width="1.5"/>
                <!-- Tramlines -->
                <line x1="100" y1="80"  x2="100" y2="720" stroke="white" stroke-width="1"/>
                <line x1="500" y1="80"  x2="500" y2="720" stroke="white" stroke-width="1"/>
                <!-- Center mark -->
                <line x1="297" y1="398" x2="303" y2="402" stroke="white" stroke-width="2"/>
                <!-- Net -->
                <line x1="60"  y1="400" x2="540" y2="400" stroke="white" stroke-width="3" stroke-dasharray="8 4"/>
            </svg>
        </div>

        <img src="https://images.unsplash.com/photo-1554068865-24cecd4e34b8?auto=format&fit=crop&w=900&q=80"
             alt="Court" class="auth-hero-bg">

        <div class="auth-hero-content">
            <div class="auth-hero-tag">Members Only</div>

            <div class="auth-hero-logo">
                HIT THE <span class="accent">COURT</span>
            </div>

            <p class="auth-hero-title">
                Book your courts.<br>Track your game.
            </p>

            <div class="auth-hero-stats">
                <div class="stat-item">
                    <span class="stat-value">24<span class="stat-accent">+</span></span>
                    <span class="stat-label">Courts</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">3k<span class="stat-accent">+</span></span>
                    <span class="stat-label">Players</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">7<span class="stat-accent">/7</span></span>
                    <span class="stat-label">Open Days</span>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>