<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtraExpense;
use App\Models\BookingContainer;
use App\Models\BookingContrainerExtraCosts;
use App\Models\Car;
use App\Models\Driver;
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
        $bookingContainer = BookingContainer::with('booking.invoice')->findOrFail($container);
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
        // لا يتم خصم المبلغ من الخزنة عند إضافة المصروف الإضافي
        // سيتم الخصم فقط عند السداد (payment)

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
