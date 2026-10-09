<?php
require_once __DIR__.'/../vendor/phpmailer/src/Exception.php';
require_once __DIR__.'/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__.'/../vendor/phpmailer/src/SMTP.php';
require_once __DIR__.'/email-templates.php';
function registration_email($email, $name, $id, $username, $password, $replacement = false) {
 $config=require __DIR__.'/../mail-config.php';
 if (!$config['enabled']) return 'not_configured';
 try {
  $mail=new PHPMailer\PHPMailer\PHPMailer(true);
  $mail->isSMTP(); $mail->Host=$config['host']; $mail->Port=(int)$config['port'];
  $mail->SMTPAuth=true; $mail->Username=$config['username']; $mail->Password=$config['password'];
  $mail->SMTPSecure=$config['encryption']; $mail->Timeout=15; $mail->CharSet='UTF-8';
  $mail->setFrom($config['from_email'],$config['from_name']); $mail->addAddress($email,$name);
  $mail->Subject=($replacement ? 'Updated account login - ' : 'Application received - ').$id;
  $opening=$replacement ? 'Your account login details have been updated. Sign in to view your current application status.' : 'Your application has been received and is being processed. This is not confirmation of approval.';
  $mail->AltBody="Hello $name,\n\n$opening\n\nApplication ID: $id\nDefault username: $username\n".("Temporary password: $password\n\n")."Sign in: ".$config['profile_url']."\n\n".("This temporary password expires in 24 hours. You must change it before viewing your profile.")." Keep your login details private. You can view your status and any assigned work dates in your profile.\n\nTulong Bayan";
  $mail->isHTML(true);
  $mail->Body=registration_email_html($name,$id,$username,$password,$config['profile_url'],$replacement);
  $mail->send(); return 'sent';
 } catch (Throwable $error) { error_log('Registration email could not be sent.'); return 'failed'; }
}

function send_application_update($notification) {
 $config=require __DIR__.'/../mail-config.php';
 if (!$config['enabled']) return 'not_configured';
 try {
  $mail=new PHPMailer\PHPMailer\PHPMailer(true);
  $mail->isSMTP(); $mail->Host=$config['host']; $mail->Port=(int)$config['port'];
  $mail->SMTPAuth=true; $mail->Username=$config['username']; $mail->Password=$config['password'];
  $mail->SMTPSecure=$config['encryption']; $mail->Timeout=15; $mail->CharSet='UTF-8';
  $mail->setFrom($config['from_email'],$config['from_name']); $mail->addAddress($notification['email'],$notification['name']);
  $mail->Subject=$notification['subject'].' - Tulong Bayan';
  $mail->AltBody="Hello ".$notification['name'].",\n\nApplication ID: ".$notification['applicant_id']."\n\n".$notification['message']."\n\nSign in to My Profile: ".$config['profile_url'];
  $mail->isHTML(true);
  $mail->Body=email_layout($notification['subject'],'Your application has an update.','<p>Hello '.email_escape($notification['name']).',</p><p>Application ID: <strong>'.email_escape($notification['applicant_id']).'</strong></p><p>'.nl2br(email_escape($notification['message'])).'</p><p>Sign in to My Profile for your latest application details.</p>');
  $mail->send(); return 'sent';
 } catch(Throwable $e) { error_log('Application update email failed.'); return 'failed'; }
}
function send_verification_email($email, $code) {
 $config=require __DIR__.'/../mail-config.php';
 if (!$config['enabled']) return 'not_configured';
 try {
  $mail=new PHPMailer\PHPMailer\PHPMailer(true);
  $mail->isSMTP(); $mail->Host=$config['host']; $mail->Port=(int)$config['port'];
  $mail->SMTPAuth=true; $mail->Username=$config['username']; $mail->Password=$config['password'];
  $mail->SMTPSecure=$config['encryption']; $mail->Timeout=15; $mail->CharSet='UTF-8';
  $mail->setFrom($config['from_email'],$config['from_name']); $mail->addAddress($email);
  $mail->Subject='Verify your email - Tulong Bayan';
  $mail->AltBody="Your Tulong Bayan email verification code is: $code\n\nThis code expires in 10 minutes. Enter it in the verification form in the same browser where you requested it. Do not share this code.\n\nIf you did not request this code, you can ignore this email.";
  $mail->isHTML(true);
  $mail->Body=verification_email_html($code);
  $mail->send(); return 'sent';
 } catch (Throwable $error) { error_log('Verification email could not be sent.'); return 'failed'; }
}

