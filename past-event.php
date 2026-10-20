<?php
require_once __DIR__ . '/config/app.php';
$event_id = (int)($_GET['id'] ?? 0);
$target_url = app_url('/event-detail.php' . ($event_id ? '?id=' . $event_id . '#reviews' : ''));
header('Location: ' . $target_url, true, 301);
exit;
