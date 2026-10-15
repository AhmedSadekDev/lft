<?php

/**
 * Fix Delete/Swal handlers across admin blades.
 * Run: php scratch/fix_delete_swal.php
 */

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$changed = [];

$inject = "\n                buttonsStyling: false,\n                customClass: {\n                    confirmButton: 'btn btn-primary mx-1',\n                    cancelButton: 'btn btn-secondary mx-1',\n                },";

foreach ($rii as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    $original = file_get_contents($path);
    $content = $original;

    $content = str_replace('"use strict";s', '"use strict";', $content);

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

    // Inject button styling before }).then when a Delete* Swal is missing customClass
    if (preg_match('/function\s+Delete\w*/', $content) && ! str_contains($content, 'customClass')) {
        $content = preg_replace(
            '/(cancelButtonText:\s*["\']\{\{\s*__\([\'"]alerts\.cancel[\'"]\)\s*\}\}["\'],)(\s*)(\}\)\s*\.then)/',
            '$1' . $inject . '$2$3',
            $content
        );
    }

    // Strip empty strict IIFE leftovers that do nothing
    $content = preg_replace(
        '/\s*\(function\s*\(\$\)\s*\{\s*"use strict";\s*\}\)\(jQuery\);\s*/',
        "\n\n",
        $content
    );

    if ($content !== $original) {
        file_put_contents($path, $content);
        $changed[] = $rel;
    }
}

echo 'Updated ' . count($changed) . " files\n";
foreach ($changed as $file) {
    echo " - {$file}\n";
}
