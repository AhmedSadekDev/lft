<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtraExpense;
use App\Models\BookingContainer;
use App\Models\BookingContrainerExtraCosts;
use App\Models\Car;
use App\Models\Driver;
use App\Models\Vault;
use App\Models\VaultTransaction;
use Illuminate\Http\Request;

class BookingContaBookingContrainerExtraCostsController extends Controller
{
    public function index($container)
    {
        $booking_container = BookingContainer::findOrFail($container);

        $expenses = $booking_container->extraExpenses;


        return view('admin.extra_expenses.index', compact('expenses', 'booking_container'));
    }


    public function create($container)
    {
        $bookingContainer = BookingContainer::findOrFail($container);
        $booking_containers = BookingContainer::all();
        $cars = Car::all();
        $drivers = Driver::all();

        return view('admin.extra_expenses.create', compact('bookingContainer', 'booking_containers', 'cars', 'drivers'));
    }



    public function edit($id)
    {
        $expense = BookingContrainerExtraCosts::findOrfail($id);
        $bookingContainer = BookingContainer::findOrFail($expense->booking_container_id);
        $booking_containers = BookingContainer::all();
        $cars = Car::all();
        $drivers = Driver::all();

        return view('admin.extra_expenses.edit', compact('expense', 'bookingContainer', 'booking_containers', 'cars', 'drivers'));
    }


    public function store(StoreExtraExpense $request)
    {
        $vault = Vault::first();

        if ($vault->amount < $request->value) {
            return back()->with('error', __("main.wallet_does_not_have_enough_amount"));
        }
        
        VaultTransaction::create([
            'name' => $request->name,
            'amount' => $request->value,
            'type' => 0
        ]);

        $container = BookingContainer::find($request->booking_container_id);
        $data = $request->all();
        $data['delivery_policy_id'] = $container->delivery_policies->first()->id;
        BookingContrainerExtraCosts::create($data);

        $id = BookingContainer::find($request->booking_container_id)->booking_id;

        return back()->with('success', __('alerts.added_successfully'));
    }



    public function update(StoreExtraExpense $request, $id)
    {
        $expense = BookingContrainerExtraCosts::find($id);
        
        $expense->update($request->all());

        $id = BookingContainer::find($request->booking_container_id)->booking_id;

        return back()->with('success', __('alerts.added_successfully'));
    }


    public function destroy($id)
    {
        $expense = BookingContrainerExtraCosts::find($id);
        $expense->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
