<?php
function request_secure() {
 $s=hosting_settings();
 return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ||
  ($s['trusted_proxy']!=='' && ($_SERVER['REMOTE_ADDR'] ?? '')===$s['trusted_proxy'] && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')==='https');
}
function throttle_take($scope,$value,$limit,$seconds) {
 $db=database(); $bucket=(int)floor(time()/$seconds); $key=hash_hmac('sha256',$scope.'|'.mb_strtolower(trim($value)),hosting_settings()['app_key']);
 $q=$db->prepare('INSERT INTO security_limits (bucket_key,bucket_number,hits,expires_at) VALUES (?,?,1,?) ON DUPLICATE KEY UPDATE hits=hits+1');
 $q->execute([$key,$bucket,($bucket+2)*$seconds]);
 $q=$db->prepare('SELECT hits FROM security_limits WHERE bucket_key=? AND bucket_number=?'); $q->execute([$key,$bucket]);
 return (int)$q->fetchColumn()<=$limit;
}
function security_allow($scope,$identity,$limit=8,$seconds=900) {
 // Both buckets are counted; new sessions do not reset these database limits.
 $ip=throttle_take($scope.'-ip',$_SERVER['REMOTE_ADDR'] ?? 'cli',max(30,$limit*4),$seconds);
 $account=throttle_take($scope.'-identity',$identity,$limit,$seconds);
 return $ip && $account;
}
