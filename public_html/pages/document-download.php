<?php
require 'config.php';require_once 'admin-auth.php';require_once 'documents.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if (!$id) {http_response_code(404);exit('Document not found.');}
try {
 $owner=null;
 if (!admin_signed_in()) {
  $auth=$_SESSION['applicant_auth']??null;
  if (!$auth || $auth['expires']<=time()) {http_response_code(403);exit('Please sign in to access documents.');}
  $q=database()->prepare('SELECT auth_version,must_change_password FROM applicant_accounts WHERE applicant_id=?');$q->execute([$auth['id']]);$a=$q->fetch();
  if (!$a || (int)$a['auth_version']!==(int)$auth['version'] || $a['must_change_password']) {http_response_code(403);exit('Please sign in and update your password.');}
  $owner=$auth['id'];
 }
 $q=database()->prepare('SELECT * FROM applicant_documents WHERE id=?');$q->execute([$id]);$doc=$q->fetch();
 if (!$doc || ($owner!==null && $owner!==$doc['applicant_id'])) {http_response_code(404);exit('Document not found.');}
 if (!preg_match('/^[a-f0-9]{64}$/',$doc['storage_name'])) throw new RuntimeException('Invalid storage record.');
 $path=document_directory().DIRECTORY_SEPARATOR.$doc['storage_name'];
 if (!is_file($path)) {http_response_code(404);exit('Document unavailable. Contact staff.');}
 header('Content-Type: '.$doc['mime_type']);header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: sandbox");
 header('Content-Disposition: attachment; filename="'.preg_replace('/[^a-zA-Z0-9 ._()-]/','_',$doc['original_name']).'"');
 header('Content-Length: '.filesize($path));session_write_close();readfile($path);
} catch(Throwable $ex) {error_log('Request failed: '.basename(__FILE__));http_response_code(503);echo 'Document service unavailable. Please contact staff.';}
