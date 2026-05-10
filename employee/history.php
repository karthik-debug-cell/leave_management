<?php
// ============================================================
// employee/history.php — My Leave History
// ============================================================
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/session.php';
requireEmployee();

$db   = getDB();
$user = currentUser();
$uid  = $user['id'];

// Optional status filter
$filterStatus = $_GET['status'] ?? '';
$where  = ['user_id = ?'];
$params = [$uid];

if ($filterStatus && in_array($filterStatus, ['Pending','Approved','Rejected'])) {
    $where[]  = 'status = ?';
    $params[] = $filterStatus;
}

$stmt = $db->prepare("
    SELECT * FROM leaves
    WHERE " . implode(' AND ', $where) . "
    ORDER BY applied_at DESC
");
$stmt->execute($params);
$leaves = $stmt->fetchAll();

$pageTitle = 'My Leave History';
include dirname(__DIR__) . '/components/head.php';
?>
<?php include dirname(__DIR__) . '/components/navbar.php'; ?>
<?php include dirname(__DIR__) . '/components/flash.php'; ?>

<div class="page-wrapper">
  <div class="container-fluid px-4">

    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <h1 class="page-title">My Leave History</h1>
        <p class="page-subtitle">All your leave requests and their current status</p>
      </div>
      <a href="/leave_management/employee/apply.php" class="btn btn-primary">
        <i class="bi bi-plus-circle-fill me-2"></i>Apply for Leave
      </a>
    </div>

    <!-- Filter -->
    <div class="filter-bar animate-in">
      <form method="GET" action="" class="d-flex align-items-end gap-3 flex-wrap">
        <div>
          <label class="form-label">Filter by Status</label>
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Status</option>
            <?php foreach (['Pending','Approved','Rejected'] as $s): ?>
              <option value="<?= $s ?>" <?= ($filterStatus === $s) ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if ($filterStatus): ?>
          <a href="/leave_management/employee/history.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-lg me-1"></i>Clear
          </a>
        <?php endif; ?>
      </form>
    </div>

    <div class="card animate-in delay-1">
      <div class="card-body p-0">
        <?php if (empty($leaves)): ?>
          <div class="empty-state">
            <i class="bi bi-calendar-x"></i>
            <h5>No Leave Requests Found</h5>
            <p>You haven't submitted any leave requests yet.</p>
            <a href="/leave_management/employee/apply.php" class="btn btn-primary mt-2">
              <i class="bi bi-plus-circle me-1"></i>Apply Now
            </a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-custom mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Leave Type</th>
                  <th>Start Date</th>
                  <th>End Date</th>
                  <th>Days</th>
                  <th>Reason</th>
                  <th>Applied On</th>
                  <th>Status</th>
                  <th>Admin Note</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($leaves as $i => $l): ?>
                  <?php
                    $days  = (new DateTime($l['start_date']))->diff(new DateTime($l['end_date']))->days + 1;
                    $badge = match($l['status']) {
                        'Approved' => 'status-approved',
                        'Rejected' => 'status-rejected',
                        default    => 'status-pending',
                    };
                  ?>
                  <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($l['leave_type']) ?></td>
                    <td><?= date('d M Y', strtotime($l['start_date'])) ?></td>
                    <td><?= date('d M Y', strtotime($l['end_date'])) ?></td>
                    <td><?= $days ?></td>
                    <td style="max-width:180px">
                      <span title="<?= htmlspecialchars($l['reason']) ?>">
                        <?= htmlspecialchars(mb_strimwidth($l['reason'], 0, 40, '...')) ?>
                      </span>
                    </td>
                    <td class="text-muted"><?= date('d M Y', strtotime($l['applied_at'])) ?></td>
                    <td><span class="badge-status <?= $badge ?>"><?= $l['status'] ?></span></td>
                    <td class="text-muted" style="font-size:.82rem">
                      <?= $l['admin_comment'] ? htmlspecialchars($l['admin_comment']) : '—' ?>
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
