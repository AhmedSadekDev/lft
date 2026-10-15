<?php

/**
 * Clean nested container wrappers + ensure form cards use lft-simple-card.
 * Run: php scratch/cleanup_nested_forms.php
 */

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$changed = [];

foreach ($rii as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $base = $file->getBasename();
    if (! in_array($base, ['create.blade.php', 'edit.blade.php', 'form.blade.php'], true)) {
        continue;
    }

    $path = $file->getPathname();
    $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    $original = file_get_contents($path);
    $content = $original;
    $removedInner = false;

    if (preg_match('/<div class="container-fluid lft-simple-page">\s*<div class="container(?:-fluid)?">/u', $content)) {
        $content = preg_replace(
            '/(<div class="container-fluid lft-simple-page">)\s*<div class="container(?:-fluid)?">/u',
            '$1',
            $content,
            1
        );
        $removedInner = true;
    }

    if ($removedInner) {
        // Drop the inner container's close: </div></div>@endsection → </div>@endsection
        $content = preg_replace(
            '/(<\/div>\s*)<\/div>\s*(@endsection)/u',
            '$1$2',
            $content,
            1
        );
    }

    // Ensure cards on create/edit get lft-simple-card
    $content = preg_replace(
        '/class="card card-custom(?![^"]*lft-simple-card)/',
        'class="card card-custom gutter-b lft-simple-card',
        $content
    );
    $content = str_replace(
        'card card-custom gutter-b lft-simple-card gutter-b',
        'card card-custom gutter-b lft-simple-card',
        $content
    );

    $content = str_replace(
        'class="card-header flex-wrap py-5"',
        'class="card-header border-0"',
        $content
    );

    if ($content !== $original) {
        file_put_contents($path, $content);
        $changed[] = $rel;
    }
}

echo 'Updated ' . count($changed) . " form files\n";
foreach ($changed as $file) {
    echo " - {$file}\n";
}
