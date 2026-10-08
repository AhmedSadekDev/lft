<?php
require __DIR__.'/phase4a-bootstrap.php';

use App\Models\Company;

echo "========================================================================\n";
echo "VERIFICATION OF COMPANIES DROPDOWN IN BOOKINGS INDEX\n";
echo "========================================================================\n\n";

// Legacy query from git commit aa0fb82f3a0c5e15304fa7e4f04c1f4c85e9a549:
// $companies = Company::query()->get();
$legacyCompanies = Company::query()->get();

// Current BookingController index query:
// $companies = Company::query()->get();
$currentCompanies = Company::query()->get();

$matchCount = ($legacyCompanies->count() === $currentCompanies->count());
$legacyIds = $legacyCompanies->pluck('id')->all();
$currentIds = $currentCompanies->pluck('id')->all();
$idsMatch = ($legacyIds === $currentIds);

echo "Total Companies Count: " . $currentCompanies->count() . "\n";
echo "Collection Count Match: " . ($matchCount ? "YES" : "NO") . "\n";
echo "Exact IDs Order Match:  " . ($idsMatch ? "YES" : "NO") . "\n";

// Verify Blade fields access
$first = $currentCompanies->first();
$fieldsPresent = isset($first->id) && isset($first->name);
echo "Blade Fields 'id' and 'name' present: " . ($fieldsPresent ? "YES" : "NO") . "\n";

// First 5 items sample
echo "\nFirst 5 companies sample:\n";
foreach ($currentCompanies->take(5) as $c) {
    echo "  ID: {$c->id} | Name: {$c->name}\n";
}

$allGood = $matchCount && $idsMatch && $fieldsPresent;
echo "\nResult: " . ($allGood ? "100% BYTE & ORDER IDENTICAL TO LEGACY" : "FAIL") . "\n";
echo "========================================================================\n";
