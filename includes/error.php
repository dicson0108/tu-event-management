<?php
// Intentionally independent of authentication and database-backed site chrome.
function render_error_page(int $status): void {
    $pages = [
        403 => ['Access denied', 'You do not have permission to open this page or complete this action. Return to campus events to continue.'],
        404 => ['Page not found', 'This page may have moved or is no longer available. Find your next campus experience in Explore events.'],
        500 => ['Something went wrong', 'Your request could not be completed. Please try again in a moment, or return to the home page.'],
    ];
    [$title, $message] = $pages[$status];
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    $base = htmlspecialchars(APP_BASE_PATH, ENT_QUOTES, 'UTF-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#f5f5f7">
    <title><?= $status ?> · <?= $title ?> · Taylor's Campus Events</title>
    <link rel="icon" type="image/png" href="<?= $base ?>/assets/images/logo/logo.png">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/errors.css">
</head>
<body class="error-page" data-error-code="<?= $status ?>">
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <header class="error-header">
        <a class="error-brand" href="<?= $base ?>/index.php" aria-label="Taylor's Campus Events home">
            <img src="<?= $base ?>/assets/images/logo/logo.png" width="44" height="44" alt="">
            <span><strong>Taylor's</strong><span>Campus Events</span></span>
        </a>
    </header>
    <main id="main-content" class="error-main">
        <div class="error-content">
            <p class="error-code" aria-label="Error <?= $status ?>"><?= $status ?></p>
            <h1><?= $title ?></h1>
            <p class="error-description"><?= $message ?></p>
            <nav class="error-actions" aria-label="Error recovery">
                <a class="btn-primary" href="<?= $base ?>/events.php">Explore events <span aria-hidden="true">↗</span></a>
                <a class="btn-secondary" href="<?= $base ?>/index.php">Back to home</a>
            </nav>
        </div>
    </main>
    <footer class="error-footer">Taylor’s Campus Events <span aria-hidden="true">·</span> Go beyond the classroom.</footer>
</body>
</html>
    <?php
    exit;
}

function handle_app_exception(Throwable $error): void {
    error_log((string) $error);
    // Discard partial markup before rendering a complete error response.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (str_starts_with($_SERVER['SCRIPT_NAME'] ?? '', app_url('/api/'))) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['success' => false, 'message' => 'Your request could not be completed. Please try again.']);
        exit;
    }
    render_error_page(500);
}
