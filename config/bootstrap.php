<?php
function hosting_settings() {
 static $settings;
 if($settings===null) {
  $settings=require __DIR__.'/settings.php';
  if(!in_array($settings['environment'],['local','production'],true)) throw new RuntimeException('Invalid environment configuration.');
  if(!filter_var($settings['site_url'],FILTER_VALIDATE_URL) || !in_array(parse_url($settings['site_url'],PHP_URL_SCHEME),['http','https'],true)) throw new RuntimeException('Configure the site URL.');
  if(strlen($settings['app_key'])<32) throw new RuntimeException('Configure a random application key with at least 32 characters.');
  if($settings['environment']==='production') {
   if(parse_url($settings['site_url'],PHP_URL_SCHEME)!=='https' || !$settings['db_user'] || strtolower($settings['db_user'])==='root' || !$settings['db_password']) throw new RuntimeException('Production requires HTTPS and a dedicated database account.');
   if($settings['registration_open'] && (!extension_loaded('gd') || !function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng') || !function_exists('imagejpeg'))) throw new RuntimeException('Enable GD with JPEG and PNG support before opening registration.');
   if($settings['registration_open'] && (!$settings['privacy_approved'] || !$settings['privacy_contact'] || !$settings['retention_statement'] || !$settings['mail']['enabled'])) throw new RuntimeException('Complete the privacy and email configuration before opening registration.');
  }
 }
 return $settings;
}
ini_set('display_errors','0'); ini_set('log_errors','1');
$logDir=dirname(__DIR__).'/private/logs';
if(!is_dir($logDir)) mkdir($logDir,0700,true);
ini_set('error_log',$logDir.'/application.log');
set_exception_handler(function(Throwable $error) {
 $reference=bin2hex(random_bytes(6));
 // Avoid recording submitted values, passwords, OTPs or full exception messages.
 error_log('Failure '.$reference.' '.get_class($error).' '.basename($error->getFile()).':'.$error->getLine());
 if(PHP_SAPI==='cli') { fwrite(STDERR,"Operation failed. Check configuration and private log reference $reference.\n"); exit(1); }
 http_response_code(503); echo 'The service is temporarily unavailable. Please contact staff. Reference: '.htmlspecialchars($reference);
});
