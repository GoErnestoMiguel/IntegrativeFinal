<?php
require_once __DIR__.'/../config/bootstrap.php';
$s=hosting_settings(); return $s['mail']+['profile_url'=>rtrim($s['site_url'],'/').'/pages/profile.php'];
