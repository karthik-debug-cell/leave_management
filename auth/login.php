<?php
// ============================================================
// auth/login.php — Login Page
// ============================================================
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/session.php';

// Already logged in — redirect to correct dashboard
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: /leave_management/admin/dashboard.php");
    } else {
        header("Location: /leave_management/employee/dashboard.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please fill in both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            // --- Role must be either 'admin' or 'employee', nothing else ---
            if (!in_array($user['role'], ['admin', 'employee'])) {
                $error = 'Your account has an invalid role. Contact administrator.';
            } else {
                // Regenerate session ID (prevents session fixation attacks)
                session_regenerate_id(true);

                // Store only what we need in session
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role']; // 'admin' or 'employee'

                setFlash('success', "Welcome back, {$user['name']}!");

                // Redirect based on role — admin goes to admin, employee goes to employee
                if ($user['role'] === 'admin') {
                    header("Location: /leave_management/admin/dashboard.php");
                } else {
                    header("Location: /leave_management/employee/dashboard.php");
                }
                exit;
            }

        } else {
            // Don't reveal whether email or password was wrong (security best practice)
            $error = 'Incorrect email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — LeaveFlow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Fraunces:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/leave_management/assets/css/style.css">
</head>
<body>

<div class="login-wrapper">
  <div class="login-card animate-in">

    <div class="login-logo">
      <i class="bi bi-calendar2-check-fill"></i>
    </div>

    <h1 class="login-title">LeaveFlow</h1>
    <p class="login-subtitle">Sign in to manage your leave requests</p>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="bi bi-x-circle-fill flex-shrink-0"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <?php
      // Show flash message on login page (e.g. after logout)
      require_once dirname(__DIR__) . '/config/session.php';
      $flash = getFlash();
      if ($flash):
    ?>
      <div class="alert alert-<?= $flash['type'] ?> d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-info-circle-fill"></i>
        <div><?= htmlspecialchars($flash['message']) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>

      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0">
            <i class="bi bi-envelope text-muted"></i>
          </span>
          <input type="email" class="form-control border-start-0" id="email" name="email"
                 placeholder="you@company.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 required autofocus>
        </div>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0">
            <i class="bi bi-lock text-muted"></i>
          </span>
          <input type="password" class="form-control border-start-0" id="password"
                 name="password" placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>

    </form>

    <!-- Demo Credentials -->
    <div class="demo-creds mt-3">
      <strong><i class="bi bi-info-circle me-1"></i>Demo Credentials</strong><br>
      <span class="text-muted">Admin &nbsp;&nbsp;:</span> admin@company.com / <code>Admin@123</code><br>
      <span class="text-muted">Employee:</span> john@company.com &nbsp;/ <code>Emp@123</code>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/leave_management/assets/js/app.js"></script>
</body>
</html>
