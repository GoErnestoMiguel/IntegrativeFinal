<?php
// Applicant IDs remain permanent; an admin-assigned TUPAD ID is an additional lookup ID.
function profile_record($lookup) {
    $query = database()->prepare('SELECT a.id FROM applicants a LEFT JOIN registrant_profiles p ON p.applicant_id = a.id WHERE a.id = ? OR p.worker_id = ? LIMIT 1');
    $query->execute([$lookup, $lookup]);
    $id = $query->fetchColumn();
    if (!$id) return null;
    $record = find_applicant($id);
    $query = database()->prepare('SELECT * FROM registrant_profiles WHERE applicant_id = ?');
    $query->execute([$id]);
    $record['profile'] = $query->fetch() ?: [];
    $query = database()->prepare('SELECT work_date FROM work_roster WHERE applicant_id=? ORDER BY work_date');
    $query->execute([$id]);
    $record['work_dates'] = $query->fetchAll(PDO::FETCH_COLUMN);
    return $record;
}
function profile_date_valid($value) {
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}
