# Tulong Bayan — separate hosting preparation edition

This is a separate project. Do not replace your working local preview with it. It has not been deployed or certified for production. Registration is closed by default, and no live database, uploaded documents, email credentials or default administrator is included.

## Folder layout

```
tulong-bayan-hosting/
  public_html/       ← the ONLY web document root
    pages/
    javascript/
    style/
    images/
    vendor/
  config/       ← private settings and bootstrap
  database/     ← private schema and migrations
  tools/        ← command-line maintenance tools
  private/      ← private uploads, sessions, logs and backups
```

Your host must let you point the domain at `public_html/` while keeping the other directories outside it. Do not upload the whole project into a publicly served `public_html` folder. For Apache, the supplied `.htaccess` rules are defense in depth; they are not a substitute for the correct document root. For Nginx or a host that ignores `.htaccess`, ask the administrator to configure equivalent access restrictions and disable directory listings. Only PHP files should be executed by the PHP handler; block backup, SQL, log and configuration files from HTTP access.

## Hosting capabilities to confirm before purchase

- A currently supported PHP release compatible with this application; the local checks used PHP 8.2.12, which is not a recommendation to deploy that exact patch version.
- MySQL/MariaDB, PDO MySQL, mbstring, iconv, fileinfo, OpenSSL and sessions; PHP zip is needed for the optional backup tool.
- HTTPS certificates, a private writable storage directory, scheduled command-line PHP jobs, and authenticated SMTP.
- PHP GD with JPEG and PNG decoding and JPEG encoding support. EXIF support is recommended to preserve phone-photo orientation. No ClamAV executable, external scanning service or proc_open is required by this image-only workflow.
- Restricted off-server backup storage, monitoring, and a tested recovery procedure.

## 1. Configure a separate database and private settings

Create a new database, such as `tulong_bayan_hosting`. Do not point this copy at your current preview database. Configure `config/settings.php` or the listed environment variables. Keep the file readable only by the PHP service account and administrator.

| Setting | Purpose |
|---|---|
| TB_ENV | `local` for isolated preparation, then `production` |
| TB_SITE_URL | Full site address without a trailing slash; production must use HTTPS |
| TB_APP_KEY | Generate a unique random key with `php tools/key.php`; keep private |
| TB_DB_HOST / TB_DB_PORT / TB_DB_NAME | Separate hosting database connection |
| TB_DB_USER / TB_DB_PASSWORD | Dedicated application database account; production rejects root or a blank password |
| TB_DOCUMENT_DIR | Absolute private upload directory, outside `public_html/` |
| TB_TRUSTED_PROXY | Exact trusted proxy IP, only if your host terminates HTTPS at that proxy |
| TB_REGISTRATION_OPEN | Leave `0` during preparation; set `1` only after launch checks |
| TB_MAINTENANCE | `1` pauses all HTTP access for maintenance; CLI backup remains available |

Run `php tools/migrate.php` with a database migration account that can create tables. Then configure the website account with only the runtime permissions it needs (SELECT, INSERT, UPDATE and DELETE on this database), without global privileges or routine schema-modification privileges. Create a fresh schema by running the migration twice to confirm repeatability. No shared/default staff account is created.

The production environment requires a unique application key, HTTPS URL and a dedicated password-protected database account. Keep database access private to the application host. Do not expose phpMyAdmin publicly without separate access protection.

## 2. Configure email and individual staff accounts

Set `TB_MAIL_ENABLED=1`, `TB_SMTP_HOST`, `TB_SMTP_PORT`, `TB_SMTP_ENCRYPTION` (`tls` or `ssl`), `TB_SMTP_USER`, `TB_SMTP_PASSWORD`, and `TB_FROM_EMAIL`. Use the mail provider's prescribed settings and configure SPF, DKIM and DMARC for your authorized sending domain. Do not disable certificate verification to make SMTP work.

Set protected environment values `TB_STAFF_USERNAME`, `TB_STAFF_EMAIL` and `TB_STAFF_PASSWORD`, then run `php tools/staff.php create`. Use an individual account for each staff member and a unique password of 15–72 characters. Clear the password environment variable after use. The CLI tool reads these values without printing them.

Staff sign-in requires the account password and a six-digit code delivered to that staff member's email. Codes expire after ten minutes and are limited to five attempts per challenge, with database-backed rate limits. Email verification is an additional factor but is not phishing-resistant; authenticator/passkey support can be considered later. There is no email-disabled bypass. Test SMTP before handing staff their accounts.

To reset staff credentials, set their username/email/new password and run `php tools/staff.php rotate`. To revoke an account, set its username and run `php tools/staff.php disable`. Disabled or rotated accounts lose existing staff sessions at the next authorization check. This edition retains the existing common staff permission level; it does not introduce separate departmental roles.

New applicants receive PROGRAM-APPLICANT- plus first-name initials and surname as their username, and normalized lowercase surname plus birth year as a temporary password. The receipt and email contain these details. The password expires after 24 hours and must be changed to a unique 15–72 character password before profile access. These defaults are predictable; change them immediately. Existing accounts are unchanged. A staff-issued recovery password is random, expires after 24 hours, and must be changed before profile access. Recovery still requires staff identity verification; self-service forgotten-password links are not included.

## 3. Protect uploads and publish the reviewed privacy notice

Accept JPG and PNG only: up to 5 MB per image, 8 images and 20 MB per submission, and 40 images per application. Photos are limited to 8 megapixels and 6,000 pixels per side, with an additional available-memory check. Set upload_max_filesize to at least 5M, post_max_size to at least 24M, and max_file_uploads to at least 8 (20 recommended). Test the actual web PHP runtime. The server decodes each photo and creates a new JPEG, stripping source metadata and trailing content and flattening transparency onto white. The original upload is never copied to permanent storage; PHP removes its temporary upload at request end. Failed submissions clean up newly written files. This reduces risk but is not antivirus scanning or proof of document authenticity. Keep PHP/GD patched and storage monitored. Staff manually verify the content.

The privacy page is a draft until the responsible barangay office reviews it. Set `TB_PRIVACY_CONTACT` and `TB_RETENTION_STATEMENT` to the approved contact and policy, then `TB_PRIVACY_APPROVED=1`. Confirm the actual purposes, recipients, legal basis, retention periods, correction/request procedure, service-provider arrangements and incident contacts with that office. This technical draft does not establish legal compliance.

Production registration cannot open without the approved privacy settings, an enabled mail configuration and GD image support. These checks do not prove that the services work: complete real integration tests. The application does not automatically delete resident/application records based on an invented retention period. Agree and implement the approved disposal process, including backups, before real use.

## 4. Schedule notification delivery

Configure the host's scheduler to run `php /absolute/path/tulong-bayan-hosting/tools/notifications.php` every five minutes using the same private configuration as the website. Each run processes up to ten pending/failed notifications, retries no sooner than fifteen minutes, and stops automatic retry after five attempts. It uses a process lock and skips maintenance mode. Entries left in `sending` after an interruption require staff review in Notifications to avoid duplicate emails. Correct `not_configured` entries and retry them from the staff interface.

Alert staff to repeated delivery failures. Mail-server acceptance does not guarantee inbox placement or that an applicant has read a message. Do not put sensitive document contents into notification text.

## 5. Back up and rehearse restoration

Use your hosting provider's consistent, encrypted database and private-file backups where available. The optional `php tools/backup.php` makes a ZIP under `private/backups` containing a SQL snapshot and referenced document files. It requires maintenance mode. Wait for active requests to finish and stop other writers/scheduled jobs before running it. It refuses to complete if a referenced document is missing.

The generated archive is sensitive and is NOT encrypted by the tool. Encrypt it using the host's approved backup service, transfer it to restricted off-server storage, and enforce the approved retention policy. Never move it into `public_html/` or include it in a downloadable source ZIP. Protect configuration secrets separately.

Restore rehearsal: create a new isolated database; extract the archive privately; import `database.sql` into that empty database; restore `documents/` into a new private storage folder; point an isolated copy at them; verify record counts, staff/applicant access, document downloads, schedules and attendance. Do not rehearse by overwriting the live database. Record the recovery time and responsible person.

## 6. Existing data and launch gates

Prefer a clean production database. Keep fictional residents, applicants and events in a separate demonstration environment. The optional sample files are outside `public_html/` and are not imported automatically.

If approved real records must be migrated, copy them into the NEW hosting database and preserve matching private documents. Back up first. After import, run `php tools/invalidate-legacy-passwords.php --confirm` on that database to invalidate the old predictable applicant passwords; staff must issue new credentials through the verified recovery process. Rotate any required existing staff accounts with the private staff tool. Do not give old shared demo credentials live access.

Before setting `TB_REGISTRATION_OPEN=1`, verify:

- HTTPS and session behavior on the actual domain, including any reverse proxy.
- No public access to config, private files, migrations, tests or backups.
- Production errors show a reference, not database or server details; private logs are rotated and access-restricted.
- Real OTP, staff verification and status emails arrive using a test address you control.
- Applicant A cannot access applicant B's documents or account; disabled staff sessions stop working.
- Both programs complete registration and review; duplicates, residency review, schedules and attendance behave correctly.
- Desktop/mobile layouts, keyboard navigation, upload limits and rejection of unsupported/damaged images work on the host.
- A backup has actually been restored in isolation and validated.
- The privacy notice, retention procedure, staff responsibilities and incident contacts are approved.

The separate edition was checked locally for syntax and selected database-backed security behavior. Live mail delivery, host-specific image processing, browser rendering, hosting configuration and backup restoration are not certified by those checks. Do not treat the ZIP as a completed deployment.
