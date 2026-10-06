<?php
$sets = [];
foreach (['baseline', 'current'] as $label) {
    $xml = simplexml_load_file(__DIR__.'/final-'.$label.'-junit.xml');
    if (!$xml) throw new RuntimeException('Invalid JUnit');
    $passed = $failed = $errors = $skipped = [];
    $assertions = 0;
    foreach ($xml->xpath('//testcase') as $test) {
        $name = (string) $test['class'].'::'.(string) $test['name'];
        $assertions += (int) $test['assertions'];
        if (isset($test->error)) $errors[] = $name;
        elseif (isset($test->failure)) $failed[] = $name;
        elseif (isset($test->skipped)) $skipped[] = $name;
        else $passed[] = $name;
    }
    foreach (['passed', 'failed', 'errors', 'skipped'] as $key) sort($$key);
    $sets[$label.'_passed_tests'] = $passed;
    $sets[$label.'_failed_tests'] = $failed;
    $sets[$label.'_error_tests'] = $errors;
    $sets[$label.'_skipped_tests'] = $skipped;
    $sets[$label.'_counts'] = ['tests' => count($passed)+count($failed)+count($errors)+count($skipped),
        'assertions' => $assertions, 'passed' => count($passed), 'failures' => count($failed), 'errors' => count($errors), 'skipped' => count($skipped)];
}
$sets['new_failures'] = array_values(array_diff($sets['current_failed_tests'], $sets['baseline_failed_tests']));
$sets['new_errors'] = array_values(array_diff($sets['current_error_tests'], $sets['baseline_error_tests']));
$sets['resolved_failures'] = array_values(array_diff($sets['baseline_failed_tests'], $sets['current_failed_tests']));
$sets['resolved_errors'] = array_values(array_diff($sets['baseline_error_tests'], $sets['current_error_tests']));
$sets['classification'] = 'PRE-EXISTING REGRESSION — NOT INTRODUCED BY PHASE 2';
echo json_encode($sets, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
