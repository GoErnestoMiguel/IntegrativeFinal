<?php
// This folder MUST remain outside the web server document root.
// Use environment variables or fill these values on your server; never commit secrets.
return [
 'environment'=>getenv('TB_ENV') ?: 'local',
 'site_url'=>getenv('TB_SITE_URL') ?: 'http://localhost:8095',
 'app_key'=>getenv('TB_APP_KEY') ?: '', // Generate: php tools/key.php
 'db_host'=>getenv('TB_DB_HOST') ?: '127.0.0.1',
 'db_port'=>getenv('TB_DB_PORT') ?: '3306',
 'db_name'=>getenv('TB_DB_NAME') ?: 'tulong_bayan_hosting',
 'db_user'=>getenv('TB_DB_USER') ?: '',
 'db_password'=>getenv('TB_DB_PASSWORD') ?: '',
 'document_dir'=>getenv('TB_DOCUMENT_DIR') ?: dirname(__DIR__).'/private/documents',
 'trusted_proxy'=>getenv('TB_TRUSTED_PROXY') ?: '', // Exact reverse-proxy IP, if required.
 'registration_open'=>getenv('TB_REGISTRATION_OPEN') === '1',
 'maintenance'=>getenv('TB_MAINTENANCE') === '1',
 'privacy_contact'=>getenv('TB_PRIVACY_CONTACT') ?: '',
 'retention_statement'=>getenv('TB_RETENTION_STATEMENT') ?: '',
 'privacy_approved'=>getenv('TB_PRIVACY_APPROVED') === '1',
 'mail'=>[
  'enabled'=>getenv('TB_MAIL_ENABLED') === '1',
  'host'=>getenv('TB_SMTP_HOST') ?: '', 'port'=>(int)(getenv('TB_SMTP_PORT') ?: 587),
  'encryption'=>getenv('TB_SMTP_ENCRYPTION') ?: 'tls',
  'username'=>getenv('TB_SMTP_USER') ?: '', 'password'=>getenv('TB_SMTP_PASSWORD') ?: '',
  'from_email'=>getenv('TB_FROM_EMAIL') ?: '', 'from_name'=>'Tulong Bayan'
 ]
];
