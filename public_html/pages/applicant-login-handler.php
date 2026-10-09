<?php
// Called by profile.php. ID-only session state is intentionally never trusted.
$error=''; $record=null; $account=null;
if ($_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['admin_action'])) {
 if (!valid_form()) $error='Refresh and try again.';
 elseif (isset($_POST['close_profile'])) {
  unset($_SESSION['applicant_auth'],$_SESSION['profile_applicant_id']); session_regenerate_id(true);
  header('Location: profile.php'); exit;
 } elseif (($_POST['account_action'] ?? '')==='login') {
  try {
   if(!security_allow('applicant-login',(string)($_POST['username'] ?? ''))) $error='Too many attempts. Try again later.';
   elseif (($_SESSION['applicant_retry'] ?? 0)>time()) $error='Too many attempts. Wait one minute and try again.';
   else {
    $q=database()->prepare('SELECT * FROM applicant_accounts WHERE username=?');$q->execute([trim((string)($_POST['username'] ?? ''))]);$candidate=$q->fetch();
    if ($candidate && password_verify((string)($_POST['password'] ?? ''),$candidate['password_hash'])) {
     $q=database()->prepare('SELECT expires_at<NOW() FROM account_recovery_expiry WHERE applicant_id=?');$q->execute([$candidate['applicant_id']]);
     if($candidate['must_change_password'] && $q->fetchColumn()) { $error='Your temporary password expired. Please contact staff for a new one.'; return; }
     session_regenerate_id(true);$_SESSION['applicant_auth']=['id'=>$candidate['applicant_id'],'version'=>$candidate['auth_version'],'expires'=>time()+1800];
     unset($_SESSION['applicant_attempts'],$_SESSION['applicant_retry']); header('Location: profile.php');exit;
    }
    $_SESSION['applicant_attempts']=($_SESSION['applicant_attempts']??0)+1;
    if ($_SESSION['applicant_attempts']>=5) { $_SESSION['applicant_retry']=time()+60;$_SESSION['applicant_attempts']=0; }
    $error='Incorrect username or password.';
   }
  } catch(PDOException $ex) {$error=database_error();}
 }
}
$auth=$_SESSION['applicant_auth']??null;
if ($auth && $auth['expires']>time()) {
 try {
  $q=database()->prepare('SELECT * FROM applicant_accounts WHERE applicant_id=?');$q->execute([$auth['id']]);$account=$q->fetch();
  if (!$account || (int)$account['auth_version']!==(int)$auth['version']) {unset($_SESSION['applicant_auth']);$account=null;}
  else {
   $_SESSION['applicant_auth']['expires']=time()+1800;
   if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['account_action']??'')==='update' && !$error) {
    $username=trim((string)($_POST['new_username']??''));$password=(string)($_POST['new_password']??'');
    if (!password_verify((string)($_POST['current_password']??''),$account['password_hash'])) $error='The current password is incorrect.';
    elseif (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/',$username)) $error='Use 3–80 letters, numbers, dots, underscores or hyphens for your username.';
    elseif (($password!=='' || $account['must_change_password']) && (strlen($password)<15 || strlen($password)>72)) $error='Choose a password containing 15–72 characters.';
    elseif ($password!=='' && $password!==(string)($_POST['confirm_password']??'')) $error='New passwords do not match.';
    elseif ($password!=='' && password_verify($password,$account['password_hash'])) $error='Choose a different password from your current one.';
    else {
     $hash=$password!==''?password_hash($password,PASSWORD_DEFAULT):$account['password_hash'];
     $q=database()->prepare('UPDATE applicant_accounts SET username=?,password_hash=?,must_change_password=?,auth_version=auth_version+1 WHERE applicant_id=?');
     $q->execute([$username,$hash,$password!==''?0:$account['must_change_password'],$account['applicant_id']]);
     $_SESSION['applicant_auth']['version']++;session_regenerate_id(true); header('Location: profile.php?updated=1');exit;
    }
   }
   if (!$account['must_change_password']) $record=profile_record($account['applicant_id']);
  }
 } catch(PDOException $ex) {$error=($ex->errorInfo[1]??0)===1062?'That username is already in use.':database_error();}
} else unset($_SESSION['applicant_auth']);
