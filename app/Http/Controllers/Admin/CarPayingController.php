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
        $paying = Payingcar::findOrFail($id);
        return view('admin.payments.edit', compact('paying'));
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
        $paying = Payingcar::findOrFail($id);
        $policy = $paying->delivery_policy;
        $vault = Vault::first();

        $data = $request->validate([
            'value' => 'required|numeric',
            'image' => 'nullable|mimes:jpg,jpeg,png'
        ]);

        $oldValue = $paying->value;
        $newValue = $request->value;

        // حساب المتبقي للسداد بعد التحديث
        $extraExpensesTotal = $policy->extraExpenses->sum('value');
        $paidTotalBeforeUpdate = $policy->payingCars->sum('value');
        $paidTotalAfterUpdate = $paidTotalBeforeUpdate - $oldValue + $newValue;

        if ($policy->cost) {
            $calc = $policy->cost - ($paidTotalAfterUpdate - $newValue);
        } else {
            $calc = $extraExpensesTotal - ($paidTotalAfterUpdate - $newValue);
        }

        if ($newValue > $calc) {
            return back()->with('error', __('Delivery Policy is less than your money'));
        }

        // تحديث الصورة إن وجدت
        if ($request->hasFile('image')) {
            $imageName = time() . '_transaction.' . $request->image->extension();
            $this->uploadImage($request->image, $imageName, 'banks', $paying->image);
            $data['image'] = 'Admin/images/banks/' .  $imageName;
        }

        // تحديث قيمة الخزنة
        $valueDiff = $newValue - $oldValue;
        if ($valueDiff != 0) {
            if ($valueDiff > 0) {
                // زيادة السداد - خصم من الخزنة
                if ($vault->amount < $valueDiff) {
                    return back()->with('error', __('main.car_wallet_does_not_have_enough_amount'));
                }

                VaultTransaction::create([
                    'name' => 'تحديث سداد سياره - ' . $paying->id,
                    'amount' => $valueDiff,
                    'type' => 0 // منصرف
                ]);

                $vault->update([
                    'amount' => $vault->amount - $valueDiff
                ]);
            } else {
                // تقليل السداد - إضافة للخزنة
                VaultTransaction::create([
                    'name' => 'تحديث سداد سياره - ' . $paying->id,
                    'amount' => abs($valueDiff),
                    'type' => 1 // وارد
                ]);

                $vault->update([
                    'amount' => $vault->amount + abs($valueDiff)
                ]);
            }
        }

        // تحديث المصروف الإضافي
        if ($extraExpensesTotal > 0) {
            // حساب المصروف الإضافي قبل التحديث
            $remainingExtraExpensesBeforeUpdate = $extraExpensesTotal - ($paidTotalBeforeUpdate - $oldValue);
            $extraExpenseAddedBeforeUpdate = min($oldValue, $remainingExtraExpensesBeforeUpdate);

            // حساب المصروف الإضافي بعد التحديث
            $remainingExtraExpensesAfterUpdate = $extraExpensesTotal - ($paidTotalAfterUpdate - $newValue);
            $extraExpenseAddedAfterUpdate = min($newValue, $remainingExtraExpensesAfterUpdate);

            $extraExpenseDiff = $extraExpenseAddedAfterUpdate - $extraExpenseAddedBeforeUpdate;

            if ($extraExpenseDiff != 0) {
                if ($extraExpenseDiff > 0) {
                    // زيادة المصروف الإضافي - إضافة للخزنة
                    VaultTransaction::create([
                        'name' => 'تحديث مصروف إضافي - بوليصة ' . $policy->id,
                        'amount' => $extraExpenseDiff,
                        'type' => 1 // وارد
                    ]);

                    $vault->update([
                        'amount' => $vault->amount + $extraExpenseDiff
                    ]);
                } else {
                    // تقليل المصروف الإضافي - خصم من الخزنة
                    VaultTransaction::create([
                        'name' => 'تحديث مصروف إضافي - بوليصة ' . $policy->id,
                        'amount' => abs($extraExpenseDiff),
                        'type' => 0 // منصرف
                    ]);

                    $vault->update([
                        'amount' => $vault->amount - abs($extraExpenseDiff)
                    ]);
                }
            }
        }

        // تحديث MoneyTransfer
        $moneyTransfer = MoneyTransfer::where('transfered_type', 'App\Models\Payingcar')
            ->where('transfered_id', $paying->id)
            ->first();

        if ($moneyTransfer) {
            $moneyTransfer->update([
                'value' => $newValue
            ]);
        }

        $paying->update($data);

        return back()->with('success', __('alerts.updated_successfully'));
    }


    public function destroy($id)
    {
        $paying = Payingcar::findOrFail($id);
        $policy = $paying->delivery_policy;
        $vault = Vault::first();

        // إرجاع قيمة السداد للخزنة
        $vault->update([
            'amount' => $vault->amount + $paying->value
        ]);

        // سجل معاملة إرجاع السداد (وارد)
        VaultTransaction::create([
            'name' => 'إلغاء سداد سياره - ' . $paying->id,
            'amount' => $paying->value,
            'type' => 1 // وارد
        ]);

        // إعادة حساب المصروف الإضافي وإرجاعه من الخزنة إذا لزم الأمر
        $extraExpensesTotal = $policy->extraExpenses->sum('value');
        $paidTotalBeforeDelete = $policy->payingCars->sum('value');
        $paidTotalAfterDelete = $paidTotalBeforeDelete - $paying->value;

        if ($extraExpensesTotal > 0) {
            // حساب المصروف الإضافي غير المدفوع قبل إضافة هذا السداد
            $remainingExtraExpensesBeforeThisPayment = $extraExpensesTotal - ($paidTotalBeforeDelete - $paying->value);
            // حساب المصروف الإضافي غير المدفوع بعد الحذف
            $remainingExtraExpensesAfterDelete = $extraExpensesTotal - $paidTotalAfterDelete;

            // الفرق هو المصروف الإضافي الذي كان قد أُضيف للخزنة عند هذا السداد
            // عند السداد: $extraExpenseToAdd = min($paying->value, $remainingExtraExpensesBeforeThisPayment)
            $extraExpenseAddedAtPayment = min($paying->value, $remainingExtraExpensesBeforeThisPayment);

            if ($extraExpenseAddedAtPayment > 0) {
                // إرجاع المصروف الإضافي من الخزنة
                VaultTransaction::create([
                    'name' => 'إلغاء مصروف إضافي - بوليصة ' . $policy->id,
                    'amount' => $extraExpenseAddedAtPayment,
                    'type' => 0 // منصرف
                ]);

                $vault->update([
                    'amount' => $vault->amount - $extraExpenseAddedAtPayment
                ]);
            }
        }

        // حذف MoneyTransfer المرتبط
        MoneyTransfer::where('transfered_type', 'App\Models\Payingcar')
            ->where('transfered_id', $paying->id)
            ->delete();

        // حذف الصورة إن وجدت
        if ($paying->image && file_exists(public_path($paying->image))) {
            unlink(public_path($paying->image));
        }

        $paying->delete();

        return response()->json(['status' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
