<?php
require_once __DIR__.'/../../config/bootstrap.php';
require_once __DIR__.'/connections.php';
require_once __DIR__.'/security.php';
$settings=hosting_settings();
if(PHP_SAPI!=='cli') {
 if($settings['maintenance']) { http_response_code(503); header('Retry-After: 600'); exit('The website is undergoing maintenance. Please try again later.'); }
 if($settings['environment']==='local' && !in_array($_SERVER['REMOTE_ADDR'] ?? '',['127.0.0.1','::1'],true)) { http_response_code(403); exit('Local environment only.'); }
 if($settings['environment']==='production' && !request_secure()) {
  if(($_SERVER['REQUEST_METHOD'] ?? 'GET')!=='GET') { http_response_code(400); exit('Use the HTTPS website address.'); }
  header('Location: '.rtrim($settings['site_url'],'/').'/pages/index.php',true,302); exit;
 }
 header('X-Content-Type-Options: nosniff'); header('X-Frame-Options: DENY'); header('Referrer-Policy: same-origin');
 header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
 header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
 if($settings['environment']==='production') header('Strict-Transport-Security: max-age=31536000');
}
date_default_timezone_set('Asia/Manila');
ini_set('session.use_strict_mode','1'); ini_set('session.use_only_cookies','1');
session_name('tulong_bayan_hosting');
$sessionDir=dirname(__DIR__,2).'/private/sessions'; if(!is_dir($sessionDir)) mkdir($sessionDir,0700,true);
session_save_path($sessionDir);
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>$settings['environment']==='production']);
session_start(); header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0)>0) {
 http_response_code(413); exit('The submission could not be read. Keep photos within 20 MB in total, go back and select them again, or contact staff.');
}

$site_name = 'Tulong Bayan';
$barangay = 'Barangay San Sebastian, Lipa City';

// Escape values before displaying them in HTML.
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Basic form protection, also used for the demonstration staff form.
if (empty($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(16));
}

function valid_form() {
    return hash_equals($_SESSION['form_token'], (string) ($_POST['form_token'] ?? ''));
}

function demo_statuses($program) {
    if ($program === '4Ps') {
        return ['Received', 'Under review', 'Documents needed', 'Interview scheduled', 'Referred to DSWD', 'Confirmed member'];
    }
    return ['Received', 'Under review', 'Documents needed', 'Interview scheduled', 'Orientation scheduled', 'Community work scheduled', 'Confirmed member'];
}
