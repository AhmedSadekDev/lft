<?php

namespace App\Exports;

use App\Models\DeliveryPolicy;
use Maatwebsite\Excel\Concerns\FromCollection;

class CarTransactionExport implements FromCollection
{

    public $ids;

    public function __construct($ids)
    {
        $this->ids = $ids;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $shipments = DeliveryPolicy::whereIn('id', $this->ids)->get();

        // Create a collection to store grouped shipments by booking container IDs
        $groupedShipments = $shipments->groupBy(function ($shipment) {
            // Generate a unique key for the booking containers of this shipment
            return $shipment->booking_containers->pluck('id')->sort()->values()->toJson();
        });

        // Create a collection to store the final results
        $results = collect();

        // Loop through the grouped shipments to calculate the total money transfer for each group
        foreach ($groupedShipments as $bookingContainerIds => $group) {
            $totalMoneyTransfer = $group->sum(function ($shipment) {
                return $shipment->money_transfer->value;
            });

            foreach ($group as $shipment) {
                $results->push([
                    'id' => $shipment->id,
                    'car_number' => $shipment->car->car_number,
                    'value' => $totalMoneyTransfer, // Total value for the group
                    'date' => $shipment->created_at,
                ]);
            }
        }

        return $results;
    }
}
