<?php

namespace App\Services;

use App\Models\BookingContainer;
use App\Models\CompanyTransportation;

/**
 * Resolve trip price from company_transportations for a booking container.
 * Must always scope by the booking's company_id so tariffs never cross clients
 * (e.g. النساجون 14500 vs تشيك بوينت 18000 on the same Damietta→10th route).
 */
class CompanyTransportationPriceResolver
{
    /**
     * Find the matching tariff row for this container's company + route + size.
     */
    public function findForContainer(BookingContainer $container): ?CompanyTransportation
    {
        $container->loadMissing('booking');

        $companyId = $container->booking?->company_id;
        if (! $companyId) {
            return null;
        }

        if (! $container->container_id || ! $container->departure_id || ! $container->loading_id || ! $container->aging_id) {
            return null;
        }

        return CompanyTransportation::query()
            ->where('company_id', $companyId)
            ->where('container_id', $container->container_id)
            ->where('departure_id', $container->departure_id)
            ->where('loading_id', $container->loading_id)
            ->where('aging_id', $container->aging_id)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Apply tariff price onto the container when a company-scoped match exists.
     * Leaves price unchanged when no row matches (does not force zero).
     *
     * @return float|null applied price, or null if no match
     */
    public function applyToContainer(BookingContainer $container): ?float
    {
        $tariff = $this->findForContainer($container);
        if (! $tariff) {
            return null;
        }

        $price = (float) $tariff->price;
        $container->update(['price' => $price]);

        return $price;
    }
}
