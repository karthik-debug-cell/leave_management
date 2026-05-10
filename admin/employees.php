<?php
// ============================================================
// admin/employees.php — Employee Management
// Admin can view all employees and add new ones.
// ============================================================
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/session.php';
requireAdmin();

$db = getDB();

// Fetch all employees with their leave counts
$employees = $db->query("
    SELECT u.*,
           COUNT(l.id)                                           AS total_leaves,
           SUM(l.status = 'Pending')                            AS pending,
           SUM(l.status = 'Approved')                           AS approved
    FROM users u
    LEFT JOIN leaves l ON l.user_id = u.id
    WHERE u.role = 'employee'
    GROUP BY u.id
    ORDER BY u.name
")->fetchAll();

$pageTitle = 'Employees';
include dirname(__DIR__) . '/components/head.php';
?>
<?php include dirname(__DIR__) . '/components/navbar.php'; ?>
<?php include dirname(__DIR__) . '/components/flash.php'; ?>

<div class="page-wrapper">
  <div class="container-fluid px-4">

    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <h1 class="page-title">Employees</h1>
        <p class="page-subtitle">Manage employee accounts and view their leave summary</p>
      </div>
      <a href="/leave_management/auth/register.php" class="btn btn-primary">
        <i class="bi bi-person-plus-fill me-2"></i>Add Employee
      </a>
    </div>

    <div class="card animate-in">
      <div class="card-body p-0">
        <?php if (empty($employees)): ?>
          <div class="empty-state">
            <i class="bi bi-people"></i>
            <h5>No Employees Yet</h5>
            <p>Add your first employee to get started.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-custom mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Department</th>
                  <th>Total Leaves</th>
                  <th>Pending</th>
                  <th>Approved</th>
                  <th>Joined</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($employees as $i => $emp): ?>
                  <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="user-avatar" style="width:32px;height:32px;font-size:.8rem">
                          <?= strtoupper(substr($emp['name'], 0, 1)) ?>
                        </div>
                        <strong><?= htmlspecialchars($emp['name']) ?></strong>
                      </div>
                    </td>
                    <td class="text-muted"><?= htmlspecialchars($emp['email']) ?></td>
                    <td><?= htmlspecialchars($emp['department'] ?? '—') ?></td>
                    <td><?= $emp['total_leaves'] ?></td>
                    <td>
                      <?php if ($emp['pending'] > 0): ?>
                        <span class="badge-status status-pending"><?= $emp['pending'] ?></span>
                      <?php else: ?>
                        <span class="text-muted">0</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($emp['approved'] > 0): ?>
                        <span class="badge-status status-approved"><?= $emp['approved'] ?></span>
                      <?php else: ?>
                        <span class="text-muted">0</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted"><?= date('d M Y', strtotime($emp['created_at'])) ?></td>
                    <td>
                      <a href="/leave_management/admin/requests.php?user_id=<?= $emp['id'] ?>"
                         class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-eye me-1"></i>Leaves
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
