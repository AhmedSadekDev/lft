<?php
// Apply theme initialization in header.blade.php
$headerFile = __DIR__ . '/../resources/views/layouts/includes/header.blade.php';
$headerContent = file_get_contents($headerFile);

if (strpos($headerContent, 'lft_theme') === false) {
    $search = '<head>';
    $replace = "<head>\n    <script>\n        (function() {\n            var saved = localStorage.getItem('lft_theme');\n            var theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');\n            document.documentElement.setAttribute('data-theme', theme);\n        })();\n    </script>";
    $headerContent = str_replace($search, $replace, $headerContent);
    file_put_contents($headerFile, $headerContent);
    echo "header.blade.php updated with inline theme script\n";
} else {
    echo "header.blade.php already has theme script\n";
}
