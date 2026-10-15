<?php

/**
 * Normalize all admin index listing pages + common Delete/Swal bugs.
 * Run: php scratch/normalize_admin_indexes.php
 */

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$changed = [];

$skipRels = [
    'index.blade.php', // dashboard home
    'agent-photos' . DIRECTORY_SEPARATOR . 'index.blade.php',
    'accounts' . DIRECTORY_SEPARATOR . 'index.blade.php',
    'reports' . DIRECTORY_SEPARATOR . 'index.blade.php',
];

foreach ($rii as $file) {
    if (! $file->isFile() || $file->getBasename() !== 'index.blade.php') {
        continue;
    }

    $path = $file->getPathname();
    $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);

    if (in_array($rel, $skipRels, true)) {
        continue;
    }

    $original = file_get_contents($path);
    $content = $original;

    // --- Structural typos (bankd / vaultd) ---
    $content = preg_replace('/\bbankd bankd-custom\b/', 'card card-custom', $content);
    $content = preg_replace('/\bvaultd vault-custom\b/', 'card card-custom', $content);
    $content = preg_replace('/\bbankd-header\b/', 'card-header', $content);
    $content = preg_replace('/\bbankd-toolbar\b/', 'card-toolbar', $content);
    $content = preg_replace('/\bbankd-body\b/', 'card-body', $content);
    $content = preg_replace('/\bbankd-title\b/', 'card-title', $content);
    $content = preg_replace('/\bbankd-custom\b/', 'card-custom', $content);
    $content = preg_replace('/\bbankd\b/', 'card', $content);
    $content = preg_replace('/\bvaultd-header\b/', 'card-header', $content);
    $content = preg_replace('/\bvaultd-toolbar\b/', 'card-toolbar', $content);
    $content = preg_replace('/\bvaultd-body\b/', 'card-body', $content);
    $content = preg_replace('/\bvaultd-title\b/', 'card-title', $content);
    $content = preg_replace('/\bvaultd\b/', 'card', $content);

    // --- Listing shell: container → fluid page ---
    // Only the first content wrapper after @section('content')
    $content = preg_replace(
        '/(@section\(\s*[\'"]content[\'"]\s*\)\s*\n?\s*)<div class="container">/u',
        '$1<div class="container-fluid lft-simple-page">',
        $content,
        1
    );
    $content = preg_replace(
        '/(@section\(\s*[\'"]content[\'"]\s*\)\s*\n?\s*)<div class="container-fluid">/u',
        '$1<div class="container-fluid lft-simple-page">',
        $content,
        1
    );

    // If still no lft-simple-page, wrap once
    if (! str_contains($content, 'lft-simple-page')
        && preg_match('/@section\(\s*[\'"]content[\'"]\s*\)/', $content)
    ) {
        $content = preg_replace(
            '/(@section\(\s*[\'"]content[\'"]\s*\)\s*)/u',
            "$1\n    <div class=\"container-fluid lft-simple-page\">\n",
            $content,
            1
        );
        $content = preg_replace(
            '/\n@endsection/u',
            "\n    </div>\n@endsection",
            $content,
            1
        );
    }

    // --- Card shell ---
    $content = preg_replace(
        '/class="card card-custom(?![^"]*lft-simple-card)/',
        'class="card card-custom gutter-b lft-simple-card lft-simple-card--wide',
        $content
    );
    $content = str_replace(
        'card card-custom gutter-b lft-simple-card lft-simple-card--wide gutter-b',
        'card card-custom gutter-b lft-simple-card lft-simple-card--wide',
        $content
    );
    // Deduplicate if already had gutter-b before our insert left a double
    $content = preg_replace(
        '/class="card card-custom gutter-b lft-simple-card lft-simple-card--wide gutter-b /',
        'class="card card-custom gutter-b lft-simple-card lft-simple-card--wide ',
        $content
    );

    $content = str_replace(
        'class="card-header flex-wrap py-5"',
        'class="card-header border-0"',
        $content
    );
    $content = preg_replace(
        '/class="card-header row justify-content-between align-items-center flex-wrap py-5"/',
        'class="card-header border-0"',
        $content
    );

    // --- JS bugs ---
    $content = preg_replace(
        '/\(function\s*\(\$\):\s*\{\s*"use strict";s?\s*\}\)\(jQuery\);\s*/',
        '',
        $content
    );
    $content = str_replace('"use strict";s', '"use strict";', $content);

    // Unquoted success title: title: تم  → title: "تم"
    $content = preg_replace(
        "/title:\s*\{\{\s*__\('alerts\.done'\)\s*\}\}/",
        "title: \"{{ __('alerts.done') }}\"",
        $content
    );
    $content = preg_replace(
        '/title:\s*\{\{\s*__\("alerts\.done"\)\s*\}\}/',
        'title: "{{ __(\'alerts.done\') }}"',
        $content
    );

    // Add Swal button classes when Delete exists and customClass is missing
    if (preg_match('/function\s+Delete/', $content) && ! str_contains($content, 'customClass')) {
        $content = preg_replace(
            '/(cancelButtonText:\s*["\']\{\{[^}]+\}\}["\'],)\s*(\}\.then)/',
            "$1\n                buttonsStyling: false,\n                customClass: {\n                    confirmButton: 'btn btn-primary mx-1',\n                    cancelButton: 'btn btn-secondary mx-1',\n                },\n            $2",
            $content
        );
    }

    // Same for DeleteExpense / DeleteReceipt variants
    if (preg_match('/function\s+Delete\w+/', $content) && ! str_contains($content, 'customClass')) {
        $content = preg_replace(
            '/(cancelButtonText:\s*["\']\{\{[^}]+\}\}["\'],)\s*(\}\.then)/',
            "$1\n                buttonsStyling: false,\n                customClass: {\n                    confirmButton: 'btn btn-primary mx-1',\n                    cancelButton: 'btn btn-secondary mx-1',\n                },\n            $2",
            $content
        );
    }

    // HTML comments Bankd → Card
    $content = str_replace('<!--begin::Bankd-->', '<!--begin::Card-->', $content);
    $content = str_replace('<!--end::Bankd-->', '<!--end::Card-->', $content);

    if ($content !== $original) {
        file_put_contents($path, $content);
        $changed[] = $rel;
    }
}

echo 'Updated ' . count($changed) . " index files\n";
foreach ($changed as $file) {
    echo " - {$file}\n";
}
