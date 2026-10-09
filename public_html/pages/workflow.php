<?php
// Shared workflow rules; mutations are called inside the caller's transaction.
function workflow_ready() {
    try { database()->query('SELECT applicant_id FROM applicant_identity LIMIT 0'); database()->query('SELECT work_date FROM work_activities LIMIT 0'); return true; }
    catch (PDOException $e) { return false; }
}
function workflow_require() {
    if (!workflow_ready()) throw new RuntimeException('Open Admin dashboard and install the workflow upgrade first.');
}
function workflow_audit($db, $id, $action, $details) {
    $q=$db->prepare('INSERT INTO workflow_audit (applicant_id,action,details,staff) VALUES (?,?,?,?)');
    $q->execute([$id,$action,$details,$_SESSION['admin_user'] ?? 'Registration']);
}
function match_text($value) {
    $value=mb_strtolower(trim($value),'UTF-8');
    return trim(preg_replace('/[^\p{L}\p{N}]+/u',' ',$value));
}
function resident_candidates($application) {
    $db=database(); $q=$db->prepare('SELECT * FROM applicant_identity WHERE applicant_id=?'); $q->execute([$application['id']]); $identity=$q->fetch();
    $last=$identity['last_name'] ?? ''; $q=$db->prepare("SELECT * FROM resident_registry WHERE birth_date=? OR (?<>'' AND last_name=?) ORDER BY resident_id LIMIT 100");
    $q->execute([$application['birth_date'],$last,$last]); $rows=[];
    foreach ($q->fetchAll() as $r) {
        $reasons=[]; $score=0;
        if ($application['birth_date']===$r['birth_date']) { $score+=40; $reasons[]='Birth date matches'; }
        $full=implode(' ',array_filter([$r['first_name'],$r['middle_name'],$r['last_name']]));
        if (match_text($application['name'])===match_text($full)) { $score+=50; $reasons[]='Full name matches'; }
        elseif ($identity && match_text($identity['last_name'])===match_text($r['last_name']) && match_text($identity['first_name'])===match_text($r['first_name'])) { $score+=35; $reasons[]='First and last names match; check middle name'; }
        elseif ($last && match_text($last)===match_text($r['last_name'])) { $score+=10; $reasons[]='Last name matches'; }
        $details=json_decode($r['details'],true) ?: [];
        $street=trim(($details['house_number'] ?? '').' '.($details['street'] ?? ''));
        $address=implode(', ',array_filter([$details['address'] ?? $details['street_address'] ?? $street,$details['purok'] ?? '',$r['barangay'],$r['city'],$r['province']]));
        if(match_text($application['address'])===match_text($address)) { $score+=10; $reasons[]='Address text matches'; }
        $r['display_name']=$full; $r['display_address']=$address; $r['match_reasons']=$reasons; $r['rank']=$score;
        $rows[]=$r;
    }
    usort($rows,fn($a,$b)=>$b['rank']<=>$a['rank']);
    return array_slice($rows,0,10);
}
function duplicate_candidates($application) {
    // A flag is not a rejection. Existing applications from other periods can be legitimate.
    $q=database()->prepare('SELECT a.*,d.decision,d.reason FROM applicants a LEFT JOIN duplicate_reviews d ON d.applicant_id=? AND d.other_id=a.id WHERE a.id<>? AND a.program=? AND a.birth_date=? ORDER BY a.created_at DESC');
    $q->execute([$application['id'],$application['id'],$application['program'],$application['birth_date']]);
    return array_values(array_filter($q->fetchAll(),fn($r)=>match_text($r['name'])===match_text($application['name'])));
}
function allowed_statuses($program,$current) {
    $common=['Received'=>['Under review','Withdrawn'],'Under review'=>['Documents needed','Interview scheduled','Not approved','Withdrawn'],'Documents needed'=>['Under review','Withdrawn'],'Interview scheduled'=>['Under review','Documents needed','Not approved','Withdrawn']];
    $common['Interview scheduled'][]=$program==='TUPAD'?'Orientation scheduled':'Referred to DSWD';
    $common['Orientation scheduled']=['Community work scheduled','Under review','Withdrawn'];
    $common['Community work scheduled']=['Confirmed member','Under review','Withdrawn'];
    $common['Referred to DSWD']=['Confirmed member','Under review','Not approved','Withdrawn'];
    $common['Confirmed member']=['Under review'];
    $common['Not approved']=['Under review']; $common['Withdrawn']=['Under review'];
    return array_values(array_unique(array_merge([$current],$common[$current] ?? ['Under review'])));
}
function next_step_text($status) {
    return match($status) {
        'Received','Under review'=>'Staff are reviewing your application and residency records. Check this page for updates.',
        'Documents needed'=>'Read the document review comments below and upload the requested replacement or missing copies.',
        'Interview scheduled'=>'Read the latest staff instructions for your interview date, time, venue and required documents.',
        'Orientation scheduled'=>'Check the staff instructions for your orientation arrangements.',
        'Community work scheduled'=>'Check your assigned work dates, time and venue below.',
        'Referred to DSWD'=>'Your request has been referred. DSWD assessment and validation still apply.',
        'Confirmed member'=>'Staff have recorded your membership confirmation. Read the latest instructions.',
        'Not approved','Withdrawn'=>'Read the recorded reason below. Contact the help desk if you need clarification.',
        default=>'Read your latest application updates below.'
    };
}
function queue_notification($db,$id,$subject,$message) {
    $q=$db->prepare('INSERT INTO notification_log (applicant_id,subject,message) VALUES (?,?,?)'); $q->execute([$id,$subject,$message]);
    return (int)$db->lastInsertId();
}
function deliver_notification($id) {
    $db=database();
    // Atomic claim prevents two staff requests sending the same pending entry concurrently.
    $q=$db->prepare("UPDATE notification_log SET delivery='sending',attempts=attempts+1,attempted_at=NOW() WHERE id=? AND delivery IN ('pending','failed','not_configured')"); $q->execute([$id]);
    if (!$q->rowCount()) return;
    $q=$db->prepare('SELECT n.*,a.name,a.method,c.email FROM notification_log n JOIN applicants a ON a.id=n.applicant_id LEFT JOIN applicant_accounts c ON c.applicant_id=a.id WHERE n.id=?'); $q->execute([$id]); $n=$q->fetch();
    require_once __DIR__.'/mailer.php';
    $result=$n['method']!=='email'?'profile_only':(!filter_var($n['email'],FILTER_VALIDATE_EMAIL)?'no_email':send_application_update($n));
    $q=$db->prepare('UPDATE notification_log SET delivery=? WHERE id=?'); $q->execute([$result,$id]);
}
