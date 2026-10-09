<?php
if(PHP_SAPI!=='cli') exit;
require __DIR__.'/../public_html/pages/connections.php';
$s=hosting_settings();
if(!$s['maintenance']) exit("Enable maintenance mode, wait for active requests to finish, and stop scheduled jobs before backing up.\n");
if(!class_exists('ZipArchive')) exit("Enable PHP zip extension or use your host's database and private-file backup service.\n");
$dir=__DIR__.'/../private/backups';if(!is_dir($dir)) mkdir($dir,0700,true);
$path=$dir.'/backup-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.zip';
$zip=new ZipArchive();if($zip->open($path,ZipArchive::CREATE|ZipArchive::EXCL)!==true) throw new RuntimeException('Backup unavailable.');
$db=database();$db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');$db->beginTransaction();
$temp=tempnam($dir,'sql-');chmod($temp,0600);$file=fopen($temp,'wb');
try {
 fwrite($file,"-- Restore into a NEW empty database. Contains private data.\nSET FOREIGN_KEY_CHECKS=0;\n");
 foreach($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
  if(!preg_match('/^[a-zA-Z0-9_]+$/',$table)) throw new RuntimeException('Unsupported table identifier.');
  $ddl=$db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];fwrite($file,$ddl.";\n");
  $rows=$db->query("SELECT * FROM `$table`");
  while($row=$rows->fetch(PDO::FETCH_ASSOC)) {
   $values=array_map(fn($v)=>$v===null?'NULL':$db->quote((string)$v),array_values($row));
   fwrite($file,"INSERT INTO `$table` VALUES (".implode(',',$values).");\n");
  }
 }
 fwrite($file,"SET FOREIGN_KEY_CHECKS=1;\n");fclose($file);$file=null;
 $zip->addFile($temp,'database.sql');
 $docs=$db->query('SELECT storage_name FROM applicant_documents')->fetchAll(PDO::FETCH_COLUMN);
 foreach($docs as $name) {
  if(!preg_match('/^[a-f0-9]{64}$/',$name) || !is_file($s['document_dir'].'/'.$name)) throw new RuntimeException('Missing document: backup is incomplete.');
  $zip->addFile($s['document_dir'].'/'.$name,'documents/'.$name);
 }
 if(!$zip->close()) throw new RuntimeException('Could not finalize backup.');
 $db->commit();chmod($path,0600);unlink($temp);
 echo "Backup created in private/backups. Encrypt it and copy it to restricted off-server storage. Test restoring into an isolated database.\n";
} catch(Throwable $e) {
 if($db->inTransaction())$db->rollBack();if(is_resource($file))fclose($file);
 @$zip->close();@unlink($temp);@unlink($path);throw $e;
}
