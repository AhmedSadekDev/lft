<?php

namespace App\Http\Controllers\Admin;

use App\Models\Car;
use App\Models\Vault;
use App\Models\VaultTransaction;
use App\Models\Payingcar;
use Illuminate\Http\Request;
use App\Models\MoneyTransfer;
use App\Exports\PaingCarExport;
use App\Http\Traits\ImagesTrait;
use App\Models\BookingContainer;
use App\Http\Controllers\Controller;
use App\Models\DeliveryPolicy;
use App\Models\Shipment;
use Maatwebsite\Excel\Facades\Excel;

class CarPayingController extends Controller
{
    use ImagesTrait;

    public function index(Request $request, $id)
    {
        $policy = DeliveryPolicy::find($id);

        // Initialize the query for moneyTransfers
        $paymentsQuery = $policy->payingCars();

        if ($request->filled('date_from')) {
            $paymentsQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $paymentsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // Execute the query to get the filtered payments
        $payments = $paymentsQuery->get();

        return view('admin.payments.index', compact('payments', 'policy'));
    }

    public function export(Request $request, $id)
    {

        $ids = explode(',', $request->ids);
        return Excel::download(new PaingCarExport($ids), 'payments.xlsx');
    }

    public function create(Request $request)
    {
        $car = Car::findOrFail($request->car_id)->id;
        $bookingContainer = BookingContainer::findOrFail($request->booking_container_id)->id;

        return view('admin.payments.create', compact('car', 'bookingContainer'));
    }


    public function edit($id)
    {
        // $shipment = Shipment::findOrfail($id);
        // $agents = Agent::all();

        return view('admin.payments.edit', compact('shipment', 'agents'));
    }


    public function store(Request $request)
    {

        $data = $request->validate([
            'delivery_policy_id' => 'required|exists:delivery_policies,id',
            'value' => 'required|numeric',
            'image' => 'nullable|mimes:jpg,jpeg,png'
        ]);

        $policy = DeliveryPolicy::find($request->delivery_policy_id);

        // حساب المتبقي للسداد: البوليصة (cost) والحوالة (money_transfer) لا تُخصم من السداد
        // فقط المصروف الإضافي (extraExpenses) يُحسب
        $extraExpensesTotal = $policy->extraExpenses->sum('value');
        $paidTotal = $policy->payingCars->sum('value');

        if ($policy->cost) {
            // إذا كان cost موجود: المتبقي = cost - المدفوع
            $calc = $policy->cost - $paidTotal;
        } else {
            // إذا لم يكن cost موجود: المتبقي = المصروف الإضافي فقط (بدون money_transfer)
            $calc = $extraExpensesTotal - $paidTotal;
        }

        if ($request->value > ($calc)) {
            return back()->with('error', __('Delivery Policy is less than your money'));
        }

        $data['user_id'] = auth()->user()->id;

        $data['car_id'] = $policy->car_id;

        if ($request->hasFile('image')) {
            $imageName = time() . '_transaction.' . $request->image->extension();
            $this->uploadImage($request->image, $imageName, 'banks');
            $data['image'] = 'Admin/images/banks/' .  $imageName;
        }

        $vault = Vault::first();

        // حساب المبلغ المطلوب: قيمة السداد فقط (بدون المصروف الإضافي)
        if ($vault->amount < $request->value) {
            return back()->with('error', __('main.car_wallet_does_not_have_enough_amount'));
        }

        // سجل معاملة السداد (منصرف)
        VaultTransaction::create([
            'name' => 'سداد سياره',
            'amount' => $request->value,
            'type' => 0
        ]);

        $paying = Payingcar::create($data);

        $transaction["value"] = $request->value;
        $transaction["transfered_type"] = "App\Models\Payingcar";
        $transaction["transfered_id"] = $paying->id;
        $transaction["transferer_type"] = "App\Models\User";
        $transaction["transferer_id"] = auth()->user()->id;

        MoneyTransfer::create($transaction);

        // خصم قيمة السداد من الخزنة
        $vault->update([
            'amount' => $vault->amount - $request->value
        ]);

        // إضافة المصروف الإضافي للخزنة (وارد) عند السداد
        // يتم إضافة المصروف الإضافي فقط عند السداد، وليس عند إنشاء المصروف
        if ($extraExpensesTotal > 0) {
            // حساب المصروف الإضافي غير المدفوع بعد
            $remainingExtraExpenses = $extraExpensesTotal - $paidTotal;

            // إذا كان هناك مصروف إضافي غير مدفوع، أضف جزء منه للخزنة
            if ($remainingExtraExpenses > 0) {
                // المبلغ الذي يُضاف = الحد الأدنى بين قيمة السداد والمصروف الإضافي المتبقي
                $extraExpenseToAdd = min($request->value, $remainingExtraExpenses);

                if ($extraExpenseToAdd > 0) {
                    // إضافة المصروف الإضافي للخزنة
                    VaultTransaction::create([
                        'name' => 'مصروف إضافي - بوليصة ' . $policy->id,
                        'amount' => $extraExpenseToAdd,
                        'type' => 1 // وارد
                    ]);

                    $vault->update([
                        'amount' => $vault->amount + $extraExpenseToAdd
                    ]);
                }
            }
        }

        return back()->with('success', __('alerts.added_successfully'));
    }

    public function update(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'agent_id' => 'required|exists:agents,id',
            'value' => 'required|numeric',
            'date' => 'required|date',
            'addition' => 'nullable|numeric'
        ]);
        $data['user_id'] = auth()->user()->id;


        if ($request->hasFile('image')) {
            $imageName = time() . '_transaction.' . $request->image->extension();
            $this->uploadImage($request->image, $imageName, 'banks', $shipment->image);
            $data['image'] = 'Admin/images/banks/' .  $imageName;
        }


        $oldValue = $shipment->value + $shipment->addition;
        $newValue = $request->value + $request->addition;
        $car = $shipment->car;
        $agent = $shipment->agent;

        if ($newValue > $oldValue) {
            $diff = $newValue - $oldValue;

            if ($agent->wallet - $diff < 0) {
                return to_route('payments.index', $car->id)->with('error', __('main.car_wallet_does_not_have_enough_amount'));
            }

            $agent->update([
                'wallet' => $agent->wallet - $diff
            ]);
        }

        if ($oldValue > $newValue) {
            $diff = $oldValue - $newValue;
            $agent->update([
                'wallet' => $agent->wallet + $diff
            ]);
        }

        $shipment->update($data);
        return to_route('payments.index', $shipment->car_id)->with('success', __('alerts.updated_successfully'));
    }


    public function destroy($id)
    {
        $shipment = Shipment::findOrFail($id);

        $shipment->delete();

        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
