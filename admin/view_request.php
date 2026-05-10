<?php
// ============================================================
// admin/view_request.php — Review a Single Leave Request
// Admin can approve or reject and add a comment.
// ============================================================
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/session.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header("Location: /leave_management/admin/requests.php");
    exit;
}

// ---- Fetch leave with employee details ----
$stmt = $db->prepare("
    SELECT l.*, u.name AS employee_name, u.email AS employee_email, u.department
    FROM leaves l
    JOIN users u ON u.id = l.user_id
    WHERE l.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$leave = $stmt->fetch();

if (!$leave) {
    setFlash('danger', 'Leave request not found.');
    header("Location: /leave_management/admin/requests.php");
    exit;
}

// ---- Handle Approve / Reject POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $comment = trim($_POST['admin_comment'] ?? '');

    if (in_array($action, ['Approved', 'Rejected'])) {
        $update = $db->prepare(
            "UPDATE leaves SET status = ?, admin_comment = ?, updated_at = NOW() WHERE id = ?"
        );
        $update->execute([$action, $comment, $id]);

        setFlash('success', "Leave request has been <strong>$action</strong> successfully.");
        header("Location: /leave_management/admin/requests.php");
        exit;
    }
}

// ---- Calculate days ----
$start = new DateTime($leave['start_date']);
$end   = new DateTime($leave['end_date']);
$days  = $start->diff($end)->days + 1;

$badge = match($leave['status']) {
    'Approved' => 'status-approved',
    'Rejected' => 'status-rejected',
    default    => 'status-pending',
};

$pageTitle = 'Review Request';
include dirname(__DIR__) . '/components/head.php';
?>
<?php include dirname(__DIR__) . '/components/navbar.php'; ?>
<?php include dirname(__DIR__) . '/components/flash.php'; ?>

<div class="page-wrapper">
  <div class="container-fluid px-4">

    <div class="page-header d-flex align-items-center gap-3">
      <a href="/leave_management/admin/requests.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
      </a>
      <div>
        <h1 class="page-title mb-0">Review Leave Request #<?= $id ?></h1>
        <p class="page-subtitle">Submitted on <?= date('d F Y', strtotime($leave['applied_at'])) ?></p>
      </div>
    </div>

    <div class="row g-4">

      <!-- Left: Leave Details -->
      <div class="col-lg-7">
        <div class="card animate-in">
          <div class="card-header">
            <i class="bi bi-file-earmark-text me-2"></i>Leave Details
          </div>
          <div class="card-body">
            <table class="table table-custom mb-0">
              <tbody>
                <tr>
                  <td class="text-muted fw-600" style="width:40%">Employee</td>
                  <td>
                    <strong><?= htmlspecialchars($leave['employee_name']) ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($leave['employee_email']) ?></small>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted fw-600">Department</td>
                  <td><?= htmlspecialchars($leave['department'] ?? '—') ?></td>
                </tr>
                <tr>
                  <td class="text-muted fw-600">Leave Type</td>
                  <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                </tr>
                <tr>
                  <td class="text-muted fw-600">Start Date</td>
                  <td><?= date('D, d F Y', strtotime($leave['start_date'])) ?></td>
                </tr>
                <tr>
                  <td class="text-muted fw-600">End Date</td>
                  <td><?= date('D, d F Y', strtotime($leave['end_date'])) ?></td>
                </tr>
                <tr>
                  <td class="text-muted fw-600">Total Days</td>
                  <td><strong><?= $days ?> day<?= $days > 1 ? 's' : '' ?></strong></td>
                </tr>
                <tr>
                  <td class="text-muted fw-600">Status</td>
                  <td><span class="badge-status <?= $badge ?>"><?= $leave['status'] ?></span></td>
                </tr>
                <tr>
                  <td class="text-muted fw-600">Reason</td>
                  <td><?= nl2br(htmlspecialchars($leave['reason'])) ?></td>
                </tr>
                <?php if ($leave['admin_comment']): ?>
                  <tr>
                    <td class="text-muted fw-600">Admin Comment</td>
                    <td><?= nl2br(htmlspecialchars($leave['admin_comment'])) ?></td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right: Action Panel -->
      <div class="col-lg-5">
        <div class="card animate-in delay-1">
          <div class="card-header">
            <i class="bi bi-check2-square me-2"></i>Take Action
          </div>
          <div class="card-body">

            <?php if ($leave['status'] !== 'Pending'): ?>
              <!-- Already actioned -->
              <div class="alert alert-info d-flex align-items-center gap-2">
                <i class="bi bi-info-circle-fill fs-5"></i>
                <div>
                  This request has already been <strong><?= $leave['status'] ?></strong>.
                  You can change the status below.
                </div>
              </div>
            <?php endif; ?>

            <form method="POST" action="">
              <div class="mb-4">
                <label class="form-label">Admin Comment (Optional)</label>
                <textarea name="admin_comment" class="form-control" rows="4"
                          placeholder="Add a note for the employee..."><?= htmlspecialchars($leave['admin_comment'] ?? '') ?></textarea>
              </div>

              <div class="d-grid gap-2">
                <button type="submit" name="action" value="Approved"
                        class="btn btn-success"
                        onclick="return confirm('Approve this leave request?')">
                  <i class="bi bi-check-circle-fill me-2"></i>Approve Request
                </button>
                <button type="submit" name="action" value="Rejected"
                        class="btn btn-danger"
                        onclick="return confirm('Reject this leave request?')">
                  <i class="bi bi-x-circle-fill me-2"></i>Reject Request
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
