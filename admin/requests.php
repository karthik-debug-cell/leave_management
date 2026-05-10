<?php
// ============================================================
// admin/requests.php — All Leave Requests with Filters
// Admin can filter by employee, status, leave type, and date.
// ============================================================
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/session.php';
requireAdmin();

$db = getDB();

// ---- Build Filter Query ----
$where  = ['1=1'];
$params = [];

$filterStatus = $_GET['status']     ?? '';
$filterUser   = $_GET['user_id']    ?? '';
$filterType   = $_GET['leave_type'] ?? '';
$filterFrom   = $_GET['from']       ?? '';
$filterTo     = $_GET['to']         ?? '';

if ($filterStatus && in_array($filterStatus, ['Pending','Approved','Rejected'])) {
    $where[]  = "l.status = ?";
    $params[] = $filterStatus;
}

if ($filterUser && is_numeric($filterUser)) {
    $where[]  = "l.user_id = ?";
    $params[] = (int)$filterUser;
}

if ($filterType) {
    $where[]  = "l.leave_type = ?";
    $params[] = $filterType;
}

if ($filterFrom) {
    $where[]  = "l.start_date >= ?";
    $params[] = $filterFrom;
}

if ($filterTo) {
    $where[]  = "l.end_date <= ?";
    $params[] = $filterTo;
}

$sql = "
    SELECT l.*, u.name AS employee_name, u.department
    FROM leaves l
    JOIN users u ON u.id = l.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY l.applied_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$leaves = $stmt->fetchAll();

// ---- Employee list for filter dropdown ----
$employees = $db->query("SELECT id, name FROM users WHERE role = 'employee' ORDER BY name")->fetchAll();

$leaveTypes = ['Annual','Sick','Casual','Maternity','Paternity','Unpaid'];

$pageTitle = 'Leave Requests';
include dirname(__DIR__) . '/components/head.php';
?>
<?php include dirname(__DIR__) . '/components/navbar.php'; ?>
<?php include dirname(__DIR__) . '/components/flash.php'; ?>

<div class="page-wrapper">
  <div class="container-fluid px-4">

    <div class="page-header">
      <h1 class="page-title">Leave Requests</h1>
      <p class="page-subtitle">Review and manage all employee leave requests</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar animate-in">
      <form method="GET" action="" class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-3 col-lg-2">
          <label class="form-label">Employee</label>
          <select name="user_id" class="form-select form-select-sm">
            <option value="">All Employees</option>
            <?php foreach ($employees as $emp): ?>
              <option value="<?= $emp['id'] ?>"
                <?= ($filterUser == $emp['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($emp['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">All Status</option>
            <?php foreach (['Pending','Approved','Rejected'] as $s): ?>
              <option value="<?= $s ?>" <?= ($filterStatus === $s) ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label">Leave Type</label>
          <select name="leave_type" class="form-select form-select-sm">
            <option value="">All Types</option>
            <?php foreach ($leaveTypes as $t): ?>
              <option value="<?= $t ?>" <?= ($filterType === $t) ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label">From</label>
          <input type="date" name="from" class="form-control form-control-sm"
                 value="<?= htmlspecialchars($filterFrom) ?>">
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label">To</label>
          <input type="date" name="to" class="form-control form-control-sm"
                 value="<?= htmlspecialchars($filterTo) ?>">
        </div>

        <div class="col-sm-6 col-md-1 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-funnel-fill"></i>
          </button>
          <a href="/leave_management/admin/requests.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-lg"></i>
          </a>
        </div>
      </form>
    </div>

    <!-- Results Count -->
    <p class="text-muted mb-2" style="font-size:.85rem">
      Showing <strong><?= count($leaves) ?></strong> request<?= count($leaves) !== 1 ? 's' : '' ?>
    </p>

    <!-- Table -->
    <div class="card animate-in delay-1">
      <div class="card-body p-0">
        <?php if (empty($leaves)): ?>
          <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No Requests Found</h5>
            <p>Try adjusting the filters above.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-custom mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Employee</th>
                  <th>Leave Type</th>
                  <th>Start</th>
                  <th>End</th>
                  <th>Days</th>
                  <th>Applied</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($leaves as $l): ?>
                  <?php
                    $days   = (new DateTime($l['start_date']))->diff(new DateTime($l['end_date']))->days + 1;
                    $badge  = match($l['status']) {
                        'Approved' => 'status-approved',
                        'Rejected' => 'status-rejected',
                        default    => 'status-pending',
                    };
                  ?>
                  <tr>
                    <td class="text-muted"><?= $l['id'] ?></td>
                    <td>
                      <div class="fw-600"><?= htmlspecialchars($l['employee_name']) ?></div>
                      <div class="text-muted" style="font-size:.78rem"><?= htmlspecialchars($l['department'] ?? '') ?></div>
                    </td>
                    <td><?= htmlspecialchars($l['leave_type']) ?></td>
                    <td><?= date('d M Y', strtotime($l['start_date'])) ?></td>
                    <td><?= date('d M Y', strtotime($l['end_date'])) ?></td>
                    <td><?= $days ?></td>
                    <td class="text-muted"><?= date('d M Y', strtotime($l['applied_at'])) ?></td>
                    <td><span class="badge-status <?= $badge ?>"><?= $l['status'] ?></span></td>
                    <td>
                      <a href="/leave_management/admin/view_request.php?id=<?= $l['id'] ?>"
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
