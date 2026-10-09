<?php
require_once __DIR__.'/admin-auth.php';
$admin_error='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['admin_action'])) {
 try {
  if(!valid_form()) throw new RuntimeException('Refresh and try again.');
  $action=(string)$_POST['admin_action'];
  if($action==='logout') {
   unset($_SESSION['admin_user'],$_SESSION['admin_expires'],$_SESSION['admin_db_login'],$_SESSION['admin_version'],$_SESSION['staff_challenge']);
   session_regenerate_id(true); header('Location: profile.php'); exit;
  }
  if($action==='login') {
   unset($_SESSION['staff_challenge']);
   $username=trim((string)($_POST['username'] ?? ''));
   if(!security_allow('staff-login',$username,5,900)) throw new RuntimeException('Too many attempts. Please try again later.');
   $q=database()->prepare('SELECT a.username,a.password_hash,s.email,s.auth_version FROM admins a JOIN staff_security s ON s.username=a.username WHERE a.username=? AND a.active=1'); $q->execute([$username]); $account=$q->fetch();
   if(!$account || !password_verify((string)($_POST['password'] ?? ''),$account['password_hash'])) throw new RuntimeException('Incorrect username or password.');
   $code=(string)random_int(100000,999999); require_once 'mailer.php';
   if(send_verification_email($account['email'],$code)!=='sent') throw new RuntimeException('The staff verification email could not be sent. Contact the system administrator.');
   session_regenerate_id(true);
   $_SESSION['staff_challenge']=['username'=>$account['username'],'version'=>(int)$account['auth_version'],'hash'=>password_hash($code,PASSWORD_DEFAULT),'expires'=>time()+600,'attempts'=>0];
   header('Location: profile.php#admin-login'); exit;
  }
  if($action==='verify') {
   $challenge=$_SESSION['staff_challenge'] ?? null;
   if(!$challenge || $challenge['expires']<time() || $challenge['attempts']>=5) { unset($_SESSION['staff_challenge']); throw new RuntimeException('The verification session expired. Sign in again.'); }
   $_SESSION['staff_challenge']['attempts']++;
   if(!security_allow('staff-code',$challenge['username'],10,600) || !password_verify((string)($_POST['staff_code'] ?? ''),$challenge['hash'])) throw new RuntimeException('Invalid or unavailable verification code.');
   $q=database()->prepare('SELECT s.auth_version FROM admins a JOIN staff_security s ON s.username=a.username WHERE a.username=? AND a.active=1'); $q->execute([$challenge['username']]);$version=$q->fetchColumn();
   if($version===false || (int)$version!==$challenge['version']) throw new RuntimeException('Staff access changed. Sign in again.');
   session_regenerate_id(true); $_SESSION['admin_user']=$challenge['username']; $_SESSION['admin_version']=(int)$version;
   $_SESSION['admin_db_login']=true; $_SESSION['admin_expires']=time()+1800; unset($_SESSION['staff_challenge']);
   header('Location: admin-panel.php'); exit;
  }
 } catch(PDOException $ex) { $admin_error='Staff sign-in is temporarily unavailable.'; }
 catch(RuntimeException $ex) { $admin_error=$ex->getMessage(); }
}
