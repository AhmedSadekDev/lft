<?php
$dir = __DIR__ . '/../docs/modernization/evidence/phase-4b-admin-ui-redesign';

$loginFile = $dir . '/login-rendered.html';
$dashFile = $dir . '/dashboard-rendered.html';

$loginHtml = file_get_contents($loginFile);
$dashHtml = file_get_contents($dashFile);

// Replace absolute APP_URL with relative paths
$loginHtml = str_replace('https://cloudymenue.cloudy-digital.com/assets/', '../../../../public/assets/', $loginHtml);
$dashHtml = str_replace('https://cloudymenue.cloudy-digital.com/assets/', '../../../../public/assets/', $dashHtml);

file_put_contents($dir . '/login-preview.html', $loginHtml);
file_put_contents($dir . '/dashboard-preview.html', $dashHtml);

// Also create a dark version for direct preview
$dashDarkHtml = str_replace('data-theme="light"', 'data-theme="dark"', $dashHtml);
if (strpos($dashDarkHtml, 'data-theme="dark"') === false) {
    $dashDarkHtml = str_replace('<html ', '<html data-theme="dark" ', $dashDarkHtml);
}
file_put_contents($dir . '/dashboard-preview-dark.html', $dashDarkHtml);

echo "Self contained previews created!\n";
