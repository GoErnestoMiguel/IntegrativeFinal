<?php
require 'config.php';
require_once 'admin-auth.php';
require_admin();
$page_title = 'Admin panel';
require_once 'workflow.php';
$error=''; $queue=[]; $upcoming=[];
try {
 if($_SERVER['REQUEST_METHOD']==='POST') {
  if(!valid_form()) throw new RuntimeException('Refresh and try again.');
  throw new RuntimeException('Ask the server administrator to run tools/migrate.php from the private project folder.');
  header('Location: admin-panel.php?upgraded=1'); exit;
 }
 if(workflow_ready()) {
  $queries=[
   'Applications awaiting review'=>"SELECT COUNT(*) FROM applicants WHERE status IN ('Received','Under review')",
   'Unreviewed documents'=>"SELECT COUNT(*) FROM applicant_documents d LEFT JOIN document_reviews r ON r.document_id=d.id WHERE r.decision IS NULL OR r.decision='Under review'",
   'Residency checks to resolve'=>"SELECT COUNT(*) FROM applicants a LEFT JOIN residency_reviews r ON r.applicant_id=a.id WHERE r.decision IS NULL OR r.decision='Needs clarification'",
   'Email updates needing attention'=>"SELECT COUNT(*) FROM notification_log WHERE delivery IN ('pending','failed','not_configured','sending')"
  ];
  foreach($queries as $label=>$sql) $queue[$label]=(int)database()->query($sql)->fetchColumn();
  $upcoming=database()->query("SELECT work_date,COUNT(*) AS workers FROM work_roster WHERE work_date>=CURDATE() GROUP BY work_date ORDER BY work_date LIMIT 7")->fetchAll();
 }
} catch(Throwable $e) { $error=$e instanceof PDOException?database_error():$e->getMessage(); }
require 'header.php';
?>
<section class="container section"><p class="eyebrow">STAFF WORKSPACE</p><h1>Admin panel</h1>

<?php require 'admin-nav.php'; ?>
<?php if($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<?php if(!workflow_ready()): ?><form method="post" class="card"><h2>Install workflow upgrade</h2><p>Back up your database first. This adds review, notification and work-activity tables without deleting existing records.</p><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><button class="primary">Install workflow tables</button></form><?php endif; ?>
<?php if(isset($_GET['upgraded'])): ?><p class="notice">Workflow tables are ready. Existing names were preserved; new registrations store separate name fields.</p><?php endif; ?>
<div class="dashboard-queues"><?php foreach($queue as $label=>$count): ?><a class="card" href="<?= $label==='Email updates needing attention'?'admin-notifications.php':'admin-queue.php?queue='.urlencode($label) ?>"><strong><?= $count ?></strong><?= e($label) ?></a><?php endforeach; ?></div>
<?php if($upcoming): ?><section class="card" aria-labelledby="upcoming-workers-title"><h2 id="upcoming-workers-title">Upcoming worker lists</h2><div class="table-scroll"><table class="applicant-table"><thead><tr><th scope="col">Work date</th><th scope="col">Scheduled workers</th><th scope="col">Action</th></tr></thead><tbody><?php foreach($upcoming as $day): ?><tr><td><?= e($day['work_date']) ?></td><td><?= (int)$day['workers'] ?></td><td><a href="admin-schedule.php?date=<?= e($day['work_date']) ?>">View worker list</a></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
<div class="two-column section"><article class="card"><h2>Work schedule</h2><p>Choose a date and add workers by TUPAD ID.</p><a class="button primary" href="admin-schedule.php">Manage worker lists</a></article><article class="card"><h2>Calendar and announcements</h2><p>Create, edit, publish or remove public events and announcements.</p><a class="button primary" href="admin-content.php">Manage public updates</a></article>
<article class="card"><h2>Applicant management</h2><p>Find applicant IDs, review applications, and update screening or community-work status.</p><a class="button primary" href="admin.php">Manage applicants</a></article>
<article class="card"><h2>TUPAD attendance</h2><p>Record a worker’s presence by ID, view attendance by date, and print the daily sheet.</p><a class="button primary" href="attendance.php">Open attendance</a></article>
</div></section>
<?php require 'footer.php'; ?>
