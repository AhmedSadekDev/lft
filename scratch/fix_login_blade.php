<?php
$f = __DIR__ . '/../resources/views/auth/login.blade.php';
$c = file_get_contents($f);
$c = str_replace('@if ($errors->any())', '@if (isset($errors) && $errors->any())', $c);
file_put_contents($f, $c);
echo "login.blade.php updated!\n";
