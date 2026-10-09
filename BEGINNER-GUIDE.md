# Your first Tulong Bayan website setup

**Use this guide with `tulong-bayan-hostinger-images.zip`.** Keep your current local project as a backup. This guide prepares a separate online installation; it does not move your existing applicants automatically.

You do not need to finish everything in one sitting. Follow the steps in order. Each step includes a check so you know when to continue. The exact buttons depend on your hosting company.

## The plan in plain language

**Choose compatible hosting → upload the project → create a database → enter settings → set up email and staff access → test → open registration.**

| Term | What it means |
|---|---|
| Domain | Your website address, such as `your-domain.example`. Replace this example with your real address. |
| Hosting | The service that keeps your website running online. |
| Control panel | The hosting company's dashboard for files, databases, email and settings. |
| Database | Where applications, accounts, resident records and schedules are stored. |
| Document root | The one folder the hosting company makes visible to website visitors. |
| HTTPS / SSL | The secure connection shown by `https://` in the website address. |
| SMTP | The service your website uses to send email. |
| Terminal | A box for running maintenance commands. Hosting support can help with these. |
| Cron job | A task the host runs automatically on a schedule. |

## 1. Check the hosting plan before paying

**You do:** contact the hosting company's support team. Send them the message below. You selected Hostinger Premium. Confirm that your account supports private folders and PHP GD image processing. Staff will manually review the uploaded documents.

> Hello! I am a student hosting a PHP/MySQL application for the first time. Before I buy a plan, can you confirm that you support these requirements?
>
> - A supported PHP version with PDO MySQL, mbstring, iconv, fileinfo, OpenSSL and sessions; PHP zip for backups.
> - MySQL or MariaDB, HTTPS, authenticated SMTP and scheduled PHP tasks.
> - The domain public_html folder with sibling config/, tools/, database/ and private/ folders that are NOT publicly accessible and can be accessed by PHP within the account boundaries.
> - Private writable upload/session/log folders outside the document root.
> - PHP GD with JPEG/PNG decoding and JPEG encoding, with sufficient memory for document photos. EXIF is recommended for phone-photo orientation. This image-only edition does not require ClamAV or proc_open.
> - Help checking the public_html/private-folder layout, running a PHP staff-account setup command with protected environment variables, and setting up backups.
>
> Please tell me which plan supports these features and any extra charges. I can provide the project's HOSTING-GUIDE.md for your technical team.

**Continue when:** support confirms the actual plan meets these requirements. If they cannot support GD image processing or the private-folder layout, resolve that before purchase. Do not turn off those protections just to make an incompatible plan work.

Ask the host to select a currently supported PHP release and test compatibility. The official [PHP support page](https://www.php.net/supported-versions.php) lists supported versions. The old local PHP patch version is not a version you need to reproduce online.

## 2. Collect the details you will need

Save these in a password manager or another private place. Do not send passwords or app keys in this chat or include them in screenshots.

| Detail | Who provides it? |
|---|---|
| Hosting control-panel login | Hosting company |
| Website address | Your chosen domain |
| Project folder path and PHP command path | Hosting support |
| Database host, name, username and password | Database setup in the control panel |
| SMTP host, port, encryption, login and sender address | Your email provider |
| GD JPEG/PNG support and PHP upload limits | Hostinger support / PHP Configuration |
| Privacy contact and retention wording | The responsible barangay office |

You do not need to install XAMPP on the hosting server. XAMPP is for your local computer.

**Hostinger note:** use its custom PHP/HTML website hosting option, not Website Builder. Its fixed public_html layout is documented [here](https://www.hostinger.com/support/1583494-what-is-the-path-to-your-website-s-root-home-directory-and-how-to-change-it-in-hostinger/). References to cPanel below are optional background; your account uses hPanel.

## 3. Connect your domain and enable HTTPS

**You do:** add your domain using your hosting company's instructions. If your domain was purchased elsewhere, follow the host's instructions for its DNS records or nameservers. DNS changes may take time to appear.

**Ask support:** “Please enable HTTPS for my domain and confirm it works before I install the application.”

If your panel is cPanel, domain settings are generally managed in its Domains interface, but what you can change depends on the account. In particular, document-root restrictions may require support assistance. See [cPanel's domain documentation](https://docs.cpanel.net/cpanel/domains/domains/manage-the-domain/).

**Continue when:** opening `https://YOUR-REAL-DOMAIN` does not show a certificate warning. A temporary hosting page is fine at this stage. Do not bypass a browser security warning.

## 4. Upload the separate hosting project

1. Download and extract `tulong-bayan-hostinger-images.zip` on your computer.
2. Open the extracted folder. You should see `public_html`, `config`, `database`, `tools` and `private`.
3. Use the host's File Manager or secure file-transfer service to upload the project to the private location agreed with support.
4. Put the contents of the ZIP's public_html folder inside your domain's existing public_html. Put config, database, tools and private alongside that folder, not inside it. Confirm access to that parent location with support. Hostinger uses a fixed public_html document root; you do not need to change it.

An example layout is:

```
/home/YOUR-ACCOUNT/domains/YOUR-DOMAIN/
    public_html/ ← visitors can access this folder
    config/     ← private
    database/   ← private
    tools/      ← private
    private/    ← private
```

The path above is an example, not a command. Your host will give you the real one. Keep these folders beside one another: the PHP files depend on that arrangement.

**Do not put the whole project inside an exposed public_html folder.** Use the domain's existing public_html, and keep the sibling directories private. If your account cannot access the parent location, ask support before changing paths.

**Continue when:** support confirms that the document root is the domain's `public_html`, the private folders cannot be downloaded, and the PHP account can write to `private/`. Do not solve permission errors by making everything writable to everyone.

## 5. Create an empty database

1. In your control panel, find its database creation tool. In cPanel, this may be called Database Wizard or MySQL Database Wizard. [cPanel's database documentation](https://docs.cpanel.net/cpanel/databases/) explains its database and user setup tools.
2. Create a **new** database for this hosting copy.
3. Create a database user with a strong password. This is different from the website admin account.
4. Connect that user to the new database. Ask support to provide the privileges needed to install its tables, then restrict the website's runtime account to SELECT, INSERT, UPDATE and DELETE afterward.
5. Note the full database and user names. The panel may add your hosting account name as a prefix.
6. Open phpMyAdmin and select this new, empty database.
7. Import `database/database.sql` from the hosting package.
8. Import `database/hosting-migration.sql` into the **same** database.

You can use these two imports instead of the command-line migration tool. Do not import your old preview database just to get a default admin account; this edition intentionally has none.

**Continue when:** phpMyAdmin shows tables including `applicants`, `admins`, `security_limits` and `staff_security`. If an import reports an error, resolve it before proceeding.

## 6. Fill in the private website settings

The main file to edit is **`config/settings.php`**. This replaces the local preview's separate email-configuration workflow.

Make a private backup of the file first. Open it in the host's editor. The existing file can read environment variables, but for your first setup it is also possible to replace a setting's **entire line** with a direct value.

For example, replace the entire `db_name` line with:

```php
'db_name' => 'YOUR_FULL_DATABASE_NAME',
```

Keep the quotation marks and comma. Replace the words inside the quotes with your actual value. Do not leave the example words in the finished file. A quote or backslash in a value requires correct PHP escaping; use the hosting provider's protected environment-variable settings if you are unsure.

Use this checklist:

| Setting | Enter |
|---|---|
| `environment` | `'production'` |
| `site_url` | Your real HTTPS address, without a trailing slash |
| `db_host` | Host provided by support; do not assume it is `localhost` |
| `db_port` | Provider's database port, commonly `'3306'` |
| `db_name` | Full name of your NEW database |
| `db_user` | Dedicated database user, not `root` |
| `db_password` | That database user's password |
| `registration_open` | `false` for now, without quotes |
| `maintenance` | `false`, without quotes |
| `privacy_approved` | `false` until the responsible office approves the notice |

For a boolean setting, replace the whole line, for example:

```php
'registration_open' => false,
```

Leave `document_dir` at its supplied default if the private folder layout is correct. Leave `trusted_proxy` blank unless support tells you an exact trusted proxy IP is needed.

**Generate the app key:** in the hosting terminal, go to the project folder using its actual path, then run:

```sh
php tools/key.php
```

Copy the generated random text into the `app_key` setting. Keep it private. If the host uses a different PHP command, use the command support provides. You can ask support to run this step for you.

**Continue when:** visiting the website shows its home page. Selecting Apply should show **Registration is not open**. That is the expected result. A generic service error means settings still need attention; give support the displayed reference number so they can inspect the private log.

## 7. Configure email before creating staff access

Inside the same settings file, find the `mail` section. Replace each relevant line with the values from your mail provider:

| Mail setting | What to enter |
|---|---|
| `enabled` | `true`, without quotes |
| `host` | Provider's SMTP server |
| `port` | Provider's port number, without quotes |
| `encryption` | Provider's value, usually `'tls'` or `'ssl'` |
| `username` | SMTP login |
| `password` | SMTP password or app password |
| `from_email` | Sender address the provider authorizes you to use |
| `from_name` | Keep `'Tulong Bayan'` |

Ask the provider to help configure the sender-domain records called SPF, DKIM and DMARC. These help receiving mail systems verify your sender. Do not disable certificate checking if email fails.

**Continue when:** the email settings are saved and support confirms outbound SMTP is allowed. You will test real delivery during staff sign-in next.

## 8. Create your own admin account

This edition does **not** use the old default administrator login. Staff access requires a password followed by an emailed verification code.

**Ask support to help with this step:**

> Please run `php tools/staff.php create` from my private project folder. The tool reads `TB_STAFF_USERNAME`, `TB_STAFF_EMAIL` and `TB_STAFF_PASSWORD` from protected environment variables. Please help me enter these securely, use a unique password of 15–72 characters, and clear the password variable afterward. Do not put the password in a public file or command history.

Use an email account you can access. Give each staff member their own account.

Now test:

1. Open your website and select **My Profile**.
2. Find **Admin login**.
3. Enter your new staff username and password.
4. Check that staff email inbox and spam folder.
5. Enter the six-digit verification code.

**Continue when:** the admin dashboard opens. If the code never arrives, fix email delivery now. A password alone cannot bypass this step.

## 9. Prepare image uploads and the privacy notice

**Hosting support handles:** confirming GD JPEG/PNG support in the website PHP runtime, private-folder access, and upload limits. Recommended PHP limits: upload_max_filesize at least 5M, post_max_size at least 24M, max_file_uploads at least 8 (20 recommended). More memory may be needed for high-resolution photos; the application can reject photos that exceed its memory budget.

**The responsible barangay office handles:** approving the privacy notice, who to contact about personal information, and how long records should be kept.

After their approval, fill in `privacy_contact` and `retention_statement`, and set `privacy_approved` to `true`. These should be actual approved details, not invented sample wording.

**Continue when:** JPG/PNG uploads are recreated successfully and PDFs are rejected and the Privacy notice page displays the approved contact and retention information. Production registration remains unavailable until its required settings are complete.

## 10. Turn on registration for a controlled test

Arrange a restricted test period with hosting support before advertising the website. Use fictional people, harmless sample documents and email accounts you control. Do not upload actual household records during a trial.

Change the entire setting line to:

```php
'registration_open' => true,
```

Then perform this walkthrough:

| Test | What should happen |
|---|---|
| Submit a TUPAD application | OTP arrives; the form saves and issues an application ID and username |
| Sign in as that applicant | The issued temporary password works within 24 hours and requires a password change before profile access |
| Read the confirmation email | It contains the application ID, program-prefixed username and temporary password |
| Submit a 4Ps application | Its program questions and submission work |
| Upload harmless JPG/PNG document photos | Staff can review it; the applicant can see the review comments |
| Review residency | Staff can compare a fictional resident record and record a decision |
| Update status | The applicant sees the update; email delivery is recorded when Email was selected |
| Add a worker to a date | The worker's profile shows the activity details |
| Record attendance | The date's roster is checked; an unscheduled exception requires a reason |
| Use a phone and keyboard | Text, navigation, tables, forms and buttons remain usable |

Ask support to help test HTTPS, private-file protection, multi-photo uploads and rejection of unsupported images. Use the technical guide's full launch checklist for these checks.

**Continue when:** both programs work from beginning to end. If a core step fails, set `registration_open` back to `false` while fixing it.

## 11. Set up automatic emails and backups

**Automatic email queue:** ask support to add a scheduled job that runs `tools/notifications.php` every five minutes with the same settings as the website. Use the full PHP and project paths they provide. Check the Notifications page for delivery failures.

**Backups:** ask support to schedule encrypted backups of both the database and private documents, stored away from the website server. A database backup alone does not contain the uploaded files.

Ask support to restore a backup into a separate test copy and confirm that applicants, documents and schedules can be recovered. The project also includes an optional maintenance-mode backup tool, explained in HOSTING-GUIDE.md; its ZIP output is sensitive and is not encrypted automatically.

**Continue when:** the scheduled task works and you have a verified recovery procedure, not just a promise that backups exist.

## 12. Open the real service

Before publishing the link:

- Confirm the responsible office has approved use of the system and its privacy procedures.
- Separate demonstration records from the real database. Have the maintainer handle this deliberately; do not delete unfamiliar tables yourself.
- Confirm HTTPS, real email delivery, image processing and private storage work.
- Confirm staff have individual accounts and know how to review applications.
- Confirm phone/desktop tests and a backup restoration succeeded.
- Keep a named person responsible for hosting, updates, backups and applicant support.

Then open registration and share the website address. Keep the local project and a known-good release ZIP for reference.

## If something goes wrong

| What you see | First thing to check |
|---|---|
| Registration is not open | Expected until `registration_open` is deliberately set to `true` |
| Service temporarily unavailable | Missing app key, incorrect database settings or incomplete production requirements; ask support to check the reference in the private log |
| Cannot connect to database | Full database name, assigned user, password, host and port |
| No admin verification email | SMTP settings, allowed sender, provider restrictions and spam folder |
| Page repeatedly redirects | HTTPS/reverse-proxy setup; ask support for the exact trusted proxy configuration |
| Document upload rejected | JPG/PNG type, 5 MB per-photo size, 8-photo/20 MB batch limits, dimensions, GD support and private-folder permissions |
| Admin dashboard asks for an upgrade | Confirm both hosting SQL files were imported into the configured database |
| Permission denied | Ask support to grant the PHP service account appropriate access; do not use blanket public write permissions |
| Old default login does not work | Expected: create an individual staff account with the private staff tool |

**Your first action:** in Hostinger hPanel, confirm PHP GD support and private-folder access for your actual domain. This ZIP contains a public_html layout, but account permissions and live email still need testing. Staff manually review each uploaded photo as Accepted, Needs replacement or Under review. Select multiple photos for several pages, up to 40 per application. PDFs and HEIC are not accepted; ask applicants for clear JPG/PNG copies. Existing documents are not automatically converted or deleted.
