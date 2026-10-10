<?php

namespace App\Observers;

use App\Models\Branch;
use App\Services\ReferenceDataService;

class BranchObserver
{
    public function created(Branch $branch): void
    {
        ReferenceDataService::clearBranchesCache();
    }

    public function updated(Branch $branch): void
    {
        ReferenceDataService::clearBranchesCache();
    }

    public function deleted(Branch $branch): void
    {
        ReferenceDataService::clearBranchesCache();
    }

    public function restored(Branch $branch): void
    {
        ReferenceDataService::clearBranchesCache();
    }

    public function forceDeleted(Branch $branch): void
    {
        ReferenceDataService::clearBranchesCache();
    }
}
