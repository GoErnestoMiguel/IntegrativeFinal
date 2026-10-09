<?php
if(PHP_SAPI!=='cli') exit;
require __DIR__.'/../public_html/pages/connections.php';
if(($argv[1]??'')!=='--confirm') exit("After importing legacy applicants into the NEW hosting database, run with --confirm to invalidate all old applicant passwords. Staff must issue new credentials after identity checks. Back up first.\n");
$db=database();$db->beginTransaction();
try {
 $ids=$db->query('SELECT applicant_id FROM applicant_accounts FOR UPDATE')->fetchAll(PDO::FETCH_COLUMN);
 $q=$db->prepare('UPDATE applicant_accounts SET password_hash=?,must_change_password=1,auth_version=auth_version+1 WHERE applicant_id=?');
 foreach($ids as $id)$q->execute([password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),$id]);
 $db->commit();echo count($ids)." legacy applicant passwords invalidated.\n";
} catch(Throwable $e) {if($db->inTransaction())$db->rollBack();throw $e;}
