<?php

namespace App\Observers;

use App\Models\shippingAgent;
use App\Services\ReferenceDataService;

class ShippingAgentObserver
{
    public function created(shippingAgent $shippingAgent): void
    {
        ReferenceDataService::clearShippingAgentsCache();
    }

    public function updated(shippingAgent $shippingAgent): void
    {
        ReferenceDataService::clearShippingAgentsCache();
    }

    public function deleted(shippingAgent $shippingAgent): void
    {
        ReferenceDataService::clearShippingAgentsCache();
    }

    public function restored(shippingAgent $shippingAgent): void
    {
        ReferenceDataService::clearShippingAgentsCache();
    }

    public function forceDeleted(shippingAgent $shippingAgent): void
    {
        ReferenceDataService::clearShippingAgentsCache();
    }
}
