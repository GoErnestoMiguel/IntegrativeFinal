<?php
if(PHP_SAPI!=='cli') exit;
require __DIR__.'/../public_html/pages/connections.php'; require __DIR__.'/../public_html/pages/workflow.php';
date_default_timezone_set('Asia/Manila');
if(hosting_settings()['maintenance']) exit("Maintenance mode: worker skipped.\n");
$lock=fopen(__DIR__.'/../private/notification-worker.lock','c');
if(!$lock || !flock($lock,LOCK_EX|LOCK_NB)) exit;
$ids=database()->query("SELECT id FROM notification_log WHERE delivery IN ('pending','failed') AND attempts<5 AND (attempted_at IS NULL OR attempted_at<DATE_SUB(NOW(),INTERVAL 15 MINUTE)) ORDER BY id LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
foreach($ids as $id) deliver_notification($id);
database()->exec('DELETE FROM security_limits WHERE expires_at<UNIX_TIMESTAMP()');
flock($lock,LOCK_UN);fclose($lock);echo count($ids)." queued updates processed.\n";
