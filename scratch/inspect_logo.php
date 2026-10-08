<?php
$file = __DIR__ . '/../public/assets/media/logo.png';
$info = getimagesize($file);
echo json_encode([
    'width' => $info[0],
    'height' => $info[1],
    'filesize' => filesize($file),
    'mime' => $info['mime']
], JSON_PRETTY_PRINT);
