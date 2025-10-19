<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ShipmentExport;
use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Agent;
use App\Models\DeliveryPolicy;
use App\Http\Traits\ImagesTrait;
use Illuminate\Support\Collection;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ShipmentController extends Controller
{
    use ImagesTrait;

    public function __construct()
    {
        $this->middleware('permission:shipments.index')->only('index');
        $this->middleware('permission:shipments.create')->only(['create', 'store']);
        $this->middleware('permission:shipments.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:shipments.delete')->only('destroy');
    }
    public function index(Request $request, $id)
    {
        $car = Car::findOrfail($id);
        $shipments = DeliveryPolicy::query();

        if ($request->filled('date_from')) {
            $shipments->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $shipments->whereDate('created_at', '<=', $request->date_to);
        }

        $shipments = $shipments->where('car_id', $id)->get();
        
        
        // Create an empty collection to store unique shipments and their total money transfer values
        $uniqueShipments = collect();
        $totalMoneyTransferByBookingContainer = collect();
        
        // Create a set to track booking container IDs that have been seen
        $seenBookingContainerIds = collect();
        
        // Loop through the shipments collection
        foreach ($shipments as $shipment) {
            // Get the booking_containers for the current shipment
            $bookingContainers = $shipment->booking_containers;
            $bookingContainerIds = $bookingContainers->pluck('id')->sort()->values()->toJson();
        
            // Flag to check if all booking containers in this shipment are unique
            $isUniqueShipment = true;
        
            // Loop through the booking_containers
            foreach ($bookingContainers as $bookingContainer) {
                // Check if the bookingContainer ID already exists in the seen set
                if ($seenBookingContainerIds->contains($bookingContainer->id)) {
                    $isUniqueShipment = false;
                    break; // No need to check further if we found a duplicate
                }
            }
        
            // If the shipment is unique, add it to the uniqueShipments collection
            if ($isUniqueShipment) {
                $uniqueShipments->push($shipment);
        
                // Add the bookingContainer IDs to the seen set
                foreach ($bookingContainers as $bookingContainer) {
                    $seenBookingContainerIds->push($bookingContainer->id);
                }
        
                // Initialize the total for this set of booking containers if it doesn't exist
                if (!$totalMoneyTransferByBookingContainer->has($bookingContainerIds)) {
                    $totalMoneyTransferByBookingContainer->put($bookingContainerIds, 0);
                }
        
                // Add the money_transfer value to the total for this set of booking containers
                $totalMoneyTransferByBookingContainer[$bookingContainerIds] += $shipment->money_transfer->value;
            } else {
                // If the shipment is not unique, still add its money_transfer value to the total of the corresponding set of booking containers
                if ($totalMoneyTransferByBookingContainer->has($bookingContainerIds)) {
                    $totalMoneyTransferByBookingContainer[$bookingContainerIds] += $shipment->money_transfer->value;
                }
            }
        }
        
        $shipments = $uniqueShipments; 

        return view('admin.shipments.index', compact('car', 'shipments', 'totalMoneyTransferByBookingContainer'));
    }
    
    
    public function payments(Request $request, $id)
    {
        $car = Car::findOrfail($id);
        $shipments = DeliveryPolicy::query();

        if ($request->filled('date_from')) {
            $shipments->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $shipments->whereDate('date', '<=', $request->date_to);
        }

        $shipments = $shipments->where('car_id', $id)->get();
        
        return view('admin.shipments.payments', compact('car', 'shipments'));
    }

    public function export(Request $request)
    {

        return Excel::download(new ShipmentExport($request->from, $request->to, $request->car_id), 'shipments.xlsx');
    }

    public function create($id)
    {
        $car = Car::findOrfail($id);
        $agents = Agent::all();

        return view('admin.shipments.create', compact('car', 'agents'));
    }


    public function edit($id)
    {
        $shipment = Shipment::findOrfail($id);
        $agents = Agent::all();

        return view('admin.shipments.edit', compact('shipment', 'agents'));
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'agent_id' => 'required|exists:agents,id',
            'name' => 'required|string|max:255',
            'value' => 'required|numeric',
            'date' => 'required|date',
            'addition' => 'nullable|numeric'
        ]);
        $data['user_id'] = auth()->user()->id;
        
        if($request->hasFile('image')) {
            $imageName = time() . '_transaction.' . $request->image->extension();
            $this->uploadImage($request->image, $imageName, 'banks');
            $data['image'] = 'Admin/images/banks/' .  $imageName;
        }

        $car = Car::findOrFail($request->car_id);
        $agent = Agent::findOrFail($request->agent_id);
        $value = $request->value + $request->addition;
        if ($agent->wallet >= $value) {
        $agent->update([
            'wallet' => $agent->wallet - $value
        ]);

        if ($data['addition'] == null) {
            $data['addition'] = 0;
        }
        Shipment::create($data);

            return to_route('shipments.index', $request->car_id)->with('success', __('alerts.added_successfully'));
        }

        return to_route('shipments.index', $request->car_id)->with('error', __('main.car_wallet_does_not_have_enough_amount'));
    }

    public function update(Request $request, $id)
    {
        $shipment = DeliveryPolicy::findOrFail($id);
        $shipment->update($request->all());;

        return back()->with('success', __('alerts.updated_successfully'));
    }


    public function destroy($id)
    {
        $shipment = Shipment::findOrFail($id);

        $shipment->delete();

        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
