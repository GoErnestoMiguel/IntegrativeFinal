<?php
// QUESTION SUBTITLES — EDIT THIS SECTION
// Change the text on the right. Keep the field names on the left unchanged.
// An empty string means no subtitle. Applies to both Full and Simple forms.
// Use plain text, not HTML. Escape an apostrophe with a backslash: \'
$question_subtitles = [
    'name' => 'Enter your name as it appears on your identification.',
    'birth_date' => '',
    'address' => 'Include your house or street, barangay, city or municipality, and province.',
    'method' => '',
    'contact' => '',
    'consent' => '',
    'work_situation' => '',
    'occupation' => '',
    'availability' => 'For example: weekday mornings or Monday and Wednesday afternoons. You can leave this blank if you are unsure. This is only your preference, not a booking. If you are invited for an interview, staff will confirm the date and time.',
    'household_size' => 'Include yourself in the total.',
    'children_count' => '',
    'pregnant_member' => '',
    'existing_4ps' => '',
    'civil_status' => '',
    'income' => '',
    'document_readiness' => '',
    'assistance' => '',
    'skills' => '',
    'previous_tupad' => '',
    'previous_details' => '',
    'school_information' => '',
    'livelihood' => '',
    'dswd_contact' => '',
 ];

// Display helpers — no editing needed below this line.
function question_hint($key) {
    global $question_subtitles;
    $text = $question_subtitles[$key] ?? '';
    if ($text !== '') echo '<small class="question-subtitle" id="hint-' . e($key) . '">' . e($text) . '</small>';
}
function question_hint_attribute($key) {
    global $question_subtitles;
    if (($question_subtitles[$key] ?? '') !== '') echo ' aria-describedby="hint-' . e($key) . '"';
}
