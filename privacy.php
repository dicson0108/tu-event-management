<?php
$page_title = "Privacy Policy";
require_once __DIR__ . '/includes/header.php';
?>

<div class="app-container">
    <div class="legal-layout">
        <header class="legal-header">
            <div class="section-label">LEGAL & COMPLIANCE / PDPA 2010</div>
            <h1 class="legal-title">Privacy Policy</h1>
            <div class="legal-meta">
                Taylor's University Lakeside Campus · Effective Date: October 2026 · Version 2.1
            </div>
        </header>

        <article class="legal-card">
            <div class="legal-callout">
                <strong>Summary for Students:</strong> We only collect the minimal personal information necessary to manage campus event registrations, send confirmation notices, and ensure campus safety at Lakeside Campus. We never sell your personal data.
            </div>

            <section class="legal-section">
                <h2 class="legal-section-title">
                    <span class="legal-section-num">01.</span> Commitment to Your Privacy
                </h2>
                <p class="legal-text">
                    Taylor's University ("Taylor's", "we", "us", or "our") values the privacy of our students, staff, alumni, and campus visitors. This Privacy Policy outlines how your personal data is collected, stored, processed, and safeguarded when using the <strong>Taylor's Campus Events</strong> platform.
                </p>
                <p class="legal-text">
                    This policy is formulated in accordance with the <strong>Personal Data Protection Act 2010 (PDPA)</strong> of Malaysia and relevant institutional policies of Taylor's Higher Education.
                </p>
            </section>

            <section class="legal-section">
                <h2 class="legal-section-title">
                    <span class="legal-section-num">02.</span> Personal Data We Collect
                </h2>
                <p class="legal-text">
                    When you create an account, register for events, or participate in discussions on this platform, we may collect:
                </p>
                <ul class="legal-list">
                    <li><strong>Account Identification:</strong> Full name, preferred name, official Taylor's email address (e.g. <code>@sd.taylors.edu.my</code>), student/staff ID number, and encrypted password hash.</li>
                    <li><strong>Contact Information:</strong> Phone number and faculty/department affiliation for registration coordination and waitlist notifications.</li>
                    <li><strong>Event Participation:</strong> Registrations history, waitlist timestamps, attendance records, feedback ratings, and post-event reviews.</li>
                    <li><strong>Technical Logs:</strong> IP address, device browser type, and CSRF authentication session tokens for system security and fraud prevention.</li>
                </ul>
            </section>

            <section class="legal-section">
                <h2 class="legal-section-title">
                    <span class="legal-section-num">03.</span> Purpose of Data Processing
                </h2>
                <p class="legal-text">
                    Your personal information is used exclusively for the following operational purposes:
                </p>
                <ul class="legal-list">
                    <li>Validating student and staff eligibility for exclusive campus activities.</li>
                    <li>Processing event bookings, ticket allocations, and automated waitlist promotions (FIFO).</li>
                    <li>Sending critical operational updates (e.g., venue adjustments, weather contingency plans for Lakeside Amphitheatre, or session cancellations).</li>
                    <li>Monitoring capacity limits and safety regulations in accordance with Taylor's Lakeside Campus protocols.</li>
                    <li>Aggregating anonymised attendance statistics to improve student life programs and university clubs.</li>
                </ul>
            </section>

            <section class="legal-section">
                <h2 class="legal-section-title">
                    <span class="legal-section-num">04.</span> Photography & Media Notice
                </h2>
                <p class="legal-text">
                    Events organized across Taylor's Lakeside Campus may be documented via photography, audio, or video recording by authorised university media personnel. Such materials may be published in official university newsletters, social channels, and promotional materials.
                </p>
                <p class="legal-text">
                    If you do not wish to be photographed or recorded, please inform the event organizers or registration desk personnel upon check-in.
                </p>
            </section>

            <section class="legal-section">
                <h2 class="legal-section-title">
                    <span class="legal-section-num">05.</span> Data Protection & Retention
                </h2>
                <p class="legal-text">
                    We maintain robust organizational and technical safeguards, including SSL/TLS encryption in transit, strict password hashing (`bcrypt`), CSRF protection, and restricted administrative role-based access control.
                </p>
                <p class="legal-text">
                    Your registration history is retained for the duration of your active academic tenure at Taylor's University and archived in compliance with regulatory university retention standards.
                </p>
            </section>

            <section class="legal-section">
                <h2 class="legal-section-title">
                    <span class="legal-section-num">06.</span> Your Rights & Contact Information
                </h2>
                <p class="legal-text">
                    Under the PDPA 2010, you have the right to request access to and correction of your personal data, or request restriction of data processing. You can update your profile information directly in <a href="/profile.php" style="color:var(--color-primary); font-weight:600;">My Profile</a>.
                </p>
                <p class="legal-text">
                    For inquiries or privacy concerns, please contact our Data Protection Liaison:
                </p>
                <div style="background:var(--bg-surface-subtle); padding:16px 20px; border-radius:var(--radius-sm); font-size:14px; margin-top:12px;">
                    <strong>Office of the Registrar / Student Life Division</strong><br>
                    Taylor's University Lakeside Campus<br>
                    No. 1, Jalan Taylor's, 47500 Subang Jaya, Selangor, Malaysia<br>
                    Email: <a href="mailto:campuslife@taylors.edu.my" style="color:var(--color-primary);">campuslife@taylors.edu.my</a>
                </div>
            </section>
        </article>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
