<?php
if(PHP_SAPI!=='cli') exit;
require __DIR__.'/../public_html/pages/connections.php';
$username=getenv('TB_STAFF_USERNAME') ?: ''; $action=$argv[1] ?? '';
if(!preg_match('/^[A-Za-z0-9_.-]{3,80}$/',$username) || !in_array($action,['create','rotate','disable'],true)) exit("Set TB_STAFF_USERNAME and use create, rotate or disable.\n");
$db=database();$db->beginTransaction();
try {
 if($action==='disable') {
  $db->prepare('UPDATE admins SET active=0 WHERE username=?')->execute([$username]);
  $db->prepare('UPDATE staff_security SET auth_version=auth_version+1 WHERE username=?')->execute([$username]);
 } else {
  $email=getenv('TB_STAFF_EMAIL') ?: ''; $password=getenv('TB_STAFF_PASSWORD') ?: '';
  if(!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<15 || strlen($password)>72) throw new RuntimeException('Set valid staff email and 15–72 character password.');
  if($action==='create') {
   $db->prepare('INSERT INTO admins(username,password_hash,active) VALUES (?,?,1)')->execute([$username,password_hash($password,PASSWORD_DEFAULT)]);
   $db->prepare('INSERT INTO staff_security(username,email) VALUES (?,?)')->execute([$username,$email]);
  } else {
   $q=$db->prepare('SELECT username FROM admins WHERE username=? FOR UPDATE');$q->execute([$username]);if(!$q->fetchColumn()) throw new RuntimeException('Staff account not found.');
   $db->prepare('UPDATE admins SET password_hash=?,active=1 WHERE username=?')->execute([password_hash($password,PASSWORD_DEFAULT),$username]);
   $db->prepare('INSERT INTO staff_security(username,email) VALUES (?,?) ON DUPLICATE KEY UPDATE email=VALUES(email),auth_version=auth_version+1')->execute([$username,$email]);
  }
 }
 $db->commit();echo "Staff operation completed. Clear the password environment variable after use.\n";
} catch(Throwable $e) { if($db->inTransaction())$db->rollBack();throw $e; }
