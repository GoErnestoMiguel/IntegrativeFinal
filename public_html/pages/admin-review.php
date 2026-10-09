<?php
require 'config.php'; require 'admin-auth.php'; require_admin(); require 'workflow.php'; require 'documents.php';
$page_title='Application review'; $error=''; $id=(string)($_POST['id'] ?? $_GET['id'] ?? ''); $application=null; $candidates=$duplicates=$docs=$audit=[]; $review=null;
try {
 workflow_require(); $application=find_applicant($id);
 if (!$application) throw new RuntimeException('Choose an applicant from the Applicants page.');
 if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!valid_form()) throw new RuntimeException('Refresh and try again.');
  $action=(string)($_POST['action'] ?? ''); $reason=trim((string)($_POST['reason'] ?? '')); $decision=(string)($_POST['decision'] ?? '');
  if (strlen($reason)<10 || strlen($reason)>1000) throw new RuntimeException('Enter a review reason or instructions between 10 and 1,000 characters.');
  $db=database(); $db->beginTransaction(); $notice=null;
  try {
   $q=$db->prepare('SELECT id FROM applicants WHERE id=? FOR UPDATE'); $q->execute([$id]);
   if ($action==='residency') {
    if (!in_array($decision,['Verified','Needs clarification','Not verified'],true)) throw new RuntimeException('Choose a residency decision.');
    $resident=trim((string)($_POST['resident_id'] ?? ''));
    if ($resident!=='') { $q=$db->prepare('SELECT resident_id FROM resident_registry WHERE resident_id=?'); $q->execute([$resident]); if (!$q->fetchColumn()) throw new RuntimeException('Resident record not found.'); }
    if ($decision==='Verified' && $resident==='') throw new RuntimeException('Link a resident record before verifying residency.');
    $q=$db->prepare('INSERT INTO residency_reviews (applicant_id,resident_id,decision,reason,reviewed_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE resident_id=VALUES(resident_id),decision=VALUES(decision),reason=VALUES(reason),reviewed_by=VALUES(reviewed_by),reviewed_at=CURRENT_TIMESTAMP');
    $q->execute([$id,$resident?:null,$decision,$reason,$_SESSION['admin_user']]);
    workflow_audit($db,$id,'Residency review',"$decision; resident: $resident; $reason");
   } elseif ($action==='document') {
    if (!in_array($decision,['Accepted','Needs replacement','Under review'],true)) throw new RuntimeException('Choose a document decision.');
    $doc=(int)($_POST['document_id'] ?? 0); $q=$db->prepare('SELECT id FROM applicant_documents WHERE id=? AND applicant_id=? FOR UPDATE'); $q->execute([$doc,$id]);
    if (!$q->fetchColumn()) throw new RuntimeException('Document not found for this applicant.');
    $q=$db->prepare('INSERT INTO document_reviews (document_id,decision,instructions,reviewed_by) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE decision=VALUES(decision),instructions=VALUES(instructions),reviewed_by=VALUES(reviewed_by),reviewed_at=CURRENT_TIMESTAMP');
    $q->execute([$doc,$decision,$reason,$_SESSION['admin_user']]);
    workflow_audit($db,$id,'Document review',"Document $doc: $decision; $reason");
    $notice=queue_notification($db,$id,'Document review updated',"A document was marked $decision. $reason\nSign in to view the document and submit additional copies if requested.");
   } elseif ($action==='duplicate') {
    if (!in_array($decision,['Separate legitimate application','Duplicate application'],true)) throw new RuntimeException('Choose a duplicate review outcome.');
    $other=(string)($_POST['other_id'] ?? ''); $valid=false;
    foreach(duplicate_candidates($application) as $candidate) if($candidate['id']===$other) $valid=true;
    if(!$valid) throw new RuntimeException('This record is not a current duplicate candidate.');
    $q=$db->prepare('INSERT INTO duplicate_reviews (applicant_id,other_id,decision,reason,reviewed_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE decision=VALUES(decision),reason=VALUES(reason),reviewed_by=VALUES(reviewed_by),reviewed_at=CURRENT_TIMESTAMP');
    $q->execute([$id,$other,$decision,$reason,$_SESSION['admin_user']]); workflow_audit($db,$id,'Duplicate review',"$other: $decision; $reason");
   } else throw new RuntimeException('Unknown review action.');
   $db->commit();
  } catch(Throwable $ex) { if($db->inTransaction()) $db->rollBack(); throw $ex; }
  if($notice) { try { deliver_notification($notice); } catch(Throwable $ex) { error_log('Notification delivery pending.'); } }
  header('Location: admin-review.php?id='.urlencode($id).'&saved=1'); exit;
 }
 $candidates=resident_candidates($application); $duplicates=duplicate_candidates($application);
 $q=database()->prepare('SELECT * FROM residency_reviews WHERE applicant_id=?'); $q->execute([$id]); $review=$q->fetch();
 $q=database()->prepare('SELECT d.*,r.decision,r.instructions FROM applicant_documents d LEFT JOIN document_reviews r ON r.document_id=d.id WHERE d.applicant_id=? ORDER BY d.id DESC'); $q->execute([$id]); $docs=$q->fetchAll();
 $q=database()->prepare('SELECT * FROM workflow_audit WHERE applicant_id=? ORDER BY id DESC LIMIT 100'); $q->execute([$id]); $audit=$q->fetchAll();
} catch(Throwable $ex) { $error=$ex instanceof PDOException?database_error():$ex->getMessage(); }
if($error && $application && workflow_ready()) {
 try {
  $candidates=resident_candidates($application); $duplicates=duplicate_candidates($application);
  $q=database()->prepare('SELECT * FROM residency_reviews WHERE applicant_id=?'); $q->execute([$id]); $review=$q->fetch();
  $q=database()->prepare('SELECT d.*,r.decision,r.instructions FROM applicant_documents d LEFT JOIN document_reviews r ON r.document_id=d.id WHERE d.applicant_id=? ORDER BY d.id DESC'); $q->execute([$id]); $docs=$q->fetchAll();
  $q=database()->prepare('SELECT * FROM workflow_audit WHERE applicant_id=? ORDER BY id DESC LIMIT 100'); $q->execute([$id]); $audit=$q->fetchAll();
 } catch(PDOException $ex) { $application=null; }
}
require 'header.php'; ?>
<section class="container section"><p class="eyebrow">STAFF REVIEW</p><h1>Verify application</h1><?php require 'admin-nav.php'; ?>
<?php if($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if(isset($_GET['saved'])): ?><p class="notice" role="status">Review saved. Applicant document comments are visible in My Profile.</p><?php endif; ?>
<?php if($application): ?>
<a href="admin.php?id=<?= urlencode($id) ?>">Back to application and status</a>
<article class="card"><h2><?= e($application['name']) ?></h2><dl class="review"><?php foreach(['id'=>'Application ID','program'=>'Program','birth_date'=>'Birth date','address'=>'Applicant address'] as $k=>$v): ?><div><dt><?= e($v) ?></dt><dd><?= e($application[$k]) ?></dd></div><?php endforeach; ?></dl></article>
<h2>Suggested resident records</h2><p>Suggestions compare names and birth dates. Compare the address, household and supporting documents yourself. A match does not automatically verify identity or program eligibility. Records sharing only a birth date may be unrelated.</p>
<div class="review-candidates"><?php foreach($candidates as $r): ?><article class="card"><h3><?= e($r['display_name']) ?></h3><p><?= e(implode(' · ',$r['match_reasons'])) ?></p><div class="two-column"><div><h4>Applicant details</h4><p><?= e($application['name']) ?></p><p>Born <?= e($application['birth_date']) ?></p><p><?= e($application['address']) ?></p></div><div><h4>Resident record</h4><dl class="review"><div><dt>Resident ID</dt><dd><?= e($r['resident_id']) ?><?= $r['is_sample']?' — FICTIONAL SAMPLE':'' ?></dd></div><div><dt>Birth date</dt><dd><?= e($r['birth_date']) ?></dd></div><div><dt>Household</dt><dd><?= e($r['household_id']) ?></dd></div><div><dt>Registry address</dt><dd><?= e($r['display_address']) ?></dd></div></dl></div></div><a href="admin-residents.php?<?= e(http_build_query(['search'=>$r['resident_id']])) ?>">Inspect resident record</a><button type="button" class="secondary" data-select-resident="<?= e($r['resident_id']) ?>">Use this resident ID</button></article><?php endforeach; ?></div>
<?php if(!$candidates): ?><p>No suggested record. Search Residents manually; check spelling, birth date and whether the latest records were imported.</p><?php endif; ?>
<form method="post" class="card" id="residency-review"><h2>Residency decision</h2><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><input type="hidden" name="action" value="residency"><label class="field">Resident ID (required for Verified)<input id="review-resident-id" name="resident_id" maxlength="64" value="<?= e($review['resident_id'] ?? '') ?>"></label><label class="field">Decision<select name="decision"><?php foreach(['Needs clarification','Verified','Not verified'] as $v): ?><option <?= ($review['decision']??'')===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label><label class="field">Evidence checked and reason<textarea name="reason" required minlength="10" maxlength="1000"><?= e($review['reason'] ?? '') ?></textarea></label><p class="small">Residency verification is separate from program approval. Use fictional records only for demonstrations.</p><button class="primary">Save residency review</button></form>
<h2>Possible duplicate applications</h2><p>Same normalized name, birth date and program. Confirm whether these belong to a different application period or person. No record is automatically deleted or rejected.</p>
<?php if(!$duplicates): ?><p>No exact-name duplicate candidates found.</p><?php endif; ?>
<?php foreach($duplicates as $d): ?><form method="post" class="card"><p><a href="admin.php?id=<?= urlencode($d['id']) ?>"><?= e($d['id']) ?></a> · <?= e($d['created_at']) ?> · <?= e($d['status']) ?></p><p>Review: <?= e($d['decision'] ?? 'Unresolved') ?></p><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="other_id" value="<?= e($d['id']) ?>"><input type="hidden" name="action" value="duplicate"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><label class="field">Outcome<select name="decision"><option>Separate legitimate application</option><option>Duplicate application</option></select></label><label class="field">Reason, including the application period if different<textarea name="reason" required minlength="10" maxlength="1000"><?= e($d['reason'] ?? '') ?></textarea></label><button class="secondary">Save duplicate review</button></form><?php endforeach; ?>
<h2>Document review</h2><p>New copies remain separate so the earlier submission and review are preserved. Each new copy starts Under review.</p>
<?php if(!$docs): ?><p>No documents submitted. Use Documents needed status to request applicable copies.</p><?php endif; ?>
<?php foreach($docs as $d): ?><form method="post" class="card"><h3><?= e(document_categories()[$d['category']] ?? $d['category']) ?></h3><p><a href="document-download.php?id=<?= (int)$d['id'] ?>"><?= e($d['original_name']) ?></a> · <?= e($d['uploaded_at']) ?></p><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="document_id" value="<?= (int)$d['id'] ?>"><input type="hidden" name="action" value="document"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><label class="field">Review result<select name="decision"><?php foreach(['Under review','Accepted','Needs replacement'] as $v): ?><option <?= ($d['decision']??'')===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label><label class="field">Instructions visible to applicant<textarea name="reason" required minlength="10" maxlength="1000"><?= e($d['instructions'] ?? '') ?></textarea></label><button class="primary">Save document review</button></form><?php endforeach; ?>
<h2>Review history</h2><div class="table-scroll"><table class="applicant-table"><thead><tr><th scope="col">Date</th><th scope="col">Staff</th><th scope="col">Action</th><th scope="col">Details</th></tr></thead><tbody><?php foreach($audit as $a): ?><tr><td><?= e($a['created_at']) ?></td><td><?= e($a['staff']) ?></td><td><?= e($a['action']) ?></td><td><?= e($a['details']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?></section><script src="../javascript/workflow.js" defer></script><?php require 'footer.php'; ?>
