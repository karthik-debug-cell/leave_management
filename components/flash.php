<?php
// ============================================================
// components/flash.php — Flash Message Display
// Shows success/error/warning messages stored in session.
// Include this right after the navbar on protected pages.
// ============================================================
require_once dirname(__DIR__) . '/config/session.php';
$flash = getFlash();

if ($flash):
    $icons = [
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-x-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'info'    => 'bi-info-circle-fill',
    ];
    $icon = $icons[$flash['type']] ?? 'bi-info-circle-fill';
?>
<div class="container-fluid px-4 mt-3">
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible d-flex align-items-center gap-2 fade show shadow-sm" role="alert">
    <i class="bi <?= $icon ?> fs-5 flex-shrink-0"></i>
    <div><?= htmlspecialchars($flash['message']) ?></div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>
