<?php
if(PHP_SAPI!=='cli') exit;
require __DIR__.'/../public_html/pages/connections.php';
$db=database();
foreach(['database.sql','hosting-migration.sql'] as $file) {
 $sql=file_get_contents(__DIR__.'/../database/'.$file);
 $sql=preg_replace('/CREATE DATABASE IF NOT EXISTS tulong_bayan_preview\s+CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;/','',$sql);
 $sql=str_replace('USE tulong_bayan_preview;','',$sql); $db->exec($sql);
}
echo "Hosting schema installed in the configured database. No default staff account was created.\n";
