<?php
require 'config.php';
require 'admin-auth.php'; require_admin();
require 'profile-functions.php'; require_once 'applicant-picker.php';
require_once 'workflow.php';
$page_title='Work schedule'; $error=''; $rows=[]; $activity=[];
$date=(string)($_POST['date'] ?? $_GET['date'] ?? date('Y-m-d'));
if (!profile_date_valid($date)) { $date=date('Y-m-d'); $error='Choose a valid date.'; }
$message=$_SESSION['roster_message'] ?? ''; unset($_SESSION['roster_message']);
try {
 workflow_require();
 if ($_SERVER['REQUEST_METHOD']==='POST' && !$error) {
  if (!valid_form()) throw new RuntimeException('Refresh and try again.');
  $db=database(); $notifications=[]; $db->beginTransaction();
  try {
   $action=(string)($_POST['action'] ?? 'add');
   if($action==='activity') {
    $time=trim((string)($_POST['work_time'] ?? '')); $venue=trim((string)($_POST['venue'] ?? '')); $notes=trim((string)($_POST['instructions'] ?? ''));
    if(!$time || strlen($time)>100 || !$venue || strlen($venue)>250 || strlen($notes)>1000) throw new RuntimeException('Enter time (up to 100 characters), venue (up to 250) and instructions (up to 1,000).');
    $q=$db->prepare('INSERT INTO work_activities (work_date,work_time,venue,instructions,updated_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE work_time=VALUES(work_time),venue=VALUES(venue),instructions=VALUES(instructions),updated_by=VALUES(updated_by)');
    $q->execute([$date,$time,$venue,$notes,$_SESSION['admin_user']]);
    workflow_audit($db,null,'Work activity updated',"$date; $time; $venue; $notes");
    $q=$db->prepare('SELECT applicant_id FROM work_roster WHERE work_date=?'); $q->execute([$date]);
    foreach($q->fetchAll(PDO::FETCH_COLUMN) as $workerId) $notifications[]=queue_notification($db,$workerId,'Work schedule updated',"Work date: $date. Time: $time. Venue: $venue. $notes");
    $_SESSION['roster_message']='Activity details saved. Updates are queued in Notifications for the assigned workers.';
   } elseif($action==='remove') {
    $id=(string)($_POST['applicant_id'] ?? '');
    $q=$db->prepare('DELETE FROM work_roster WHERE work_date=? AND applicant_id=?');$q->execute([$date,$id]);
    if($q->rowCount()) { workflow_audit($db,$id,'Removed from roster',$date); $notifications[]=queue_notification($db,$id,'Work schedule changed',"You have been removed from the work list for $date. Contact the help desk if you need clarification."); }
    $_SESSION['roster_message']='Worker removed from this date. Attendance history is preserved.';
   } elseif($action==='add') {
    $id=strtoupper(trim((string)($_POST['worker_id'] ?? '')));
    $worker=preg_match('/^[A-Z0-9-]{1,64}$/',$id)?profile_record($id):null;
    if(!$worker || $worker['program']!=='TUPAD') throw new RuntimeException('Choose a TUPAD worker.');
    if(!in_array($worker['status'],['Orientation scheduled','Community work scheduled','Confirmed member'],true)) throw new RuntimeException('This worker is not at a scheduling stage. Update the application status first.');
    $q=$db->prepare('SELECT * FROM work_activities WHERE work_date=?');$q->execute([$date]);$a=$q->fetch();
    if(!$a) throw new RuntimeException('Save the activity time and venue before adding workers.');
    $q=$db->prepare('INSERT IGNORE INTO work_roster (work_date,applicant_id) VALUES (?,?)'); $q->execute([$date,$worker['id']]);
    if($q->rowCount()) {
     workflow_audit($db,$worker['id'],'Added to roster',$date);
     $notifications[]=queue_notification($db,$worker['id'],'Work assignment',"Work date: $date. Time: ".$a['work_time'].". Venue: ".$a['venue'].". ".$a['instructions']);
     $_SESSION['roster_message']=$worker['name'].' added to '.$date.'.';
    } else $_SESSION['roster_message']='This worker is already on this date’s list.';
   } else throw new RuntimeException('Unknown schedule action.');
   $db->commit();
  } catch(Throwable $ex) { if($db->inTransaction()) $db->rollBack(); throw $ex; }
  // Bulk activity updates are queued for staff delivery; individual assignments send immediately.
  if(count($notifications)===1) try { deliver_notification($notifications[0]); } catch(Throwable $ex) { error_log('Schedule notification pending.'); }
  header('Location: admin-schedule.php?date='.urlencode($date)); exit;
 }
 $q=database()->prepare('SELECT * FROM work_activities WHERE work_date=?');$q->execute([$date]);$activity=$q->fetch() ?: [];
 $q=database()->prepare('SELECT a.id,a.name,p.worker_id FROM work_roster r JOIN applicants a ON a.id=r.applicant_id LEFT JOIN registrant_profiles p ON p.applicant_id=a.id WHERE r.work_date=? ORDER BY a.name,a.id');$q->execute([$date]);$rows=$q->fetchAll();
} catch(Throwable $ex) { error_log('Request failed: '.basename(__FILE__)); $error=$ex instanceof PDOException?database_error():$ex->getMessage(); }
require 'header.php';
?>
<section class="container section"><h1>TUPAD work schedule</h1><?php require 'admin-nav.php'; ?>
<p>Select a work date, then enter each TUPAD ID to build the worker list. Adding a worker here does not mark them present.</p>
<?php if($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if($message): ?><p class="notice" role="status"><?= e($message) ?></p><?php endif; ?>
<form method="get" class="toolbar"><label class="field">Work date<input type="date" name="date" required value="<?= e($date) ?>"></label><button class="secondary">Open date</button></form>
<form method="post" class="card"><h2>Activity details · <?= e($date) ?></h2><input type="hidden" name="date" value="<?= e($date) ?>"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><input type="hidden" name="action" value="activity"><label class="field">Work time<input name="work_time" required maxlength="100" value="<?= e($activity['work_time'] ?? '') ?>" placeholder="8:00 AM – 12:00 PM"></label><label class="field">Venue / meeting point<input name="venue" required maxlength="250" value="<?= e($activity['venue'] ?? '') ?>"></label><label class="field">Instructions<textarea name="instructions" maxlength="1000"><?= e($activity['instructions'] ?? '') ?></textarea></label><button class="primary">Save activity details</button></form>
<form method="post" class="card"><h2>Add worker for <?= e($date) ?></h2><input type="hidden" name="date" value="<?= e($date) ?>"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><?php applicant_picker('worker_id', '', true); ?><button class="primary">Add to schedule</button></form>
<h2><?= count($rows) ?> worker(s) scheduled · <?= e($date) ?></h2><button type="button" class="secondary" data-print>Print worker list</button>
<div class="table-scroll"><table class="applicant-table"><thead><tr><th>Name</th><th>TUPAD ID</th><th>Applicant ID</th><th>Action</th></tr></thead><tbody>
<?php foreach($rows as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e($row['worker_id'] ?? 'Not assigned') ?></td><td><?= e($row['id']) ?></td><td><form method="post"><input type="hidden" name="date" value="<?= e($date) ?>"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><input type="hidden" name="applicant_id" value="<?= e($row['id']) ?>"><button class="secondary" name="action" value="remove">Remove from date</button></form></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="4">No workers scheduled for this date.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require 'footer.php'; ?>

