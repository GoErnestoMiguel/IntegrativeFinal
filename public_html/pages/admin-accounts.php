<?php
require 'config.php'; require 'admin-auth.php'; require_admin(); require 'mailer.php';
$page_title='Applicant accounts';$error='';$issued=null;
if($_SERVER['REQUEST_METHOD']==='POST') {
 $id=strtoupper(trim((string)($_POST['id']??'')));$email=trim((string)($_POST['email']??''));
 if(!valid_form())$error='Refresh and try again.';
 elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254)$error='Enter the applicant’s verified email address.';
 elseif(empty($_POST['verified']))$error='Confirm you have verified the applicant’s identity and email.';
 else try {
  $db=database();$db->beginTransaction();
  $q=$db->prepare('SELECT id,name FROM applicants WHERE id=? FOR UPDATE');$q->execute([$id]);$a=$q->fetch();
  if(!$a){$db->rollBack();$error='Applicant not found.';}
  else {
   $password=bin2hex(random_bytes(10));
   $q=$db->prepare('SELECT username FROM applicant_accounts WHERE applicant_id=?');$q->execute([$id]);$username=$q->fetchColumn();
   if($username){$q=$db->prepare('UPDATE applicant_accounts SET email=?,password_hash=?,must_change_password=1,auth_version=auth_version+1 WHERE applicant_id=?');$q->execute([$email,password_hash($password,PASSWORD_DEFAULT),$id]);}
   else {$username='user-'.bin2hex(random_bytes(8));$q=$db->prepare('INSERT INTO applicant_accounts(applicant_id,username,password_hash,email) VALUES (?,?,?,?)');$q->execute([$id,$username,password_hash($password,PASSWORD_DEFAULT),$email]);}
   $q=$db->prepare('INSERT INTO account_recovery_expiry (applicant_id,expires_at) VALUES (?,DATE_ADD(NOW(),INTERVAL 24 HOUR)) ON DUPLICATE KEY UPDATE expires_at=VALUES(expires_at)');$q->execute([$id]);
   $db->commit();$state=registration_email($email,$a['name'],$id,$username,$password,true);
   $q=$db->prepare('UPDATE applicant_accounts SET mail_status=? WHERE applicant_id=?');$q->execute([$state,$id]);
   $_SESSION['issued_account']=['id'=>$id,'username'=>$username,'password'=>$password,'state'=>$state];header('Location: admin-accounts.php');exit;
  }
 } catch(PDOException $ex){if(isset($db)&&$db->inTransaction())$db->rollBack();$error=database_error();}
}
$issued=$_SESSION['issued_account']??null;unset($_SESSION['issued_account']);
require 'header.php';
?>
<section class="container section"><h1>Applicant accounts</h1><?php require 'admin-nav.php'; ?>
<p>Issue login details for an existing applicant or reset a forgotten password after verifying their identity. Resetting invalidates their previous password and active sessions.</p>
<?php if($error):?><p class="error"><?= e($error) ?></p><?php endif;?>
<?php if($issued):?><div class="notice"><p>Applicant ID: <?= e($issued['id']) ?></p><p>Username: <?= e($issued['username']) ?></p><p>Temporary password: <?= e($issued['password']) ?></p><p><?= $issued['state']==='sent'?'Email accepted by the mail server.':'Email not sent. Provide these one-time details securely to the applicant.' ?></p></div><?php endif;?>
<form method="post" class="card"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><label class="field">Applicant ID<input name="id" required maxlength="32"></label><label class="field">Applicant email<input type="email" name="email" required maxlength="254"></label><label class="checkbox"><input type="checkbox" name="verified" required><span>I verified the applicant’s identity and email address.</span></label><button class="primary">Issue new temporary password</button></form></section>
<?php require 'footer.php'; ?>
