<?php
require_once __DIR__ . '/config.php';
function admin_signed_in() {
    if(empty($_SESSION['admin_db_login']) || empty($_SESSION['admin_user']) || ($_SESSION['admin_expires'] ?? 0)<=time()) return false;
    try {
      $q=database()->prepare('SELECT s.auth_version FROM admins a JOIN staff_security s ON s.username=a.username WHERE a.username=? AND a.active=1'); $q->execute([$_SESSION['admin_user']]);$version=$q->fetchColumn();
      return $version!==false && (int)$version===(int)($_SESSION['admin_version'] ?? -1);
    } catch(PDOException $e) { return false; }
}
function require_admin() {
    if (!admin_signed_in()) {
        unset($_SESSION['admin_user'], $_SESSION['admin_expires']);
        header('Location: profile.php?admin=required#admin-login'); exit;
    }
    $_SESSION['admin_expires'] = time() + 1800;
}
