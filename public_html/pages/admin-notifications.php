<?php
require 'config.php'; require 'admin-auth.php'; require_admin(); require 'workflow.php';
$page_title='Notifications'; $error=''; $rows=[];
try {
 workflow_require();
 if($_SERVER['REQUEST_METHOD']==='POST') {
  if(!valid_form()) throw new RuntimeException('Refresh and try again.');
  if(($_POST['action'] ?? '')==='batch') {
   $pending=database()->query("SELECT id FROM notification_log WHERE delivery='pending' ORDER BY id LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
   foreach($pending as $pendingId) deliver_notification($pendingId);
   header('Location: admin-notifications.php'); exit;
  }
  $id=(int)($_POST['notification_id'] ?? 0);
  // An interrupted delivery is deliberately not retried automatically: first check the recipient inbox.
  if(($_POST['action'] ?? '')==='release') {
   if(empty($_POST['checked'])) throw new RuntimeException('Confirm you checked whether the message was received.');
   $q=database()->prepare("UPDATE notification_log SET delivery='failed' WHERE id=? AND delivery='sending' AND attempted_at<DATE_SUB(NOW(),INTERVAL 10 MINUTE)"); $q->execute([$id]);
  }
  deliver_notification($id); header('Location: admin-notifications.php'); exit;
 }
 $q=database()->query('SELECT n.*,a.name FROM notification_log n JOIN applicants a ON a.id=n.applicant_id ORDER BY n.id DESC LIMIT 100'); $rows=$q->fetchAll();
} catch(Throwable $e) { $error=$e instanceof PDOException?database_error():$e->getMessage(); }
require 'header.php'; ?>
<section class="container section"><h1>Notification delivery</h1><?php require 'admin-nav.php'; ?>
<p>Latest 100 application updates. Email is sent only when the applicant selected Email. Profile-only updates remain available after login. “Sent” means accepted by the mail server; it does not prove the email was read.</p>
<form method="post"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><button class="primary" name="action" value="batch">Send next 3 queued updates</button></form>
<?php if($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<div class="table-scroll"><table class="applicant-table"><thead><tr><th scope="col">Applicant</th><th scope="col">Update</th><th scope="col">Delivery</th><th scope="col">Attempts</th><th scope="col">Action</th></tr></thead><tbody>
<?php foreach($rows as $n): ?><tr><td><a href="admin.php?id=<?= urlencode($n['applicant_id']) ?>"><?= e($n['name']) ?></a></td><td><?= e($n['subject']) ?><details><summary>Message</summary><p><?= nl2br(e($n['message'])) ?></p></details><small><?= e($n['created_at']) ?></small></td><td><?= e(str_replace('_',' ',$n['delivery'])) ?></td><td><?= (int)$n['attempts'] ?></td><td>
<?php if(in_array($n['delivery'],['pending','failed','not_configured','sending'],true)): ?><form method="post"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><input type="hidden" name="notification_id" value="<?= (int)$n['id'] ?>"><?php if($n['delivery']==='sending'): ?><label><input type="checkbox" name="checked" required> I checked delivery; retry is necessary.</label><button name="action" value="release" class="secondary">Retry interrupted delivery (after 10 minutes)</button><?php else: ?><button class="secondary">Send / retry</button><?php endif; ?></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="5">No notifications recorded yet.</td></tr><?php endif; ?></tbody></table></div></section><?php require 'footer.php'; ?>
