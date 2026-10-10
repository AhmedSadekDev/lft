<?php

namespace App\Observers;

use App\Models\Yard;
use App\Services\ReferenceDataService;

class YardObserver
{
    public function created(Yard $yard): void
    {
        ReferenceDataService::clearYardsCache();
    }

    public function updated(Yard $yard): void
    {
        ReferenceDataService::clearYardsCache();
    }

    public function deleted(Yard $yard): void
    {
        ReferenceDataService::clearYardsCache();
    }

    public function restored(Yard $yard): void
    {
        ReferenceDataService::clearYardsCache();
    }

    public function forceDeleted(Yard $yard): void
    {
        ReferenceDataService::clearYardsCache();
    }
}
