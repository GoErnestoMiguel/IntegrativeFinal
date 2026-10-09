<?php
// Local intake questions, not an agency-issued application template.
function intake_fields($program, $mode) {
    $fields = $program === 'TUPAD' ? [
        'work_situation' => ['Current work situation', ['No work', 'Seasonal or irregular work', 'Self-employed', 'Employed', 'Prefer to discuss with staff']],
        'occupation' => ['Usual job or livelihood', null],
        'availability' => ['What days and times are you usually available for an interview? (optional)', null],
    ] : [
        'household_size' => ['How many people live in your household?', 'number'],
        'children_count' => ['How many household members are aged 0–18?', 'number'],
        'pregnant_member' => ['Is there a pregnant household member?', ['Yes', 'No', 'Prefer to discuss with staff']],
        'existing_4ps' => ['Is your household currently in 4Ps?', ['Yes', 'No', 'Not sure']],
    ];
    if ($mode === 'full') {
        $fields += [
            'civil_status' => ['Civil status', ['Single', 'Married', 'Widowed', 'Separated', 'Other', 'Prefer not to say']],
            'income' => ['Approximate monthly household income in pesos (optional)', 'number'],
            'document_readiness' => ['Do you have documents ready for staff to check?', ['Ready to show at the help desk', 'Some are missing', 'I need help identifying documents']],
            'assistance' => ['Accessibility or application assistance needed (optional)', null],
        ];
        $fields += $program === 'TUPAD' ? [
            'skills' => ['Work skills or experience (optional)', null],
            'previous_tupad' => ['Previous TUPAD participation', ['Never', 'Previously participated', 'Not sure']],
            'previous_details' => ['Previous participation date and location, if known (optional)', null],
        ] : [
            'school_information' => ['School attendance or school-related concerns (optional)', null],
            'livelihood' => ['Main household source of income', null],
            'dswd_contact' => ['Has a DSWD worker contacted your household?', ['Yes', 'No', 'Not sure']],
        ];
    }
    return $fields;
}
