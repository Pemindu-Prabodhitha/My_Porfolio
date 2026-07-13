<?php
/**
 * _header.php
 * Shared top bar + <head> for every admin page. Include after calling
 * require_login(), and pass $pageTitle before including this file.
 */
$pageTitle = $pageTitle ?? 'Admin';
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e($pageTitle); ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../css/style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../../css/admin.css?v=<?php echo time(); ?>">
</head>
<body class="admin-body">

<div class="admin-bar">
  <div class="container">
    <span class="admin-logo">ADMIN<span>.</span>PANEL</span>
    <nav class="admin-nav">
      <a href="dashboard.php" class="<?php echo $activeNav === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
      <a href="content.php" class="<?php echo $activeNav === 'content' ? 'active' : ''; ?>">Site Content</a>
      <a href="timeline.php" class="<?php echo $activeNav === 'timeline' ? 'active' : ''; ?>">Timeline</a>
      <a href="skills.php" class="<?php echo $activeNav === 'skills' ? 'active' : ''; ?>">Skills</a>
      <a href="projects.php" class="<?php echo $activeNav === 'projects' ? 'active' : ''; ?>">Projects</a>
      <a href="messages.php" class="<?php echo $activeNav === 'messages' ? 'active' : ''; ?>">Messages</a>
      <a href="change-password.php" class="<?php echo $activeNav === 'password' ? 'active' : ''; ?>">Change Password</a>
      <a href="../../index.php" target="_blank">View Site ↗</a>
      <a href="logout.php">Log Out</a>
    </nav>
  </div>
</div>

<main class="admin-main">
  <div class="container">
