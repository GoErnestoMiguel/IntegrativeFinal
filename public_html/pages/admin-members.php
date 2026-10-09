<?php
require 'config.php'; require 'admin-auth.php'; require_admin();
$page_title='Program members';$error='';$members=[];
try {
 $members=database()->query("SELECT a.id,a.name,a.program,a.status,m.admitted_at FROM tupad_members m JOIN applicants a ON a.id=m.applicant_id UNION ALL SELECT a.id,a.name,a.program,a.status,m.admitted_at FROM fourps_members m JOIN applicants a ON a.id=m.applicant_id ORDER BY program,name")->fetchAll();
} catch(PDOException $ex){$error=database_error();}
require 'header.php';
?>
<section class="container section"><h1>Program members</h1><?php require 'admin-nav.php'; ?><p>Applicants are added here only after staff select Confirmed member in their application status. Registration alone does not create membership.</p>
<?php if($error):?><p class="error"><?= e($error) ?></p><?php endif;?>
<div class="table-scroll"><table class="applicant-table"><thead><tr><th>Program</th><th>Name</th><th>Applicant ID</th><th>Current status</th><th>Membership recorded</th></tr></thead><tbody><?php foreach($members as $m):?><tr><td><?= e($m['program']) ?></td><td><?= e($m['name']) ?></td><td><a href="admin.php?id=<?= urlencode($m['id']) ?>"><?= e($m['id']) ?></a></td><td><?= e($m['status']) ?></td><td><?= e($m['admitted_at']) ?></td></tr><?php endforeach;?><?php if(!$members):?><tr><td colspan="5">No confirmed members yet.</td></tr><?php endif;?></tbody></table></div></section>
<?php require 'footer.php'; ?>
