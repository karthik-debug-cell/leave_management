<?php
// ============================================================
// components/navbar.php — Shared Navigation Bar
// Include this in every page after starting the session.
// ============================================================
require_once dirname(__DIR__) . '/config/session.php';
$user = currentUser();
$isAdmin = ($user['role'] === 'admin');
$dashboardLink = $isAdmin
    ? '/leave_management/admin/dashboard.php'
    : '/leave_management/employee/dashboard.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="mainNav">
  <div class="container-fluid px-4">

    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $dashboardLink ?>">
      <div class="brand-icon">
        <i class="bi bi-calendar2-check-fill"></i>
      </div>
      <span class="brand-text">LeaveFlow</span>
    </a>

    <!-- Mobile toggle -->
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <?php if ($isAdmin): ?>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>"
               href="/leave_management/admin/dashboard.php">
              <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'requests.php' ? 'active' : '' ?>"
               href="/leave_management/admin/requests.php">
              <i class="bi bi-inbox-fill me-1"></i>Leave Requests
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'employees.php' ? 'active' : '' ?>"
               href="/leave_management/admin/employees.php">
              <i class="bi bi-people-fill me-1"></i>Employees
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>"
               href="/leave_management/admin/reports.php">
              <i class="bi bi-bar-chart-fill me-1"></i>Reports
            </a>
          </li>

        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>"
               href="/leave_management/employee/dashboard.php">
              <i class="bi bi-house-fill me-1"></i>Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'apply.php' ? 'active' : '' ?>"
               href="/leave_management/employee/apply.php">
              <i class="bi bi-plus-circle-fill me-1"></i>Apply Leave
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'history.php' ? 'active' : '' ?>"
               href="/leave_management/employee/history.php">
              <i class="bi bi-clock-history me-1"></i>My History
            </a>
          </li>
        <?php endif; ?>

      </ul>

      <!-- User dropdown -->
      <div class="dropdown">
        <button class="btn user-pill dropdown-toggle d-flex align-items-center gap-2"
                type="button" data-bs-toggle="dropdown">
          <div class="user-avatar">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
          </div>
          <span class="user-name d-none d-md-inline"><?= htmlspecialchars($user['name']) ?></span>
          <?php if ($isAdmin): ?>
            <span class="badge badge-admin">Admin</span>
          <?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li><h6 class="dropdown-header"><?= htmlspecialchars($user['email']) ?></h6></li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item text-danger" href="/leave_management/auth/logout.php">
              <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
