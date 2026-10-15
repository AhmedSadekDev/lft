<?php

/**
 * One-shot normalizer: apply the banks-style compact form shell/footer across admin blades.
 * Run: php scratch/normalize_admin_forms.php
 */

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$changed = [];

foreach ($rii as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $base = $file->getBasename();
    $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);

    // Skip heavy / special UIs already customized
    if (str_contains($rel, 'booking-invoices' . DIRECTORY_SEPARATOR . 'show')) {
        continue;
    }
    if (str_contains($rel, 'bookings' . DIRECTORY_SEPARATOR . 'form.blade.php')) {
        continue;
    }
    if (str_contains($rel, 'bookings' . DIRECTORY_SEPARATOR . 'edit.blade.php')) {
        continue;
    }

    $original = file_get_contents($path);
    $content = $original;

    // 1) Compact footer class on every card-footer
    $content = preg_replace(
        '/class="card-footer(?![^"]*lft-simple-form-footer)/',
        'class="card-footer lft-simple-form-footer',
        $content
    );
    $content = preg_replace(
        "/class='card-footer(?![^']*lft-simple-form-footer)/",
        "class='card-footer lft-simple-form-footer",
        $content
    );

    // 2) Drop useless row wrapper that only wraps the submit button in footers
    $content = preg_replace(
        '/(<div class="card-footer lft-simple-form-footer">)\s*<div class="row\s*">\s*(.*?)\s*<\/div>\s*(<\/div>)/s',
        '$1$2$3',
        $content
    );

    // 3) create/edit shells: bankd typo + simple card classes
    if (in_array($base, ['create.blade.php', 'edit.blade.php'], true)) {
        $content = str_replace('bankd bankd-custom', 'card card-custom', $content);
        $content = str_replace('bankd-header', 'card-header', $content);
        $content = str_replace('bankd-title', 'card-title', $content);
        $content = str_replace('bankd-body', 'card-body', $content);
        $content = str_replace('vaultd', 'card', $content);

        // Add lft-simple-card to standard cards
        $content = preg_replace(
            '/class="card card-custom gutter-b(?![^"]*lft-simple-card)/',
            'class="card card-custom gutter-b lft-simple-card',
            $content
        );

        // Wrap section content with lft-simple-page if missing
        if (! str_contains($content, 'lft-simple-page')
            && preg_match('/@section\(\s*["\']content["\']\s*\)/', $content)
        ) {
            $content = preg_replace(
                '/(@section\(\s*["\']content["\']\s*\)\s*)/u',
                "$1\n    <div class=\"container-fluid lft-simple-page\">\n",
                $content,
                1
            );
            // Close before @endsection
            $content = preg_replace(
                '/\n@endsection/u',
                "\n    </div>\n@endsection",
                $content,
                1
            );
        }

        // Normalize header back button float-right clutter
        $content = str_replace(
            'class="btn btn-secondary float-right"',
            'class="btn btn-secondary btn-sm"',
            $content
        );
        $content = str_replace(
            '<div class="mt-3">',
            '<div class="card-toolbar">',
            $content
        );
    }

    // 4) form.blade.php: ensure Form::open gets lft-simple-form class when missing
    if ($base === 'form.blade.php') {
        $content = preg_replace_callback(
            '/Form::open\(\[(.*?)\]\)/s',
            static function (array $m) {
                $inner = $m[1];
                if (str_contains($inner, 'lft-simple-form')) {
                    return $m[0];
                }
                if (preg_match("/'class'\s*=>\s*'([^']*)'/", $inner, $cm)) {
                    $inner = preg_replace(
                        "/'class'\s*=>\s*'([^']*)'/",
                        "'class' => '" . trim($cm[1] . ' lft-simple-form') . "'",
                        $inner,
                        1
                    );
                } elseif (preg_match('/"class"\s*=>\s*"([^"]*)"/', $inner, $cm)) {
                    $inner = preg_replace(
                        '/"class"\s*=>\s*"([^"]*)"/',
                        '"class" => "' . trim($cm[1] . ' lft-simple-form') . '"',
                        $inner,
                        1
                    );
                } else {
                    $inner = rtrim($inner) . ",\n        'class' => 'lft-simple-form',\n    ";
                }

                return 'Form::open([' . $inner . '])';
            },
            $content
        );

        // Polish primary submit classes inside footers
        $content = str_replace(
            'btn btn-primary btn-md text-uppercase font-weight-bold chat-send py-2 px-6',
            'btn btn-primary',
            $content
        );
    }

    if ($content !== $original) {
        file_put_contents($path, $content);
        $changed[] = $rel;
    }
}

echo 'Updated ' . count($changed) . " files\n";
foreach ($changed as $file) {
    echo " - {$file}\n";
}
