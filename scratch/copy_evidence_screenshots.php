<?php
$brainDir = 'C:/Users/Egy Sky/.gemini/antigravity-ide/brain/6a2d72df-65ce-49a7-baab-f26dc4ecee66';
$evidenceDir = __DIR__ . '/../docs/modernization/evidence/phase-4b-admin-ui-redesign';

$filesToCopy = [
    'login_preview_screenshot_1791484512210.png' => '01-login-preview.png',
    'dashboard_light_preview_1791484586328.png' => '02-dashboard-light-mode.png',
    'dashboard_dark_preview_1791484660577.png' => '03-dashboard-dark-mode-full.png',
    'dashboard_dark_top_1791484715463.png' => '04-dashboard-dark-mode-top.png',
    'dashboard_dark_bottom_1791484898554.png' => '05-dashboard-dark-mode-bottom.png',
];

foreach ($filesToCopy as $src => $dest) {
    $srcPath = $brainDir . '/' . $src;
    $destPath = $evidenceDir . '/' . $dest;
    if (file_exists($srcPath)) {
        copy($srcPath, $destPath);
        echo "Copied $src to $dest (" . filesize($destPath) . " bytes)\n";
    } else {
        echo "File not found: $srcPath\n";
    }
}
