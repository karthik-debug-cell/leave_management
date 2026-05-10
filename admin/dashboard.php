<?php
// ============================================================
// admin/dashboard.php — Admin Dashboard
// Shows summary stats and recent leave requests
// ============================================================
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/session.php';
requireAdmin();

$db   = getDB();
$user = currentUser();

// ---- Summary Statistics ----
$stats = [];

// Total employees
$res = $db->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'employee'");
$stats['employees'] = $res->fetch()['cnt'];

// Total leave requests
$res = $db->query("SELECT COUNT(*) as cnt FROM leaves");
$stats['total'] = $res->fetch()['cnt'];

// Pending
$res = $db->query("SELECT COUNT(*) as cnt FROM leaves WHERE status = 'Pending'");
$stats['pending'] = $res->fetch()['cnt'];

// Approved
$res = $db->query("SELECT COUNT(*) as cnt FROM leaves WHERE status = 'Approved'");
$stats['approved'] = $res->fetch()['cnt'];

// Rejected
$res = $db->query("SELECT COUNT(*) as cnt FROM leaves WHERE status = 'Rejected'");
$stats['rejected'] = $res->fetch()['cnt'];

// ---- Recent 10 Leave Requests ----
$recent = $db->query("
    SELECT l.*, u.name AS employee_name, u.department
    FROM leaves l
    JOIN users u ON u.id = l.user_id
    ORDER BY l.applied_at DESC
    LIMIT 10
")->fetchAll();

$pageTitle = 'Admin Dashboard';
include dirname(__DIR__) . '/components/head.php';
?>
<?php include dirname(__DIR__) . '/components/navbar.php'; ?>
<?php include dirname(__DIR__) . '/components/flash.php'; ?>

<div class="page-wrapper">
  <div class="container-fluid px-4">

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">Admin Dashboard</h1>
      <p class="page-subtitle">
        Welcome back, <?= htmlspecialchars($user['name']) ?> &mdash;
        <?= date('l, d F Y') ?>
      </p>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-4 col-xl-2 animate-in delay-1">
        <div class="stat-card teal">
          <i class="bi bi-people-fill stat-icon"></i>
          <div class="stat-number"><?= $stats['employees'] ?></div>
          <div class="stat-label">Total Employees</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2 animate-in delay-2">
        <div class="stat-card slate">
          <i class="bi bi-calendar2-fill stat-icon"></i>
          <div class="stat-number"><?= $stats['total'] ?></div>
          <div class="stat-label">Total Requests</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2 animate-in delay-3">
        <div class="stat-card amber">
          <i class="bi bi-hourglass-split stat-icon"></i>
          <div class="stat-number"><?= $stats['pending'] ?></div>
          <div class="stat-label">Pending</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2 animate-in delay-4">
        <div class="stat-card green">
          <i class="bi bi-check-circle-fill stat-icon"></i>
          <div class="stat-number"><?= $stats['approved'] ?></div>
          <div class="stat-label">Approved</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2 animate-in">
        <div class="stat-card red">
          <i class="bi bi-x-circle-fill stat-icon"></i>
          <div class="stat-number"><?= $stats['rejected'] ?></div>
          <div class="stat-label">Rejected</div>
        </div>
      </div>
    </div>

    <!-- Recent Leave Requests Table -->
    <div class="card animate-in">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-primary"></i>Recent Leave Requests</span>
        <a href="/leave_management/admin/requests.php" class="btn btn-outline-secondary btn-sm">
          View All <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recent)): ?>
          <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No Leave Requests Yet</h5>
            <p>Leave requests from employees will appear here.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-custom mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Employee</th>
                  <th>Leave Type</th>
                  <th>Duration</th>
                  <th>Applied On</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent as $leave): ?>
                  <?php
                    $start   = new DateTime($leave['start_date']);
                    $end     = new DateTime($leave['end_date']);
                    $days    = $start->diff($end)->days + 1;
                    $status  = $leave['status'];
                    $badge   = match($status) {
                        'Approved' => 'status-approved',
                        'Rejected' => 'status-rejected',
                        default    => 'status-pending',
                    };
                  ?>
                  <tr>
                    <td class="text-muted"><?= $leave['id'] ?></td>
                    <td>
                      <div class="fw-600"><?= htmlspecialchars($leave['employee_name']) ?></div>
                      <div class="text-muted" style="font-size:.78rem"><?= htmlspecialchars($leave['department'] ?? '') ?></div>
                    </td>
                    <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                    <td>
                      <?= date('d M', strtotime($leave['start_date'])) ?>
                      <?php if ($leave['start_date'] !== $leave['end_date']): ?>
                        → <?= date('d M', strtotime($leave['end_date'])) ?>
                      <?php endif; ?>
                      <span class="text-muted">(<?= $days ?>d)</span>
                    </td>
                    <td class="text-muted"><?= date('d M Y', strtotime($leave['applied_at'])) ?></td>
                    <td><span class="badge-status <?= $badge ?>"><?= $status ?></span></td>
                    <td>
                      <a href="/leave_management/admin/view_request.php?id=<?= $leave['id'] ?>"
                         class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-eye me-1"></i>Review
                      </a>
                    </td>
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
