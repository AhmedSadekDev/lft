<?php

$langFiles = ['admin', 'main', 'alerts', 'actions', 'auth', 'profile', 'booking_container', 'container_stages'];
$scanRoots = ['resources/views', 'app'];
$used = [];

foreach ($scanRoots as $root) {
    if (! is_dir($root)) {
        continue;
    }
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($rii as $f) {
        if (! $f->isFile()) {
            continue;
        }
        if (! preg_match('/\.(blade\.php|php)$/', $f->getFilename())) {
            continue;
        }
        $c = file_get_contents($f->getPathname());
        // Capture full key path; allow apostrophes inside double-quoted keys
        if (preg_match_all('/__\(\s*"([a-zA-Z0-9_]+)\.([^"]+)"/', $c, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                if (in_array($match[1], $langFiles, true)) {
                    $used[$match[1]][$match[2]] = ($used[$match[1]][$match[2]] ?? 0) + 1;
                }
            }
        }
        if (preg_match_all("/__\(\s*'([a-zA-Z0-9_]+)\.([^']+)'/", $c, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                if (in_array($match[1], $langFiles, true)) {
                    $used[$match[1]][$match[2]] = ($used[$match[1]][$match[2]] ?? 0) + 1;
                }
            }
        }
    }
}

function keyExists(array $arr, string $key): bool
{
    // Laravel Arr::get: exact key first, then dotted nested
    if (array_key_exists($key, $arr)) {
        return true;
    }
    $parts = explode('.', $key);
    $cur = $arr;
    foreach ($parts as $p) {
        if (! is_array($cur) || ! array_key_exists($p, $cur)) {
            return false;
        }
        $cur = $cur[$p];
    }

    return true;
}

$missing = [];
foreach ($used as $file => $keys) {
    foreach (['ar', 'en'] as $locale) {
        $path = "resources/lang/{$locale}/{$file}.php";
        if (! file_exists($path)) {
            continue;
        }
        $arr = include $path;
        if (! is_array($arr)) {
            continue;
        }
        foreach ($keys as $k => $cnt) {
            if (! keyExists($arr, $k)) {
                $missing[] = [$locale, $file, $k, $cnt];
            }
        }
    }
}

usort($missing, fn ($a, $b) => [$a[1], $a[2], $a[0]] <=> [$b[1], $b[2], $b[0]]);

echo 'Missing: ' . count($missing) . PHP_EOL;
foreach ($missing as $row) {
    echo implode('|', $row) . PHP_EOL;
}
