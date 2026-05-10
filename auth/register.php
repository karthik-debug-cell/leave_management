<?php
// ============================================================
// auth/register.php — Register New Employee (Admin Only)
// This page is accessible only to admins via the Employees panel.
// ============================================================
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/session.php';
requireAdmin();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name']       ?? '');
    $email      = trim($_POST['email']      ?? '');
    $password   = trim($_POST['password']   ?? '');
    $role       = trim($_POST['role']       ?? 'employee');
    $department = trim($_POST['department'] ?? '');

    // Validate
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!in_array($role, ['admin', 'employee'])) {
        $error = 'Invalid role selected.';
    } else {
        $db   = getDB();

        // Check for duplicate email
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'A user with this email already exists.';
        } else {
            // Hash password using bcrypt
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $db->prepare(
                "INSERT INTO users (name, email, password, role, department) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $email, $hashed, $role, $department]);

            setFlash('success', "User '$name' created successfully.");
            header("Location: /leave_management/admin/employees.php");
            exit;
        }
    }
}

$pageTitle = 'Add Employee';
include dirname(__DIR__) . '/components/head.php';
?>
<?php include dirname(__DIR__) . '/components/navbar.php'; ?>
<?php include dirname(__DIR__) . '/components/flash.php'; ?>

<div class="page-wrapper">
  <div class="container-fluid px-4">

    <div class="page-header d-flex align-items-center gap-3">
      <a href="/leave_management/admin/employees.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
      </a>
      <div>
        <h1 class="page-title mb-0">Add New Employee</h1>
        <p class="page-subtitle">Create a new user account</p>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-md-7 col-lg-6">
        <div class="card">
          <div class="card-body">

            <?php if ($error): ?>
              <div class="alert alert-danger d-flex align-items-center gap-2">
                <i class="bi bi-x-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="">

              <div class="mb-3">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control"
                       placeholder="Min. 6 characters" required>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Role <span class="text-danger">*</span></label>
                  <select name="role" class="form-select" required>
                    <option value="employee" <?= (($_POST['role'] ?? '') === 'employee') ? 'selected' : '' ?>>Employee</option>
                    <option value="admin"    <?= (($_POST['role'] ?? '') === 'admin')    ? 'selected' : '' ?>>Admin</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Department</label>
                  <input type="text" name="department" class="form-control"
                         value="<?= htmlspecialchars($_POST['department'] ?? '') ?>"
                         placeholder="e.g. Engineering">
                </div>
              </div>

              <div class="d-flex gap-2 justify-content-end pt-2">
                <a href="/leave_management/admin/employees.php" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-person-plus-fill me-1"></i>Create User
                </button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
