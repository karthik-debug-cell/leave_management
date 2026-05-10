<?php
// ============================================================
// employee/dashboard.php — Employee Dashboard
// Shows personal stats and recent leave requests.
// ============================================================
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/session.php';
requireEmployee();

$db   = getDB();
$user = currentUser();
$uid  = $user['id'];

// ---- Stats for this employee ----
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM leaves WHERE user_id = ?");
$stmt->execute([$uid]);
$total = $stmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM leaves WHERE user_id = ? AND status = 'Pending'");
$stmt->execute([$uid]);
$pending = $stmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM leaves WHERE user_id = ? AND status = 'Approved'");
$stmt->execute([$uid]);
$approved = $stmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM leaves WHERE user_id = ? AND status = 'Rejected'");
$stmt->execute([$uid]);
$rejected = $stmt->fetch()['cnt'];

// ---- Recent 5 leaves ----
$stmt = $db->prepare("
    SELECT * FROM leaves
    WHERE user_id = ?
    ORDER BY applied_at DESC
    LIMIT 5
");
$stmt->execute([$uid]);
$recent = $stmt->fetchAll();

$pageTitle = 'My Dashboard';
include dirname(__DIR__) . '/components/head.php';
?>
<?php include dirname(__DIR__) . '/components/navbar.php'; ?>
<?php include dirname(__DIR__) . '/components/flash.php'; ?>

<div class="page-wrapper">
  <div class="container-fluid px-4">

    <!-- Welcome Header -->
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <h1 class="page-title">Welcome, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋</h1>
        <p class="page-subtitle"><?= date('l, d F Y') ?></p>
      </div>
      <a href="/leave_management/employee/apply.php" class="btn btn-primary">
        <i class="bi bi-plus-circle-fill me-2"></i>Apply for Leave
      </a>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3 animate-in delay-1">
        <div class="stat-card teal">
          <i class="bi bi-calendar2-fill stat-icon"></i>
          <div class="stat-number"><?= $total ?></div>
          <div class="stat-label">Total Requests</div>
        </div>
      </div>
      <div class="col-6 col-md-3 animate-in delay-2">
        <div class="stat-card amber">
          <i class="bi bi-hourglass-split stat-icon"></i>
          <div class="stat-number"><?= $pending ?></div>
          <div class="stat-label">Pending</div>
        </div>
      </div>
      <div class="col-6 col-md-3 animate-in delay-3">
        <div class="stat-card green">
          <i class="bi bi-check-circle-fill stat-icon"></i>
          <div class="stat-number"><?= $approved ?></div>
          <div class="stat-label">Approved</div>
        </div>
      </div>
      <div class="col-6 col-md-3 animate-in delay-4">
        <div class="stat-card red">
          <i class="bi bi-x-circle-fill stat-icon"></i>
          <div class="stat-number"><?= $rejected ?></div>
          <div class="stat-label">Rejected</div>
        </div>
      </div>
    </div>

    <!-- Recent Requests -->
    <div class="card animate-in">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-primary"></i>Recent Leave Requests</span>
        <a href="/leave_management/employee/history.php" class="btn btn-outline-secondary btn-sm">
          View All <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="empty-state">
            <i class="bi bi-calendar-plus"></i>
            <h5>No Leave Requests</h5>
            <p>You haven't applied for any leave yet.</p>
            <a href="/leave_management/employee/apply.php" class="btn btn-primary mt-2">
              <i class="bi bi-plus-circle me-1"></i>Apply Now
            </a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-custom mb-0">
              <thead>
                <tr>
                  <th>Leave Type</th>
                  <th>Start Date</th>
                  <th>End Date</th>
                  <th>Days</th>
                  <th>Applied</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent as $l): ?>
                  <?php
                    $days  = (new DateTime($l['start_date']))->diff(new DateTime($l['end_date']))->days + 1;
                    $badge = match($l['status']) {
                        'Approved' => 'status-approved',
                        'Rejected' => 'status-rejected',
                        default    => 'status-pending',
                    };
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($l['leave_type']) ?></td>
                    <td><?= date('d M Y', strtotime($l['start_date'])) ?></td>
                    <td><?= date('d M Y', strtotime($l['end_date'])) ?></td>
                    <td><?= $days ?></td>
                    <td class="text-muted"><?= date('d M Y', strtotime($l['applied_at'])) ?></td>
                    <td><span class="badge-status <?= $badge ?>"><?= $l['status'] ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
