<?php
$admin_links = [
 'admin-panel.php' => 'Dashboard',
 'admin.php' => 'Applicants',
 'admin-residents.php' => 'Residents',
 'admin-accounts.php' => 'Accounts',
 'admin-members.php' => 'Members',
 'admin-schedule.php' => 'Work schedule',
 'attendance.php' => 'Attendance',
 'admin-content.php' => 'Calendar & announcements',
 'admin-notifications.php' => 'Notifications',
];
$admin_current = basename($_SERVER['SCRIPT_NAME']);
?>
<nav class="admin-navigation" aria-label="Admin navigation">
 <div class="admin-navigation-links">
 <?php foreach ($admin_links as $file => $label): ?>
  <a href="<?= e($file) ?>" <?= $admin_current === $file ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
 <?php endforeach; ?>
 </div>
 <form method="post" action="profile.php" class="admin-navigation-logout"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><button name="admin_action" value="logout">Log out <span aria-hidden="true">↗</span></button></form>
</nav>

