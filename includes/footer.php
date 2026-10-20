</main>

<footer class="site-footer">
    <div class="app-container">
        <div class="footer-top-grid">
            <div>
                <a href="<?= APP_BASE_PATH ?>/index.php" class="brand-group" style="margin-bottom: 16px;">
                    <?= get_site_logo_html() ?>
                    <div class="brand-text">
                        <span class="brand-title">Taylor's</span>
                        <span class="brand-subtitle">CAMPUS EVENTS</span>
                    </div>
                </a>
                <p style="font-size: 13.5px; color: var(--text-secondary); max-width: 320px; line-height: 1.5;">
                    Your campus. Your community. Your next great experience.
                </p>
            </div>

            <div>
                <h4 class="footer-col-title">Discover</h4>
                <ul class="footer-links-list">
                    <li><a href="<?= APP_BASE_PATH ?>/events.php" class="footer-link">Explore events</a></li>
                    <li><a href="<?= APP_BASE_PATH ?>/events.php?category=arts-culture" class="footer-link">Clubs & societies</a></li>
                    <li><a href="<?= APP_BASE_PATH ?>/events.php" class="footer-link">Campus life</a></li>
                </ul>
            </div>

            <div>
                <h4 class="footer-col-title">Need a hand?</h4>
                <ul class="footer-links-list">
                    <li><a href="<?= APP_BASE_PATH ?>/terms.php" class="footer-link">Community guidelines</a></li>
                    <li><a href="<?= APP_BASE_PATH ?>/privacy.php" class="footer-link">Privacy notice (PDPA)</a></li>
                    <li><a href="mailto:campuslife@taylors.edu.my" class="footer-link">Contact Student Life</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom-bar">
            <span>© 2026 Taylor's University. All rights reserved.</span>
            <div style="display:flex; gap:20px; align-items:center;">
                <a href="<?= APP_BASE_PATH ?>/privacy.php" class="footer-link">Privacy policy</a>
                <a href="<?= APP_BASE_PATH ?>/terms.php" class="footer-link">Terms of use</a>
                <span style="color:var(--text-muted)">Lakeside Campus, Malaysia</span>
            </div>
        </div>
    </div>
</footer>

<!-- Floating Back To Top Button -->
<button type="button" id="back-to-top" class="back-to-top-btn" aria-label="Back to top" title="Back to top">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
</button>

<script src="<?= APP_BASE_PATH ?>/assets/js/main.js"></script>
</body>
</html>
