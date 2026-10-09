<?php
require_once 'documents.php';
if ($record && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['document_action']??'')==='upload' && !$error) {
 $created=[];$db=null;
 try {
  if (!valid_form()) throw new RuntimeException('Refresh the page and try again.');
  $files=document_uploads($record['program']);if (!$files) throw new RuntimeException('Choose at least one document.');
  $db=database();$db->beginTransaction();
  $q=$db->prepare('SELECT id FROM applicants WHERE id=? FOR UPDATE');$q->execute([$record['id']]);
  store_documents($db,$record['id'],$files,$created);$db->commit();
  header('Location: profile.php?documents=saved#documents');exit;
 } catch(Throwable $ex) {if ($db && $db->inTransaction())$db->rollBack();foreach($created as $path)@unlink($path);error_log('Request failed: '.basename(__FILE__));$error=$ex instanceof PDOException ? 'Could not save documents. Check the database migration with staff.' : $ex->getMessage();}
}

