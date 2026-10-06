<?php
// Source inventory; does not boot Laravel or connect to a database.
$root = dirname(__DIR__, 4);
$resources = [];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app/Http/Controllers'));
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') continue;
    $source = file_get_contents($file->getPathname());
    $tokens = token_get_all($source);
    for ($i = 0; $i < count($tokens); $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
        $line = $tokens[$i][2];
        $j = $i + 1;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
        if (!isset($tokens[$j]) || !is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) continue;
        $name = $tokens[$j][1];
        if (!preg_match('/^(index|all|specification|waiting|loading|unloading|notifications|expenses|bookings|invoices|getNotifications)$/i', $name)) continue;
        while (isset($tokens[$j]) && $tokens[$j] !== '{') $j++;
        $depth = 1;
        $body = '';
        while (isset($tokens[++$j]) && $depth) {
            $t = $tokens[$j];
            if ($t === '{' || (is_array($t) && in_array($t[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]))) $depth++;
            if ($t === '}') $depth--;
            if ($depth) $body .= is_array($t) ? $t[1] : $t;
        }
        $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        $resources[] = ['source' => $path, 'method' => $name, 'line' => $line,
            'pagination' => str_contains($body, 'paginate(') ? 'existing paginate; inspect source for parameters' : (str_contains($body, 'Paginator') ? 'manual paginator' : 'no paginator call in method'),
            'search_filter_sort_lines' => array_values(array_filter(explode("\n", $body), fn ($l) => preg_match('/search|word|filter|where|orderBy|sort|paginate|->get\(|::all\(|->with\(/i', $l))),
            'body_sha256' => hash('sha256', $body),
            'verification' => 'SOURCE INVENTORY ONLY; not endpoint equivalence evidence'];
    }
}
echo json_encode(['resources' => $resources, 'count' => count($resources)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
