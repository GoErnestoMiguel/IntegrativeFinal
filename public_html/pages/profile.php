<?php
require 'config.php';
require 'profile-functions.php';
require_once 'workflow.php';
require 'admin-login-handler.php';
$page_title = 'My profile';
require 'applicant-login-handler.php';
require 'document-upload-handler.php';
require 'header.php';
?>
<section class="container section narrow"><p class="eyebrow">REGISTRANT PORTAL</p><h1>My profile</h1>
<?php require 'account-section.php'; ?>
<?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if ($record): $profile = $record['profile']; ?>
<article class="card section"><span class="badge"><?= e($record['program']) ?></span><h2><?= e($record['name']) ?></h2>
<dl class="review"><?php foreach (['id'=>'Applicant ID','birth_date'=>'Date of birth','address'=>'Address','contact'=>'Contact','status'=>'Current application status'] as $key=>$label): ?><div><dt><?= e($label) ?></dt><dd><?= e($record[$key] ?: 'Not provided') ?></dd></div><?php endforeach; ?></dl>
<p class="notice"><strong>What happens next</strong><br><?= e(next_step_text($record['status'])) ?></p><?php if(workflow_ready()): $q=database()->prepare('SELECT decision,reason FROM residency_reviews WHERE applicant_id=?'); $q->execute([$record['id']]); $residency=$q->fetch(); ?><h3>Residency review</h3><p><?= e($residency['decision'] ?? 'Awaiting staff review') ?></p><p><?= e($residency['reason'] ?? 'Staff will compare your details against barangay records.') ?></p><?php endif; ?><section id="documents"><h3>Your documents</h3><?php if (isset($_GET['documents'])): ?><p class="notice">Documents submitted for staff review.</p><?php endif; ?><?php document_list($record['id']); ?><form method="post" enctype="multipart/form-data"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><?php document_fields($record['program']); ?><button class="primary" name="document_action" value="upload">Submit documents</button></form></section><h3>Your identification</h3><div class="notice"><strong><?= e($record['name']) ?></strong><p>Applicant ID: <?= e($record['id']) ?></p>
<?php if ($record['program'] === 'TUPAD'): ?><p>TUPAD ID: <strong><?= e($profile['worker_id'] ?? 'Not assigned yet') ?></strong></p><?php endif; ?>
<p class="small">This is your website identification record, not a government-issued identity card or proof of approval.</p></div>
<h3>Scheduled work dates</h3>
<?php if (!empty($record['work_dates'])): ?><ul><?php foreach ($record['work_dates'] as $work_date): ?><li><?= e($work_date) ?><?= $work_date < date('Y-m-d') ? ' (past)' : '' ?></li><?php endforeach; ?></ul><?php elseif ($record['program']==='TUPAD'): ?><p>No dates added to the worker roster yet.</p><?php endif; ?>
<?php if(workflow_ready() && $record['program']==='TUPAD'): $q=database()->prepare('SELECT w.* FROM work_activities w JOIN work_roster r ON r.work_date=w.work_date WHERE r.applicant_id=? ORDER BY w.work_date'); $q->execute([$record['id']]); foreach($q->fetchAll() as $activity): ?><article class="notice"><strong><?= e($activity['work_date']) ?> · <?= e($activity['work_time']) ?></strong><p><?= e($activity['venue']) ?></p><p><?= nl2br(e($activity['instructions'])) ?></p></article><?php endforeach; endif; ?>
<h3>Previously recorded assignment details</h3>
<?php if ($record['program'] !== 'TUPAD'): ?><p>Work schedules apply to TUPAD only.</p>
<?php elseif (empty($profile['work_start'])): ?><p>No work schedule assigned yet. Check again after staff update your profile.</p>
<?php else: ?><dl class="review"><div><dt>Dates</dt><dd><?= e($profile['work_start']) ?> to <?= e($profile['work_end']) ?></dd></div><div><dt>Time</dt><dd><?= e($profile['work_time']) ?></dd></div><div><dt>Location</dt><dd><?= e($profile['work_venue']) ?></dd></div><div><dt>Instructions / working days</dt><dd><?= nl2br(e($profile['work_notes'])) ?></dd></div></dl><?php endif; ?>
<?php if(workflow_ready()): $q=database()->prepare('SELECT subject,message,created_at FROM notification_log WHERE applicant_id=? ORDER BY id DESC LIMIT 20'); $q->execute([$record['id']]); ?><h3>Latest notices for you</h3><div class="timeline"><?php foreach($q->fetchAll() as $notice): ?><article><strong><?= e($notice['subject']) ?></strong><p class="small"><?= e($notice['created_at']) ?></p><p><?= nl2br(e($notice['message'])) ?></p></article><?php endforeach; ?></div><?php endif; ?>
<h3>Application updates</h3><div class="timeline"><?php foreach ($record['history'] as $item): ?><article><strong><?= e($item['status']) ?></strong><p><?= e($item['created_at']) ?></p><p><?= e($item['message']) ?></p></article><?php endforeach; ?></div>
<form method="post"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><button name="close_profile" value="1" class="secondary">Close my profile</button></form></article>
<?php endif; ?></section>
<div class="container narrow"><?php require 'admin-login-section.php'; ?></div>
<?php require 'footer.php'; ?>


