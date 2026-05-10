<?php
// ============================================================
// admin/reports.php — Leave Reports with Filters
// Admin can filter leave summaries by user and date range.
// ============================================================
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/session.php';
requireAdmin();

$db = getDB();

// Filter inputs
$filterUser = $_GET['user_id'] ?? '';
$filterFrom = $_GET['from']    ?? date('Y-m-01'); // Default: first of current month
$filterTo   = $_GET['to']      ?? date('Y-m-d');

// Build WHERE
$where  = ["l.status = 'Approved'"];
$params = [];

if ($filterUser && is_numeric($filterUser)) {
    $where[]  = "l.user_id = ?";
    $params[] = (int)$filterUser;
}

if ($filterFrom) {
    $where[]  = "l.start_date >= ?";
    $params[] = $filterFrom;
}

if ($filterTo) {
    $where[]  = "l.end_date <= ?";
    $params[] = $filterTo;
}

// Summary by leave type
$summarySQL = "
    SELECT l.leave_type,
           COUNT(*) AS count,
           SUM(DATEDIFF(l.end_date, l.start_date) + 1) AS total_days
    FROM leaves l
    WHERE " . implode(' AND ', $where) . "
    GROUP BY l.leave_type
";
$stmt = $db->prepare($summarySQL);
$stmt->execute($params);
$summary = $stmt->fetchAll();

// Detailed records
$detailSQL = "
    SELECT l.*, u.name AS employee_name, u.department,
           DATEDIFF(l.end_date, l.start_date) + 1 AS days
    FROM leaves l
    JOIN users u ON u.id = l.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY l.start_date DESC
";
$stmt = $db->prepare($detailSQL);
$stmt->execute($params);
$details = $stmt->fetchAll();

// Total days from summary
$totalDays = array_sum(array_column($summary, 'total_days'));

// Employees for dropdown
$employees = $db->query("SELECT id, name FROM users WHERE role = 'employee' ORDER BY name")->fetchAll();

$pageTitle = 'Leave Reports';
include dirname(__DIR__) . '/components/head.php';
?>
<?php include dirname(__DIR__) . '/components/navbar.php'; ?>
<?php include dirname(__DIR__) . '/components/flash.php'; ?>

<div class="page-wrapper">
  <div class="container-fluid px-4">

    <div class="page-header">
      <h1 class="page-title">Leave Reports</h1>
      <p class="page-subtitle">Approved leave analysis by employee and date range</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar animate-in">
      <form method="GET" action="" class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-3">
          <label class="form-label">Employee</label>
          <select name="user_id" class="form-select form-select-sm">
            <option value="">All Employees</option>
            <?php foreach ($employees as $emp): ?>
              <option value="<?= $emp['id'] ?>" <?= ($filterUser == $emp['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($emp['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-6 col-md-2">
          <label class="form-label">From Date</label>
          <input type="date" name="from" class="form-control form-control-sm"
                 value="<?= htmlspecialchars($filterFrom) ?>">
        </div>
        <div class="col-sm-6 col-md-2">
          <label class="form-label">To Date</label>
          <input type="date" name="to" class="form-control form-control-sm"
                 value="<?= htmlspecialchars($filterTo) ?>">
        </div>
        <div class="col-auto d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-bar-chart-fill me-1"></i>Generate
          </button>
          <a href="/leave_management/admin/reports.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
      </form>
    </div>

    <!-- Summary Cards -->
    <?php if (!empty($summary)): ?>
    <div class="row g-3 mb-4 animate-in">
      <div class="col-6 col-md-3">
        <div class="stat-card teal">
          <i class="bi bi-calendar-check stat-icon"></i>
          <div class="stat-number"><?= $totalDays ?></div>
          <div class="stat-label">Total Days Taken</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card green">
          <i class="bi bi-check-circle stat-icon"></i>
          <div class="stat-number"><?= count($details) ?></div>
          <div class="stat-label">Approved Leaves</div>
        </div>
      </div>
      <?php foreach (array_slice($summary, 0, 2) as $s): ?>
      <div class="col-6 col-md-3">
        <div class="stat-card amber">
          <i class="bi bi-tag stat-icon"></i>
          <div class="stat-number"><?= $s['total_days'] ?></div>
          <div class="stat-label"><?= $s['leave_type'] ?> Days</div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Summary by Type -->
    <div class="row g-4 mb-4">
      <div class="col-lg-4">
        <div class="card animate-in delay-1 h-100">
          <div class="card-header"><i class="bi bi-pie-chart-fill me-2"></i>By Leave Type</div>
          <div class="card-body p-0">
            <table class="table table-custom mb-0">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Count</th>
                  <th>Total Days</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($summary as $s): ?>
                  <tr>
                    <td><?= htmlspecialchars($s['leave_type']) ?></td>
                    <td><?= $s['count'] ?></td>
                    <td><strong><?= $s['total_days'] ?></strong></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Detailed Records -->
      <div class="col-lg-8">
        <div class="card animate-in delay-2 h-100">
          <div class="card-header"><i class="bi bi-list-ul me-2"></i>Approved Leave Records</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-custom mb-0">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Days</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($details as $d): ?>
                    <tr>
                      <td>
                        <div class="fw-600"><?= htmlspecialchars($d['employee_name']) ?></div>
                        <div class="text-muted" style="font-size:.78rem"><?= htmlspecialchars($d['department'] ?? '') ?></div>
                      </td>
                      <td><?= htmlspecialchars($d['leave_type']) ?></td>
                      <td><?= date('d M Y', strtotime($d['start_date'])) ?></td>
                      <td><?= date('d M Y', strtotime($d['end_date'])) ?></td>
                      <td><strong><?= $d['days'] ?></strong></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php else: ?>
      <div class="card animate-in">
        <div class="empty-state">
          <i class="bi bi-bar-chart"></i>
          <h5>No approved leave records found</h5>
          <p>Try adjusting your filters or date range.</p>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
