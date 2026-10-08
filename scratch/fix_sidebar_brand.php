<?php
$file = __DIR__ . '/../resources/views/layouts/includes/sidebar.blade.php';
$content = file_get_contents($file);

$target = '        <!--begin::Brand-->
        <div class="brand flex-column-auto" id="kt_brand">
            <!--begin::Logo-->
            <a href="{{ route(\'main\') }}" class="brand-logo">
                <img alt="Leader for Trans" src="{{ asset(\'assets/media/logo.png\') }}" />
                <span class="lft-brand-name" dir="ltr">LEADER<span><small>TRANSPORT & LOGISTICS</small></span></span>
            </a>
            <!--end::Logo-->';

$replacement = '        <!--begin::Brand-->
        <div class="brand flex-column-auto" id="kt_brand">
            <!--begin::Logo-->
            <a href="{{ route(\'main\') }}" class="brand-logo lft-sidebar-brand d-flex align-items-center">
                <img src="{{ asset(\'assets/media/logo.png\') }}" alt="Leader for Trans" class="lft-sidebar-logo" />
                <div class="lft-brand-titles mr-3">
                    <span class="lft-brand-title">Leader for Trans</span>
                    <span class="lft-brand-subtitle">منظومة التجارة والأعمال</span>
                </div>
            </a>
            <!--end::Logo-->';

if (strpos($content, $target) !== false) {
    $content = str_replace($target, $replacement, $content);
    file_put_contents($file, $content);
    echo "Sidebar brand updated successfully!\n";
} else {
    // Try regex
    $pattern = '/<!--begin::Brand-->\s*<div class="brand flex-column-auto"[^>]*>.*?<!--end::Logo-->/s';
    $content = preg_replace($pattern, $replacement, $content);
    file_put_contents($file, $content);
    echo "Sidebar brand updated via regex!\n";
}
