<?php
function credential_letters($text) {
    $text = strtr(trim($text), ['ñ'=>'n','Ñ'=>'N']);
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    return strtolower(preg_replace('/[^a-zA-Z]/', '', $ascii === false ? $text : $ascii));
}
function default_credentials($first, $last, $birth, $program = 'TUPAD') {
    $surname = credential_letters($last);
    $initials = '';
    foreach (preg_split('/[\s-]+/u', trim($first), -1, PREG_SPLIT_NO_EMPTY) as $part) {
        $initials .= substr(credential_letters($part), 0, 1);
    }
    if ($surname === '' || $initials === '') throw new RuntimeException('Enter first name(s) and last name containing letters.');
    if (!preg_match('/^(\d{4})-\d{2}-\d{2}$/', $birth, $match)) throw new RuntimeException('Enter a valid birth date.');
    $prefix = strtoupper($program) === 'TUPAD' ? 'TUPAD-APPLICANT-' : '4PS-APPLICANT-';
    return ['username'=>$prefix.strtoupper(substr($initials,0,10).substr($surname,0,50)), 'password'=>$surname.$match[1]];
}
function create_default_account($db, $id, $first, $last, $birth, $email, $program) {
    $credentials = default_credentials($first,$last,$birth,$program);
    $password = $credentials['password'];
    if (strlen($password)>72) throw new RuntimeException('Surname is too long for a temporary password. Please contact staff.');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $db->prepare('INSERT INTO applicant_accounts (applicant_id,username,password_hash,email,must_change_password) VALUES (?,?,?,?,1)');
    for ($suffix=0; $suffix<10000; $suffix++) {
        $username = $credentials['username'].($suffix ? (string)($suffix+1) : '');
        try {
            $insert->execute([$id,$username,$hash,$email]);
            $db->prepare('INSERT INTO account_recovery_expiry (applicant_id,expires_at) VALUES (?,DATE_ADD(NOW(),INTERVAL 24 HOUR))')->execute([$id]);
            return ['username'=>$username,'password'=>$password];
        } catch (PDOException $error) {
            if (($error->errorInfo[1]??0)!==1062) throw $error;
            // Only retry a username collision, never a duplicate applicant account.
            $check=$db->prepare('SELECT 1 FROM applicant_accounts WHERE applicant_id=?');$check->execute([$id]);
            if ($check->fetchColumn()) throw $error;
        }
    }
    throw new RuntimeException('Could not allocate a username. Please contact staff.');
}
