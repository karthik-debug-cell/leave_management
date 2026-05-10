<?php
// ============================================================
// employee/apply.php — Apply for Leave
// ============================================================
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/session.php';
requireEmployee();

$db   = getDB();
$user = currentUser();
$uid  = $user['id'];

$leaveTypes = ['Annual', 'Sick', 'Casual', 'Maternity', 'Paternity', 'Unpaid'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = trim($_POST['leave_type'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date   = trim($_POST['end_date']   ?? '');
    $reason     = trim($_POST['reason']     ?? '');

    // Validate
    if (empty($leave_type) || empty($start_date) || empty($end_date) || empty($reason)) {
        $error = 'All fields are required.';
    } elseif (!in_array($leave_type, $leaveTypes)) {
        $error = 'Invalid leave type selected.';
    } elseif ($end_date < $start_date) {
        $error = 'End date cannot be before start date.';
    } elseif ($start_date < date('Y-m-d')) {
        $error = 'Start date cannot be in the past.';
    } else {
        $stmt = $db->prepare(
            "INSERT INTO leaves (user_id, leave_type, start_date, end_date, reason, status)
             VALUES (?, ?, ?, ?, ?, 'Pending')"
        );
        $stmt->execute([$uid, $leave_type, $start_date, $end_date, $reason]);

        setFlash('success', 'Your leave request has been submitted successfully!');
        header("Location: /leave_management/employee/history.php");
        exit;
    }
}

$pageTitle = 'Apply for Leave';
include dirname(__DIR__) . '/components/head.php';
?>
<?php include dirname(__DIR__) . '/components/navbar.php'; ?>
<?php include dirname(__DIR__) . '/components/flash.php'; ?>

<div class="page-wrapper">
  <div class="container-fluid px-4">

    <div class="page-header d-flex align-items-center gap-3">
      <a href="/leave_management/employee/dashboard.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
      </a>
      <div>
        <h1 class="page-title mb-0">Apply for Leave</h1>
        <p class="page-subtitle">Fill in the details below to submit a leave request</p>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card animate-in">
          <div class="card-body">

            <?php if ($error): ?>
              <div class="alert alert-danger d-flex align-items-center gap-2">
                <i class="bi bi-x-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="">

              <div class="mb-3">
                <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                <select name="leave_type" class="form-select" required>
                  <option value="">— Select Leave Type —</option>
                  <?php foreach ($leaveTypes as $type): ?>
                    <option value="<?= $type ?>"
                      <?= (($_POST['leave_type'] ?? '') === $type) ? 'selected' : '' ?>>
                      <?= $type ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Start Date <span class="text-danger">*</span></label>
                  <input type="date" id="start_date" name="start_date" class="form-control"
                         value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">End Date <span class="text-danger">*</span></label>
                  <input type="date" id="end_date" name="end_date" class="form-control"
                         value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>" required>
                </div>
              </div>

              <!-- Day count display -->
              <div class="mb-3">
                <small class="text-muted">
                  Duration: <strong id="day_count">—</strong>
                </small>
              </div>

              <div class="mb-4">
                <label class="form-label">Reason <span class="text-danger">*</span></label>
                <textarea name="reason" class="form-control" rows="4"
                          placeholder="Briefly explain the reason for your leave..."
                          required><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
              </div>

              <div class="d-flex gap-2 justify-content-end">
                <a href="/leave_management/employee/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-send-fill me-1"></i>Submit Request
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
