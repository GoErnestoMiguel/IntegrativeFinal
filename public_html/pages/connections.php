<?php
require_once __DIR__.'/../../config/bootstrap.php';
// Hosted database settings. Change these if your MySQL login is different.
// Environment overrides are optional, and mainly useful for testing.
function database() {
    static $connection;
    if ($connection) return $connection;

    $settings=hosting_settings();
    $host=$settings['db_host']; $port=$settings['db_port']; $name=$settings['db_name']; $user=$settings['db_user']; $password=$settings['db_password'];

    $connection = new PDO(
        "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
    return $connection;
}

function find_applicant($id) {
    $query = database()->prepare('SELECT * FROM applicants WHERE id = ?');
    $query->execute([$id]);
    $applicant = $query->fetch();
    if ($applicant) {
        $query = database()->prepare('SELECT status, message, created_at FROM application_history WHERE applicant_id = ? ORDER BY id');
        $query->execute([$id]);
        $applicant['history'] = $query->fetchAll();
        $table = $applicant['program'] === 'TUPAD' ? 'tupad_applicants' : 'fourps_applicants';
        $query = database()->prepare("SELECT intake_details FROM $table WHERE applicant_id = ?");
        $query->execute([$id]);
        $applicant['details'] = json_decode($query->fetchColumn() ?: '{}', true);
    }
    return $applicant;
}

function save_applicant($form, $answers, $submission_token, $details = [], $documents = []) {
    $db = database();
    // A refreshed/retried POST returns the same ID instead of inserting twice.
    $query = $db->prepare('SELECT id FROM applicants WHERE submission_token = ?');
    $query->execute([$submission_token]);
    $existing = $query->fetchColumn();
    if ($existing) return $existing;

    $id = ($form['program'] === 'TUPAD' ? 'TUP' : '4PS') . '-' . strtoupper(bin2hex(random_bytes(5)));
    $created_documents = [];
    $db->beginTransaction();
    try {
        $query = $db->prepare('INSERT INTO applicants (id, name, birth_date, address, program, method, contact, qualification_answers, submission_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $query->execute([$id, $form['name'], $form['birth_date'], $form['address'], $form['program'], $form['method'], $form['method'] === 'none' ? '' : $form['contact'], json_encode($answers), $submission_token]);
        $query = $db->prepare('INSERT INTO application_history (applicant_id, status, message) VALUES (?, ?, ?)');
        $query->execute([$id, 'Received', 'Your application was received. Await staff review. This is not program approval.']);
        $query = $db->prepare('INSERT INTO application_details (applicant_id, details) VALUES (?, ?)');
        $query->execute([$id, json_encode($details, JSON_THROW_ON_ERROR)]);
        $table = $form['program'] === 'TUPAD' ? 'tupad_applicants' : 'fourps_applicants';
        $query = $db->prepare("INSERT INTO $table (applicant_id,intake_details) VALUES (?,?)");
        $query->execute([$id,json_encode($details,JSON_THROW_ON_ERROR)]);
        require_once __DIR__.'/workflow.php';
        workflow_require();
        $query=$db->prepare('INSERT INTO applicant_identity (applicant_id,first_name,middle_name,last_name) VALUES (?,?,?,?)');
        $query->execute([$id,$form['first_name'],$form['middle_name'],$form['last_name']]);
        workflow_audit($db,$id,'Application received','Structured name and application saved for review.');
        require_once __DIR__.'/default-credentials.php';
        $credentials = create_default_account($db,$id,$form['first_name'],$form['last_name'],$form['birth_date'],$form['email'],$form['program']);
        $username = $credentials['username'];
        $password = $credentials['password'];
        require_once __DIR__.'/documents.php';
        store_documents($db,$id,$documents,$created_documents);
        $db->commit();
    } catch (Throwable $error) {
        $db->rollBack();
        foreach ($created_documents as $path) @unlink($path);
        throw $error;
    }
    // Delivery happens after commit: an SMTP failure must not lose the application.
    require_once __DIR__.'/mailer.php';
    $mail_status = registration_email($form['email'],$form['name'],$id,$username,$password);
    $_SESSION['registration_credentials'] = ['id'=>$id,'username'=>$username,'password'=>$password,'mail_status'=>$mail_status];
    try {
        $query=$db->prepare('UPDATE applicant_accounts SET mail_status=? WHERE applicant_id=?');
        $query->execute([$mail_status,$id]);
    } catch (PDOException $error) { error_log('Could not update email delivery status.'); }
    return $id;

}

function database_error() {
    return 'The service could not save your request. Please try again later or contact the help desk. Your form has not been cleared.';
}


