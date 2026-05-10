<?php
// ============================================================
// components/head.php — Shared HTML <head> section
// Usage: include this at the top of every page
// Pass $pageTitle before including.
// ============================================================
$pageTitle = $pageTitle ?? 'LeaveFlow';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?> — LeaveFlow</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Fraunces:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <!-- Custom Global CSS -->
  <link rel="stylesheet" href="/leave_management/assets/css/style.css">
</head>
<body>
