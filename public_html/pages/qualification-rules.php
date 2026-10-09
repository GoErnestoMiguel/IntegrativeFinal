<?php
// Edit question wording here. Rule decisions are kept separately below.
function qualification_questions() {
    $yn = ['yes'=>'Yes','no'=>'No','unsure'=>'Not sure'];
    return [
        'age'=>['What is your age group?', ['under18'=>'Under 18','adult'=>'18–59','senior'=>'60 or older'], 'TUPAD: staff verify age and appropriate work. Age alone does not determine household eligibility for 4Ps.'],
        'residency'=>['Do you live in Barangay San Sebastian, Lipa City?', $yn, 'This routes you to the right local office; living elsewhere does not rule out national programs.'],
        'work'=>['Which best describes your current work?', ['none'=>'No work','displaced'=>'Lost work or livelihood','informal'=>'Irregular, seasonal or informal work','underemployed'=>'Working but need more hours or income','regular'=>'Regular employment','unsure'=>'Not sure'], 'TUPAD: identify your actual work situation, including reduced work.'],
        'disruption'=>['Has a closure, disaster or other crisis reduced your work or livelihood?', $yn, 'TUPAD: staff may ask what happened and when. A No answer is not automatic rejection.'],
        'previous_tupad'=>['Have you already worked under TUPAD during this calendar year?', $yn, 'TUPAD: repeat participation and any applicable exceptions require DOLE verification.'],
        'household_tupad'=>['Is another person in your household participating in the same TUPAD project?', $yn, 'TUPAD: staff need to check household participation and duplication rules.'],
        'availability'=>['Can you attend the assigned work and orientation schedule?', ['yes'=>'Yes','support'=>'I need accommodation or help with the schedule','no'=>'Not at present','unsure'=>'I do not know the schedule yet'], 'Readiness only. Disability or a need for accommodation is not treated as automatic ineligibility.'],
        'already_4ps'=>['What is your household’s 4Ps status?', ['no'=>'Never enrolled','yes'=>'Currently enrolled','former'=>'Previously enrolled / exited','pending'=>'Waiting for validation or a decision','unsure'=>'Not sure'], '4Ps: existing beneficiaries should request assistance or updates rather than duplicate enrollment.'],
        'children'=>['Does your household have a member aged 0–18?', $yn, '4Ps: count household members at the time of assessment or registration.'],
        'pregnancy'=>['Is any household member currently pregnant?', ['yes'=>'Yes','no'=>'No','unsure'=>'Not sure / prefer to discuss privately'], '4Ps: a pregnant member can meet the composition criterion even without a child aged 0–18.'],
        'targeting'=>['Has DSWD identified your household for CBMS-based validation?', ['invited'=>'Yes, we received an invitation or were listed','surveyed'=>'We were surveyed but have no confirmation','no'=>'No invitation or listing known','unsure'=>'Not sure'], 'A CBMS survey alone is not approval. DSWD verifies official targeting and household classification.'],
        'income'=>['Does your household struggle to cover basic needs?', $yn, '4Ps: this is context, not an official poverty classification. No fixed income cutoff is applied here.'],
        'conditions'=>['Is your household willing to follow applicable 4Ps health, education and family-development requirements?', ['yes'=>'Yes','help'=>'We need an explanation or help complying','no'=>'No','unsure'=>'Not sure'], '4Ps: ask the City/Municipal Link to explain conditions and support available for barriers.'],
        'school'=>['Are household members of school age attending school?', ['yes'=>'Yes','barrier'=>'Some are not attending / need help','na'=>'No school-age members','unsure'=>'Not sure'], 'Preparation only. School barriers prompt help, not automatic rejection by this checklist.'],
        'other_aid'=>['Does your household receive other government financial assistance?', $yn, 'Disclose the program and dates to staff. Other assistance is not treated as an automatic exclusion.'],
        'documents'=>['Do you have identification and relevant household documents available?', ['ready'=>'Yes','partial'=>'Some documents are missing','help'=>'I need help identifying or obtaining documents'], 'Readiness only. Ask staff which alternatives are accepted; missing documents do not decide eligibility here.'],
    ];
}
function qualification_results($a) {
    $tn = []; $pn = []; $review = false;
    if ($a['age'] !== 'adult') { $review = true; $tn[] = 'Ask DOLE to verify the age requirements and suitable work for your age group before proceeding.'; }
    if (in_array($a['work'], ['regular','unsure'], true)) { $review = true; $tn[] = 'Your employment circumstances need review; regular employment alone does not establish membership in the disadvantaged-worker target group.'; }
    else $tn[] = 'Your reported work situation may fit TUPAD’s disadvantaged, displaced or underemployed worker target group.';
    if ($a['disruption'] === 'yes') $tn[] = 'Explain the event that reduced your work or livelihood and when it happened.';
    foreach (['previous_tupad'=>'Your previous TUPAD participation this year needs checking against repeat-participation rules and exceptions.','household_tupad'=>'Staff must check whether another household member’s participation affects this project.'] as $key=>$note) {
        if ($a[$key] !== 'no') { $review = true; $tn[] = $note; }
    }
    if ($a['availability'] !== 'yes') $tn[] = 'Before accepting work, confirm the dates and discuss scheduling or accessibility support with staff.';
    $tl = $review ? 'TUPAD: staff assessment needed' : 'TUPAD: possible target-group match';
    if ($a['already_4ps'] === 'yes') { $pl = '4Ps: existing beneficiary support'; $pn[] = 'Contact your City/Municipal Link for updates or concerns. Do not request duplicate household enrollment.'; }
    elseif ($a['already_4ps'] === 'former') { $pl = '4Ps: prior enrollment needs review'; $pn[] = 'DSWD must review your exit history and any applicable rules before considering further enrollment.'; }
    elseif ($a['children'] === 'no' && $a['pregnancy'] === 'no') { $pl = '4Ps: household criterion not indicated'; $pn[] = 'You reported neither a member aged 0–18 nor a pregnant member. Ask DSWD about other assistance or correct these answers if needed.'; }
    elseif ($a['conditions'] === 'no') { $pl = '4Ps: discuss program conditions first'; $pn[] = 'Willingness to comply with applicable program conditions is part of eligibility. Discuss these with DSWD before proceeding.'; }
    elseif ($a['already_4ps'] === 'pending') { $pl = '4Ps: follow up existing assessment'; $pn[] = 'Follow up your pending case with DSWD rather than submitting duplicate requests.'; }
    elseif ($a['already_4ps'] === 'unsure') { $pl = '4Ps: verify household enrollment'; $pn[] = 'Ask DSWD to check whether your household is already enrolled before making a new request.'; }
    elseif (($a['children'] === 'yes' || $a['pregnancy'] === 'yes') && $a['targeting'] === 'invited' && $a['conditions'] === 'yes') { $pl = '4Ps: proceed to official validation'; $pn[] = 'You report the household-composition indicator, willingness to comply and an invitation/listing. Attend the official validation; none of these is approval.'; }
    else { $pl = '4Ps: DSWD assessment needed'; $pn[] = 'Ask your City/Municipal Link to check household composition, enrollment status and official targeting before registration.'; }
    if ($a['targeting'] !== 'invited') $pn[] = 'An invitation or eligible listing has not been confirmed. DSWD must verify CBMS-based identification; a survey or financial difficulty alone does not establish eligibility.';
    else $pn[] = 'Bring the details of your invitation or listing so DSWD can verify them.';
    if ($a['income'] !== 'yes') $pn[] = 'DSWD determines household classification through official data and assessment, not your answer about financial difficulty alone.';
    if (in_array($a['conditions'], ['help','unsure'], true)) $pn[] = 'Request an explanation of health, education and Family Development Session requirements and help with any barriers.';
    if ($a['school'] === 'barrier') $pn[] = 'Tell DSWD about school-attendance barriers so staff can assess support needs.';
    foreach (['residency','other_aid','documents'] as $key) {
        $note = '';
        if ($key === 'residency' && $a[$key] !== 'yes') $note = 'Confirm which local office covers your residence. This is a routing issue, not a national eligibility exclusion.';
        if ($key === 'other_aid' && $a[$key] !== 'no') $note = 'List any other assistance and dates for staff to check applicable overlap rules.';
        if ($key === 'documents' && $a[$key] !== 'ready') $note = 'Ask the help desk about missing documents and accepted alternatives. Document readiness is separate from eligibility.';
        if ($note) { $tn[] = $note; $pn[] = $note; }
    }
    $tn[] = 'DOLE must verify current project rules, records and available slots. This checklist cannot approve an application.';
    $pn[] = 'DSWD confirms classification, eligibility and available registration through official validation. This checklist cannot enroll a household.';
    return [
        'TUPAD'=>[$tl,$tn,['Accepted identification and residence details','Employment/livelihood information and previous TUPAD participation dates, if applicable'],'tupad.php'],
        '4Ps'=>[$pl,$pn,['PhilSys ID or proof of registration; ask DSWD about accepted alternatives','Birth certificates and school documents, where applicable','Pregnancy/health documents, where applicable','Invitation or validation notice, if received'],'fourps.php'],
    ];
}
