<?php
require 'config.php';
require_once 'admin-auth.php';
require_admin();
$page_title = 'Calendar and announcements';
$error = ''; $items = [];
$form = ['id'=>'','kind'=>'event','program'=>'All','title'=>'','body'=>'','event_date'=>'','event_time'=>'','venue'=>'','published'=>'0'];
try {
 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($form as $key=>$value) $form[$key] = is_string($_POST[$key] ?? '') ? trim($_POST[$key] ?? '') : '';
  if (!valid_form()) $error = 'Refresh the page and try again.';
  elseif ($form['id'] !== '' && !ctype_digit($form['id'])) $error = 'Invalid item ID.';
  elseif (($_POST['action'] ?? '') === 'delete') {
   if (empty($_POST['confirm_delete'])) $error = 'Check the confirmation box before deleting.';
   else { $q=database()->prepare('DELETE FROM public_updates WHERE id=?'); $q->execute([$form['id']]); header('Location: admin-content.php?saved=1'); exit; }
  } else {
   if (!in_array($form['kind'],['event','announcement'],true) || !in_array($form['program'],['All','TUPAD','4Ps'],true)) $error='Choose a valid content type and program.';
   elseif ($form['title']==='' || strlen($form['title'])>160 || $form['body']==='' || strlen($form['body'])>5000 || strlen($form['event_time'])>100 || strlen($form['venue'])>250) $error='Provide a title and description within the field limits.';
   elseif ($form['kind']==='event') {
    $d=DateTime::createFromFormat('!Y-m-d',$form['event_date']);
    if (!$d || $d->format('Y-m-d')!==$form['event_date'] || $form['event_time']==='' || $form['venue']==='') $error='Calendar events need a valid date, time and venue.';
   }
   if (!$error) {
    $values=[$form['kind'],$form['program'],$form['title'],$form['body'],$form['kind']==='event'?$form['event_date']:null,$form['kind']==='event'?$form['event_time']:'',$form['kind']==='event'?$form['venue']:'',$form['published']==='1'?1:0];
    if ($form['id']!=='') { $q=database()->prepare('UPDATE public_updates SET kind=?,program=?,title=?,body=?,event_date=?,event_time=?,venue=?,published=? WHERE id=?'); $values[]=$form['id']; }
    else $q=database()->prepare('INSERT INTO public_updates (kind,program,title,body,event_date,event_time,venue,published) VALUES (?,?,?,?,?,?,?,?)');
    $q->execute($values); header('Location: admin-content.php?saved=1'); exit;
   }
  }
 } elseif (isset($_GET['edit'])) {
  $q=database()->prepare('SELECT * FROM public_updates WHERE id=?');$q->execute([(string)$_GET['edit']]);$item=$q->fetch();
  if ($item) $form=$item; else $error='Item not found.';
 }
 $items=database()->query('SELECT * FROM public_updates ORDER BY updated_at DESC,id DESC')->fetchAll();
} catch (PDOException $exception) { error_log('Request failed: '.basename(__FILE__));$error=database_error(); }
require 'header.php';
?>
<section class="container section"><h1>Calendar and announcements</h1><?php require 'admin-nav.php'; ?>
<p>Create public updates or save drafts. Published items appear on the matching public page. Calendar events do not change individual worker assignments.</p>
<?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php elseif (isset($_GET['saved'])): ?><p class="notice" role="status">Changes saved.</p><?php endif; ?>
<form method="post" class="card">
<h2><?= $form['id'] ? 'Edit item' : 'Add an item' ?></h2>
<input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><input type="hidden" name="id" value="<?= e($form['id']) ?>">
<label class="field">Type<select name="kind"><option value="event" <?= $form['kind']==='event'?'selected':'' ?>>Calendar event</option><option value="announcement" <?= $form['kind']==='announcement'?'selected':'' ?>>Announcement</option></select></label>
<label class="field">Program<select name="program"><?php foreach (['All','TUPAD','4Ps'] as $p): ?><option <?= $form['program']===$p?'selected':'' ?>><?= e($p) ?></option><?php endforeach; ?></select></label>
<label class="field">Title<input name="title" required maxlength="160" value="<?= e($form['title']) ?>"></label>
<label class="field">Description / instructions<textarea name="body" required maxlength="5000" rows="5"><?= e($form['body']) ?></textarea></label>
<fieldset><legend>Calendar event details</legend><p class="small">Required for calendar events; ignored for announcements.</p>
<?php foreach (['event_date'=>'Date','event_time'=>'Time (Philippine time)','venue'=>'Venue'] as $key=>$label): ?><label class="field"><?= e($label) ?><input type="<?= $key==='event_date'?'date':'text' ?>" name="<?= e($key) ?>" maxlength="<?= $key==='venue'?250:100 ?>" value="<?= e($form[$key]) ?>"></label><?php endforeach; ?></fieldset>
<label class="checkbox"><input type="checkbox" name="published" value="1" <?= $form['published']?'checked':'' ?>><span>Publish on the website (leave unchecked for draft)</span></label>
<button class="primary">Save item</button> <a href="admin-content.php">New item / cancel editing</a></form>
<h2>Saved items</h2><div class="table-scroll"><table class="applicant-table"><thead><tr><th>Title</th><th>Type</th><th>Program</th><th>Date</th><th>Visibility</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($items as $item): ?><tr><td><?= e($item['title']) ?></td><td><?= e($item['kind']) ?></td><td><?= e($item['program']) ?></td><td><?= e($item['event_date'] ?? '—') ?></td><td><?= $item['published']?'Published':'Draft' ?></td><td><a href="admin-content.php?edit=<?= (int)$item['id'] ?>">Edit</a><form method="post"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><label><input type="checkbox" name="confirm_delete" required> Confirm deletion</label><button class="secondary" name="action" value="delete">Delete</button></form></td></tr><?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="6">No saved items yet.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require 'footer.php'; ?>
