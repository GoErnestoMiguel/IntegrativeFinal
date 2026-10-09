# Tulong Bayan User Manual

**Version:** October 10, 2026 — hosting edition with restricted photo uploads  
**Project:** TUPAD and 4Ps community assistance portal, Barangay San Sebastian, Lipa City  
**Audience:** Applicants, barangay staff, system administrators, and project demonstrators

This manual describes the separate hosting edition. The original local preview is unchanged. Existing accounts retain their current usernames and passwords; the new naming rule applies to new registrations. It covers the implemented interface and identifies features that remain manual or are not yet available. The forms are locally designed intake forms, not official agency-issued application templates. Submission is not program approval.

## Contents

1. System overview and roles
2. Installation and upgrades
3. Email and document-storage setup
4. Public pages and initial check
5. Applicant registration
6. Login, passwords, and My Profile
7. Document submission
8. Admin login and navigation
9. Applicant review and status updates
10. Applicant accounts and password resets
11. Resident registry and CSV import
12. Using resident records during review
13. TUPAD identification and work schedules
14. Daily attendance
15. Calendar events and announcements
16. Program members
17. Daily operations, backups, and recovery
18. Troubleshooting
19. Suggested consultation demonstration
20. Current limitations and file reference

## 1. System overview and roles

Tulong Bayan collects applications, verifies access to an email address, stores submitted documents, and gives applicants a place to view their status. Staff manage applications, resident records, work dates, attendance, and public notices.

| Role | Available activities |
|---|---|
| Visitor | Read program descriptions, complete the initial check, view published notices and calendar events, begin registration |
| Applicant | Register, verify email, sign in, change credentials, view profile/status/work dates, submit and download their documents |
| Administrator | Review applicants and documents, update status, manage accounts, import residents, assign TUPAD IDs, manage schedules/attendance, publish content |

Administrators share the same staff access level in this version; separate reviewer, attendance-officer, and publishing permissions are not implemented.

### Important identifiers

| Identifier | Purpose |
|---|---|
| Applicant/reference ID | Permanent application identifier, such as `TUP-A1B2C3D4E5` or `4PS-A1B2C3D4E5` |
| Username | Applicant login name; separate from the reference number |
| Assigned TUPAD ID | Optional additional ID assigned by staff, beginning with `TUPAD-` |
| Resident ID | Identifies a row in the resident registry; does not create an applicant account |
| Household ID | Groups resident records from the same household |

Old records may retain earlier ID and username formats. Do not rename database IDs manually: schedules, documents, accounts, and history refer to them.

## 2. Installation and upgrades

Use the included **Beginner-Guide.html** for the step-by-step Hostinger setup and **HOSTING-GUIDE.md** for configuration details. A project ZIP contains source and schema, not a copy of your running database. Keep registration closed until the installation checks pass.

### 2.1 Fresh installation

1. Back up any existing website before uploading files.
2. Place the contents of the package's `public_html` in the domain's existing public_html directory. Keep `config`, `database`, `tools`, and `private` beside it, outside the public directory. Do not expose the whole package on the web.
3. Create a separate database and database user. Configure the private `config/settings.php`, including the actual HTTPS website address and a unique application key.
4. Follow the beginner guide to run the private migration tool and create an individual staff account. No default admin account is supplied.
5. Configure SMTP, private storage, and scheduled notification processing. Verify these using the website's PHP runtime.
6. Complete the launch checklist with fictional records and an email address you control before opening registration. Obtain barangay approval for the privacy contact and retention policy.

### 2.2 Updating an existing installation

Back up the matching database, private photos, and settings before replacing files. Preserve your live configuration and application key. Use the supplied migration instructions; do not overwrite your database with an empty schema. This credential/photo update adds no new database migration beyond the hosting edition's existing migrations. Existing usernames, passwords, and uploaded documents are not automatically rewritten. Do not point this hosting copy at the original preview database.

## 3. Email and document-storage setup

### 3.1 Email

The administrator configures authenticated SMTP in private `config/settings.php` or protected environment variables. Use the provider's approved hostname, port, encryption, sender address, username and password. The public `mail-config.php` reads the private settings; do not put secrets in shared ZIPs. Set the profile link to your actual HTTPS address.

Test both applicant and staff verification emails. Applicant email verification is required before submission. A later confirmation-email failure does not undo a saved application: save the credentials shown once on the receipt and contact staff if needed. Mail-server acceptance does not prove delivery or that the recipient read it.

### 3.2 Private photo storage

Use a private writable directory outside public_html. Enable PHP Fileinfo and GD with JPEG/PNG reading and JPEG writing. EXIF is recommended for phone-photo orientation. Configure PHP to allow at least 5 MB per file, at least 8 simultaneous uploads and a request size greater than the 20 MB photo batch limit (for example 32M). The website still enforces its own smaller limits.

Only JPG/JPEG and PNG are accepted. The system checks the content and recreates a fresh JPEG from decoded pixels; original metadata is not retained. No ClamAV installation or external scanning service is required. This reduces upload risks but does not prove a document is genuine. Staff must review readability, identity, residency and applicable requirements. Back up private photos together with the database.

## 4. Public pages and initial check

| Page | Use |
|---|---|
| Home | Find program introductions and registration/help links |
| TUPAD | Read its overview, process, and document guidance |
| 4Ps | Read household-assessment guidance and document information |
| Initial check | Answer screening questions for preliminary guidance |
| Apply | Choose TUPAD or 4Ps and open the registration form |
| Calendar | View published events and dates |
| Announcements | Read published notices |
| Help & FAQ | Read help and common questions |
| My profile | Applicant login and a separate admin login section |

The initial check is separate from registration. Its result is guidance only: it neither verifies residence nor guarantees selection. Answer truthfully and discuss uncertain answers with staff.

The active website is English-only. Translation files are preserved in the `translations` folder but are not loaded by the pages.

## 5. Applicant registration

### 5.1 Choose the program and form mode

1. Select **Apply**, choose a program, and continue. Alternatively, select **Start registration** on the TUPAD or 4Ps page to open that program directly.
2. The selected program defaults to the full form.
3. Use **Simple mode** for guided steps, or **Full Form** to return to the full layout.
4. Choose the mode before typing: switching modes starts a fresh form. A leave-page warning helps prevent accidental loss. Validation errors preserve typed answers; uploaded files must be selected again. Separate browser tabs use independent submission tokens, valid for two hours.

### 5.2 Complete the four sections

**1. Tell us about yourself**

Enter first name(s), optional middle name, last name, birth date, and complete address. Put all given names in first name(s), for example `Juan Carlo`; put the whole surname in last name, for example `Dela Cruz`.

The date picker supports month selection and an editable year. Enter the actual birth date; future dates are not accepted.

**2. Your work situation / Your household**

TUPAD questions concern employment and availability; 4Ps questions concern household circumstances. The full form requests additional details. Optional interview availability is a preference, not a confirmed appointment.

**3. Documents and updates**

1. Enter the account email at the top of this section.
2. Select **Get OTP**.
3. Check that mailbox and enter the six-digit code in the adjacent field.
4. Upload applicable documents.
5. Select the preferred update method.

Choosing **Email** automatically uses the account email. Changing that email updates the contact field and requires a new OTP. Choosing **Text message** permits a mobile number, but SMS delivery is not implemented. The help-desk contact option does not remove the requirement for an account email and OTP.

**4. Review and send**

Review the displayed answers and filenames. Confirm the accuracy checkbox, then select **Save and get reference number**. If there are errors, correct them and reselect any uploaded files before resubmitting. Browser file fields do not retain files after the server returns a validation error.

### 5.3 OTP rules

- Use the code in the same browser session that requested it.
- It expires after 10 minutes.
- Wait 60 seconds between code requests.
- The session permits five requests per hour and five incorrect attempts per code.
- Requesting a new successfully sent code replaces the previous code.
- OTP checks access to an email address, not identity or barangay residence.

### 5.4 Save the receipt

After successful registration, record the applicant ID, username, and temporary password. The system also attempts to email them. Avoid relying on reopening the receipt: credentials are displayed once.

New usernames contain the program identifier, the first letter of each first name, and the surname. Middle names do not contribute initials. Spaces and punctuation are removed, letters are normalized where supported, and the username is uppercase. Very long name components are shortened. The temporary password uses the normalized lowercase surname followed by the four-digit birth year.

| Program and example applicant | Username | Temporary password |
|---|---|---|
| TUPAD: Juan Carlo Santos, born 1990 | `TUPAD-APPLICANT-JCSANTOS` | `santos1990` |
| 4Ps: Maria Ana Dela Cruz, born 2000 | `4PS-APPLICANT-MADELACRUZ` | `delacruz2000` |

If that username is taken, a number is appended, starting with 2. Always copy the username actually issued. The temporary password expires after 24 hours and must be changed before profile access. If it expires, ask staff to verify your identity and issue a replacement. Recovery passwords are random and also expire after 24 hours. Existing accounts are unchanged.

**Keep credentials private.** Surname/birth-year passwords are predictable. Expiry and mandatory changes reduce exposure but do not make this convention secure against someone who knows those details. Change it immediately to a unique passphrase; do not reuse the temporary password elsewhere.

If a matching name, birth date and program already exist, the form warns you before saving another application. Check your current profile first. If a separate request is necessary, select the confirmation and explain why, including the application period if different. Staff review the possible duplicate; no record is automatically deleted or rejected.

## 6. Login, passwords, and My Profile

1. Open **My profile**.
2. Enter your username and password. An applicant ID alone no longer grants access.
3. On first login, complete the required password change before viewing your profile.
4. Choose a new password of 15–72 characters, different from the current password, and enter the same value in the confirmation field.
5. To change credentials later, use the account settings and provide the current password. Usernames accept 3–80 letters, numbers, dots, underscores, or hyphens.

The profile shows application details, current status, the applicant ID, any assigned TUPAD ID, submitted documents, work dates, and status messages. The displayed identification is a website record, not a government-issued card.

Applicant sessions expire after inactivity. Staff password resets invalidate previous sessions. If login fails, verify the exact issued username and ask staff for assistance if necessary. Do not register again solely to recover a password.

## 7. Document submission

### 7.1 Program upload categories

**TUPAD**

- Accepted valid identification.
- Proof of residence, if requested.
- Beneficiary information or profiling form.
- Proof or declaration of employment circumstances, as requested.

**4Ps**

- PhilSys ID or proof of registration; confirm current acceptance with DSWD.
- Birth certificates of household members.
- School records, when applicable.
- Health or pregnancy records, when applicable.
- Marriage, death, or disability records, when applicable.

Uploads are optional at initial submission in the current software. Staff determine what applies. A blank optional upload does not mean the applicant is exempt from a document later requested by the responsible office.

### 7.2 Upload later through My Profile

1. Sign in and complete any required password change.
2. Find **Your documents**.
3. Select a file under the appropriate program category.
4. Select **Submit documents**.
5. Check for the success message and the newly listed file.

JPG/JPEG and PNG photos only: up to 5 MB each, 8 photos and 20 MB per submission, and 40 files per application. Each category accepts multiple photos. Photograph each page separately, including all corners, and check legibility. Images must be no more than 6,000 pixels on either side and 8 million pixels in total. Resize large camera images before submitting. PDFs, HEIC and GIF are rejected. The stored copy is a recreated JPEG; check its orientation after upload. Previously stored documents are retained.

Staff and the owning applicant can download files. Public visitors and other applicants should not have access. Uploading does not mark residency or eligibility as verified. The implementation checks file type and size but does not include antivirus scanning or a document authenticity checker. Self-service replacement/deletion is not provided.

## 8. Admin login and navigation

Open **My profile**, scroll to **Admin login**, and use the credentials supplied by the project administrator. Enter the emailed six-digit staff verification code after your password. The code expires after ten minutes. After signing in, select **Open admin panel**. The username/password are intentionally not repeated in this manual; keep staff access details separate from user documentation.

The navigation includes Dashboard, Applicants, Residents, Accounts, Members, Work schedule, Attendance, and Calendar & announcements. The active page is highlighted. Use **Log out** when finished, especially on a shared computer.

## 9. Applicant review and status updates

1. Open **Applicants**.
2. Use the applicant selector and its search field, or the applicant-table search.
3. Select the correct name and ID, then open details.
4. Review identity information, program answers, uploaded documents, and history.
5. Download files under **Submitted documents** as needed.
6. Choose a new status and write clear instructions, including dates, time, location, and missing documents when relevant.
7. Select **Save status** and check the updated history.

| Shared statuses | Program-specific statuses |
|---|---|
| Received, Under review, Documents needed, Interview scheduled, Confirmed member | TUPAD: Orientation scheduled, Community work scheduled. 4Ps: Referred to DSWD |

Status changes are saved to the profile and generate a notification record. If the applicant selected Email and SMTP is configured, an email is attempted. Open **Notifications** to see sent, pending, failed, not configured, profile only or no email results. “Sent” means accepted by the mail server, not read by the applicant. Retry a failed delivery after correcting SMTP settings. Bulk work-activity changes are queued. Configure the private notification tool to run every five minutes as described in the hosting guide. Staff can also inspect and retry delivery through Notifications; do not assume that saving an event sends mail immediately. For an interrupted “sending” entry, check whether the applicant received the message before retrying after ten minutes.

The status dropdown offers the current status and permitted next stages. Normal TUPAD progression is Received → Under review → Interview scheduled → Orientation scheduled → Community work scheduled → Confirmed member. Normal 4Ps progression is Received → Under review → Interview scheduled → Referred to DSWD → Confirmed member. Documents needed, Not approved and Withdrawn are available at appropriate review stages. Reopen a decision through Under review. Advancement after interview requires Verified residency and resolved duplicate flags. Staff must include meaningful instructions, including the date, time and venue for appointments. A confirmation still depends on the responsible agency; the system does not determine government eligibility.

The present admin interface reviews submitted answers and updates status; it is not a general editor for every applicant identity field. Do not imply an applicant's original name/address was corrected merely by writing a history message.

## 10. Applicant accounts and password resets

1. Open **Accounts**.
2. Enter the applicant ID and verified email address.
3. Verify the person using the barangay's actual identity-check procedure.
4. Tick the identity/email verification confirmation only after completing that check.
5. Select **Issue new temporary password**.
6. Check the resulting account details and email-delivery message.

For an existing account, the username is retained and the temporary password is regenerated. Previous sessions become invalid, and the applicant must change the password again. Staff-issued account creation/reset does not use the surname-and-birth-year registration convention; newly issued accounts for existing applicants may use a `user-...` username.

If email fails, provide the displayed one-time credentials privately through the established help-desk process. Do not reset repeatedly merely to test mail delivery.

## 11. Resident registry and CSV import

### 11.1 Prepare the registry

Start MySQL, open **Residents**, and use **Initialize resident registry** if the table is not available. Alternatively, import `resident-migration.sql` through phpMyAdmin. This creates a separate resident table; it does not populate program applications.

### 11.2 Prepare a CSV

The importer accepts **CSV UTF-8**, not `.xlsx` directly. In Excel, save the relevant sheet as CSV UTF-8. Preserve these required headers:

```text
resident_id,household_id,first_name,last_name,birth_date,barangay,city,province
```

| Field | Format or rule |
|---|---|
| resident_id, household_id | Up to 64 letters, digits, hyphens, or underscores |
| birth_date | YYYY-MM-DD; valid date, not future or more than 120 years ago |
| barangay | San Sebastian |
| city | Lipa City |
| province | Batangas |
| is_sample, if supplied | 1 for fictional samples, 0 for non-sample imports |

Optional sample columns include middle name, sex, marital status, household role, house number, street, purok, work situation, occupation, income, household size, and school status. The importer preserves optional details but does not treat a supplied verification label as proof: its stored verification status is reset to **Not verified**.

Maximum file size is 2 MB; maximum rows per import is 1,000. The sample CSV includes 100 fictional residents across 25 households. Their ages were calculated as of October 8, 2026; use birth dates for later age calculations.

### 11.3 Preview and confirm

1. Expand **Import resident records**.
2. Choose the CSV and select **Preview import**.
3. Check total rows, new IDs, and existing IDs to skip.
4. Scroll vertically through all rows and horizontally through all fields. Use **First row** and **Last row** if needed.
5. Resolve any reported validation error in the source CSV, then preview again.
6. Select **Confirm import** to save, or **Cancel** to discard the preview.
7. Check the result message, search for a resident, and open **View** to inspect details.

The preview expires after 30 minutes. Existing resident IDs are skipped, not updated. Duplicate IDs inside the same CSV cause validation failure. Records with different IDs but similar names are not automatically merged. Importing does not create applicant accounts, verify residence, or enroll people in either program.

### 11.4 Correcting records

There is no resident edit/delete interface in this release. Re-importing the same ID will not correct it. Ask the system maintainer to handle a correction with a backup and an appropriate database update; do not invent a new resident ID simply to bypass the duplicate check.

## 12. Using resident records during review

1. Open **Applicants**, choose the application, then **Review residency, duplicates and documents**.
2. Inspect suggested resident records. Reasons identify matching names, birth dates and exact address text. Suggestions are ranked, not expressed as a probability of identity. A shared birth date alone is weak evidence.
3. Compare the applicant and resident details side by side. Open the full resident record if needed. Older applications without separate names can still match by birth date and full name.
4. Select **Use this resident ID**, or search Residents manually and enter the correct ID.
5. Choose Verified, Needs clarification or Not verified. Verified requires a linked record. Enter the evidence checked and reason, then save. The applicant sees the decision and explanation in My Profile.
6. Resolve possible duplicates. Compare application dates and periods. Choose Separate legitimate application or Duplicate application, and record a reason. This does not delete or merge applications.
7. Review each submitted document and select Accepted, Needs replacement or Under review. Explain precisely what needs to be supplied. The applicant sees these comments and can upload another copy under the same category. Each new copy starts Under review; previous files and decisions remain available.
8. Review history records the staff account, timestamp and decision. Fictional resident records are labeled; never use a sample record as real residency evidence.

Registry presence alone does not prove that the person submitting the application is the resident in that record. A missing record is not automatically proof of non-residency. Follow the barangay's actual identity and residency procedure.

The dashboard provides review queues for pending applications, unreviewed documents, unresolved residency checks and email delivery problems. Select a card to open the relevant work list; upcoming worker lists link directly to their dates.

## 13. TUPAD identification and work schedules

### 13.1 Assign an additional TUPAD ID

1. In Applicants, open the person and select **Manage profile, TUPAD ID and work schedule**.
2. Select the person using the searchable dropdown if needed.
3. Enter a unique ID beginning with `TUPAD-`, followed by letters, digits, or hyphens, such as `TUPAD-2026-0001`.
4. Select **Save TUPAD ID**.

The original applicant ID remains usable. The assigned ID must not duplicate another worker ID or another person's applicant ID. 4Ps applicants do not receive TUPAD work assignments through this page.

### 13.2 Build a worker roster

1. Set the applicant status appropriately in Applicants.
2. Open **Work schedule**.
3. Choose the date and select **Open date**.
4. Save the activity time, venue or meeting point, and instructions. Then search the worker dropdown by name, applicant ID, or assigned TUPAD ID.
5. Select the worker and click **Add to schedule**.
6. Repeat for other workers. Confirm that the roster shows the correct names and date.

The dropdown includes TUPAD records with **Orientation scheduled**, **Community work scheduled**, or **Confirmed member** status. A worker can appear on several dates but cannot be added twice to the same date. Removing a roster row does not remove attendance history. Use **Print worker list** when a printed roster is needed.

Each work date has one shared time, venue and set of instructions for its assigned workers. These appear in each assigned worker’s profile. Saving changes queues notifications for the roster. Publishing a public calendar event remains separate and does not assign workers.

## 14. Daily attendance

1. Open **Attendance**.
2. Select the work date and open that date's attendance list. Only today or earlier dates are accepted.
3. Confirm that the person is expected on the date's work roster.
4. Enter the worker's TUPAD ID or applicant ID and submit the attendance form.
5. Check the confirmation and recorded list before entering the next worker.

TUPAD workers at Orientation scheduled, Community work scheduled or Confirmed member stage can be recorded when assigned to the selected date. A duplicate entry for the same person/date is prevented.

The system checks the date’s roster. An unscheduled worker is blocked unless staff select the exception option and enter a reason of 10–500 characters. The exception is recorded in the review audit history. The page lists scheduled workers as Present or Not yet recorded. Scheduling never marks attendance automatically. Recorded time is the data-entry time, not a verified arrival time. Attendance correction, time-in/time-out and payroll remain outside this version.

## 15. Calendar events and announcements

1. Open **Calendar & announcements**.
2. Choose event or announcement, then the audience: All, TUPAD, or 4Ps.
3. Enter a title and description.
4. For an event, enter a valid date, time, and venue.
5. Choose whether to publish, then save.
6. Check the public page to ensure the information appears correctly.

Draft/unpublished content is not shown publicly. Edit existing content from its management list. Deletion requires its confirmation checkbox and removes the item; unpublish instead if you want to retain it.

Use descriptive titles and state who should attend. A public interview notice should not imply everyone has been invited. Demo notices and dates should retain their **Sample** labels.

## 16. Program members

Only use **Confirmed member** after the responsible office has actually confirmed the person's membership. This adds the program member record while retaining the application. Later application-status changes do not automatically erase that membership record. The Members page is a view of staff-confirmed entries, not a connection to a live DOLE or DSWD database.

## 17. Daily operations, backups, and recovery

### Start of day

- Start Apache and MySQL and confirm the homepage loads.
- Sign in to the appropriate staff account.
- Review newly received applications and missing documents.
- Check upcoming notices and the day's work roster.
- Confirm email configuration if OTP requests are failing.

### End of day

- Check that attendance was recorded against the correct date.
- Save public content changes and review any urgent applicant instructions.
- Log out of the staff account.
- Back up the database and private documents according to the project's operating procedure.

### What to back up

1. Export your configured hosting database in phpMyAdmin as SQL.
2. Copy the private document directory.
3. Preserve the project source and configuration securely.

Restore the matching database and document files together. A record in `applicant_documents` points to a private stored file; restoring only one side leaves missing or inaccessible documents. Test restoration in a separate copy before relying on a backup.

Do not include real resident records, downloaded IDs, or SMTP passwords in a publicly shared project ZIP.

## 18. Troubleshooting

| Problem | Action |
|---|---|
| Website cannot open | Check your domain, HTTPS, hosting service and public_html placement. Ask the administrator to inspect private error logs. |
| Database error | Start MySQL, verify the connection settings, and import the required schema/migrations. |
| Admin cannot sign in after fresh setup | Ask the administrator to provision an individual staff account with the private staff tool; there is no default admin. |
| OTP unavailable/not configured | Configure SMTP in private config/settings.php or protected environment settings for the running copy. |
| OTP does not arrive | Check inbox/spam, recipient spelling, SMTP credentials, connectivity, and cooldown. |
| OTP expired or wrong | Request a new code after the cooldown and enter the latest code in the same browser. |
| Registration email fails after saving | Keep the displayed receipt credentials; the application is already saved. Ask staff for recovery if necessary. |
| Cannot open profile with applicant ID | Use the issued username and password. ID-only login was removed. |
| Must change password | Complete the first-login change with a new 15–72-character password. |
| Worker missing from scheduler | Check program and status; only the listed scheduling statuses are included. |
| Attendance rejects a worker | Check the ID, TUPAD program, scheduling status and selected date’s roster. An unscheduled exception requires an authorization reason. |
| Document fails | Use a genuine JPG/PNG within 5 MB, check PHP request limits and private-folder write permissions, and reselect the file. |
| Document link unavailable | Check login/ownership, the document table, and whether the private file was restored with the database. |
| Resident import fails | Use CSV UTF-8 with exact required headers and valid dates, IDs, locality, and size. Fix the reported row. |
| Import adds zero records | The resident IDs may already exist; existing IDs are intentionally skipped. |
| Preview controls or styles look old | Press Ctrl + F5 and verify you updated the copy being served. |
| Changes are not visible | Check whether you edited the workspace preview or a separate XAMPP copy. They are different folders. |

## 19. Suggested consultation demonstration

Use fictional records and an email account you control.

1. Show Home, program guidance, and the independent initial check.
2. Sign in as staff; import the 100-resident sample CSV, review all rows, and confirm.
3. Show name/household search and a resident detail view. Show the suggested matches and explain why staff still verify the evidence.
4. Open registration, choose a program, and show full/simple modes before entering data.
5. Enter a fictional name/address, receive an OTP, and upload a non-sensitive sample JPG or PNG.
6. Submit, save the receipt, sign in with the issued credentials, and change the temporary password.
7. Show applicant profile details and the uploaded file.
8. As staff, link the sample resident, save a residency decision, resolve duplicate flags if present, and review the document. Progress through the allowed statuses and inspect Notifications.
9. For a TUPAD test applicant, set a scheduling status, assign an ID, and add a future work date.
10. Demonstrate attendance only on today's or an earlier sample date, after checking the roster.
11. Publish a sample calendar event and announcement and confirm their public visibility.

Do not present a successful sample import, status update, or OTP as proof of real government eligibility or real identity verification.

## 20. Current limitations and file reference

Staff make residency, document and program decisions. The system does not directly integrate with DOLE/DSWD, issue official government IDs, deliver SMS, process payroll, or automatically verify document authenticity. The hosting edition includes private storage, staff email verification, notification processing and maintenance tools, but still needs actual-host configuration and launch testing. No deployment or real SMTP delivery is certified by this manual. Test mobile/desktop layouts, real email delivery, access restrictions and backup restoration on the final host before using real records.

File paths below are relative to public_html unless a private root is stated.

| File or folder | Purpose |
|---|---|
| `pages/register.php` | Program registration, email/OTP placement and document fields |
| `pages/default-credentials.php` | New-registration username/password generation |
| `pages/application-fields.php` | Program questions |
| `pages/question-subtitles.php` | Question helper text |
| `pages/documents.php` | Upload categories, checks, storage and listing |
| `pages/admin-residents.php` | Registry, preview, import confirmation and search |
| `pages/resident-import.php` | CSV parsing and validation |
| `pages/admin-review.php`, `pages/workflow.php` | Matching, residency/document review, duplicate resolution and workflow rules |
| `pages/admin-notifications.php` | Email delivery log and retry controls |
| `pages/admin-queue.php` | Paginated dashboard review queues |
| `pages/admin-nav.php` | Staff navigation |
| `pages/admin-login-section.php` | Admin sign-in card and signed-in buttons |
| `pages/connections.php` | Database connection and application persistence |
| `../config/settings.php` | Private application and SMTP settings |
| `pages/email-templates.php` | Styled email content |
| `style/style.css`, `style/modern.css`, `style/refined.css` | Base and subsequent design layers |
| `translations/` | Preserved inactive translations |
| `../database/database.sql` | Database structure for setup |
| `../database/` migrations | Additive upgrades for existing installations |
| `sample-data/` | Available fictional demonstration records |

Keep a copy of this manual with the project. Update it whenever validation rules, account conventions, document categories, or staff workflows change.
