<?php
require 'config.php'; require 'admin-auth.php'; require_admin(); require 'workflow.php';
$page_title='Review queue'; $rows=[]; $error=''; $queue=(string)($_GET['queue'] ?? 'Applications awaiting review'); $page=max(1,(int)($_GET['p'] ?? 1));
$filters=[
 'Applications awaiting review'=>"a.status IN ('Received','Under review')",
 'Unreviewed documents'=>"EXISTS(SELECT 1 FROM applicant_documents d LEFT JOIN document_reviews r ON r.document_id=d.id WHERE d.applicant_id=a.id AND (r.decision IS NULL OR r.decision='Under review'))",
 'Residency checks to resolve'=>"NOT EXISTS(SELECT 1 FROM residency_reviews r WHERE r.applicant_id=a.id AND r.decision IN ('Verified','Not verified'))"
];
if(!isset($filters[$queue])) $queue='Applications awaiting review';
try { workflow_require(); $rows=database()->query('SELECT a.id,a.name,a.program,a.status FROM applicants a WHERE '.$filters[$queue].' ORDER BY a.created_at,a.id LIMIT 26 OFFSET '.(($page-1)*25))->fetchAll(); } catch(Throwable $e) { $error=$e instanceof PDOException?database_error():$e->getMessage(); }
require 'header.php'; ?>
<section class="container section"><h1><?= e($queue) ?></h1><?php require 'admin-nav.php'; ?><?php if($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?><div class="table-scroll"><table class="applicant-table"><thead><tr><th scope="col">Applicant</th><th scope="col">Program</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead><tbody><?php foreach(array_slice($rows,0,25) as $r): ?><tr><td><?= e($r['name']) ?><br><?= e($r['id']) ?></td><td><?= e($r['program']) ?></td><td><?= e($r['status']) ?></td><td><a href="admin-review.php?id=<?= urlencode($r['id']) ?>">Review</a> · <a href="admin.php?id=<?= urlencode($r['id']) ?>">Status</a></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="4">No applications in this queue.</td></tr><?php endif; ?></tbody></table></div><div class="actions"><?php if($page>1): ?><a href="?<?= e(http_build_query(['queue'=>$queue,'p'=>$page-1])) ?>">Previous</a><?php endif; ?><?php if(count($rows)>25): ?><a href="?<?= e(http_build_query(['queue'=>$queue,'p'=>$page+1])) ?>">Next</a><?php endif; ?></div></section><?php require 'footer.php'; ?>
