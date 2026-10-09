<?php
require 'config.php';
require_once 'mailer.php';
header('Content-Type: application/json');
function otp_reply($status, $message) { http_response_code($status); echo json_encode(['message'=>$message]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') otp_reply(405, 'Use the Get OTP button on the registration form.');
if (!is_string($_POST['form_token'] ?? null) || !valid_form()) otp_reply(403, 'Refresh the form and try again.');
$email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
if (strlen($email)>254 || !filter_var($email,FILTER_VALIDATE_EMAIL)) otp_reply(422, 'Enter a valid email address first.');
try { if(!security_allow('email-otp',$email,5,3600)) otp_reply(429,'Too many requests. Please try again later.'); } catch(PDOException $e) { otp_reply(503,'Verification is temporarily unavailable.'); }
$now=time();
$requests=array_values(array_filter($_SESSION['otp_requests'] ?? [], fn($t)=>$t>$now-3600));
if ($requests && end($requests)>$now-60) otp_reply(429, 'Please wait 60 seconds before requesting another code.');
if (count($requests)>=5) otp_reply(429, 'You have reached the request limit. Please try again in one hour.');
$requests[]=$now; $_SESSION['otp_requests']=$requests;
$code=(string)random_int(100000,999999);
$result=send_verification_email($email,$code);
if ($result!=='sent') otp_reply(503, $result==='not_configured' ? 'Email sending is not configured yet. Please contact the help desk.' : 'The code could not be emailed. Check the address or try again later.');
$_SESSION['email_otp']=['email'=>$email,'hash'=>password_hash($code,PASSWORD_DEFAULT),'expires'=>$now+600,'attempts'=>0];
otp_reply(200, 'Code sent. Check your inbox or spam folder. It expires in 10 minutes. Only the latest code works.');
