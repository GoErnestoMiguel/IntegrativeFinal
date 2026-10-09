<?php
// Codes are tied to one email and browser session; never store the plaintext code.
function check_email_otp($email, $code) {
    if(!security_allow('otp-check',$email,10,600)) return 'Too many verification attempts. Try again later.';
    $state = $_SESSION['email_otp'] ?? null;
    if (!$state || $state['email'] !== strtolower(trim($email))) return 'Request a verification code for this email address.';
    if ($state['expires'] < time()) return 'Your verification code has expired. Request a new code.';
    if ($state['attempts'] >= 5) return 'Too many incorrect codes. Request a new code.';
    if (!is_string($code) || !preg_match('/^[0-9]{6}$/', $code) || !password_verify($code, $state['hash'])) {
        $_SESSION['email_otp']['attempts']++;
        return 'The verification code is incorrect. Check your email and try again.';
    }
    return null;
}
