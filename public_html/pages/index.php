<?php
$page_title = 'Home';
require 'header.php';
?>
<section class="hero">
    <div class="container hero-grid">
        <div>
            <p class="eyebrow">SAN SEBASTIAN · COMMUNITY SUPPORT</p>
            <h1>Find the right help.<br><span>Take the next step.</span></h1>
            <p>Learn about TUPAD and 4Ps, prepare your documents, and complete a short registration form.</p>
            <div class="actions">
                <a href="register.php" class="button yellow">Start registration →</a>
                <a href="help.php" class="button outline-light">I need help applying</a>
            </div>
            <p class="hero-footnote">A little guidance at every step. <a href="checker.php">Check which program may suit you →</a></p>
        </div>
        <aside class="card hero-card">
            <p class="eyebrow">YOUR APPLICATION JOURNEY</p>
            <h2>Three simple steps</h2>
            <ol class="steps">
                <li><strong>Choose your program</strong><span>Read what each program offers.</span></li>
                <li><strong>Complete the form</strong><span>Enter your details and preferred contact.</span></li>
                <li><strong>Keep your application ID</strong><span>Save your login details to view updates in My profile.</span></li>
            </ol>
            <div class="journey-footer"><span class="journey-dot" aria-hidden="true"></span>Already registered? <a href="profile.php">Open My Profile →</a></div>
        </aside>
    </div>
</section>
<section class="container section">
    <p class="eyebrow">EXPLORE THE PROGRAMS</p>
    <h2>Two programs, different kinds of support.</h2>
    <div class="two-column">
        <article class="card program-card">
            <div class="program-symbol" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="18" height="14" rx="3"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12c5 3 13 3 18 0M12 12v4"/></svg></div>
            <span class="badge">DOLE · Emergency employment</span>
            <h3>TUPAD</h3>
            <p>Temporary community work and income support for disadvantaged or displaced workers.</p>
            <a href="tupad.php" class="text-link">Requirements and process →</a>
        </article>
        <article class="card program-card red-top">
            <div class="program-symbol" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M9 21v-7h6v7"/></svg></div>
            <span class="badge red">DSWD · Family support</span>
            <h3>4Ps</h3>
            <p>Conditional support for eligible households’ health, nutrition, and children’s education.</p>
            <a href="fourps.php" class="text-link">Requirements and process →</a>
        </article>
    </div>
</section>
<section class="soft-section">
    <div class="container two-column">
        <div><h2>Know when to take the next step.</h2><p>View registration, interview, orientation, and TUPAD community work dates.</p><a href="calendar.php" class="button primary">View calendar</a></div>
        <div><h2>Need someone to guide you?</h2><p>Ask barangay staff or someone you trust to help. Staff can help you prepare your details and set up the email needed for registration.</p><a href="help.php" class="button secondary">See how to get help</a></div>
    </div>
</section>
<?php require 'footer.php'; ?>
