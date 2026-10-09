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
use Illuminate\Support\Facades\DB;
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
            'value' => 'required|numeric|min:0.01',
            'image' => 'nullable|mimes:jpg,jpeg,png'
        ]);

        if ($request->hasFile('image')) {
            $imageName = time() . '_transaction.' . $request->image->extension();
            $this->uploadImage($request->image, $imageName, 'banks');
            $data['image'] = 'Admin/images/banks/' .  $imageName;
        }

        try {
            DB::transaction(function () use ($request, $data) {
                $policy = DeliveryPolicy::lockForUpdate()->findOrFail($request->delivery_policy_id);
                $vault = Vault::lockForUpdate()->firstOrFail();

                // حساب المتبقي للسداد: البوليصة (cost) والحوالة (money_transfer) لا تُخصم من السداد
                // فقط المصروف الإضافي (extraExpenses) يُحسب
                $extraExpensesTotal = $policy->extraExpenses()->sum('value');
                $paidTotal = $policy->payingCars()->sum('value');

                if ($policy->cost) {
                    $calc = $policy->cost - $paidTotal;
                } else {
                    $calc = $extraExpensesTotal - $paidTotal;
                }

                if ((float) $request->value > (float) $calc) {
                    throw new \DomainException(__('Delivery Policy is less than your money'));
                }

                if ((float) $vault->amount < (float) $request->value) {
                    throw new \DomainException(__('main.car_wallet_does_not_have_enough_amount'));
                }

                $data['user_id'] = auth()->user()->id;
                $data['car_id'] = $policy->car_id;

                // سجل معاملة السداد (منصرف)
                VaultTransaction::create([
                    'name' => 'سداد سياره',
                    'amount' => $request->value,
                    'type' => 0
                ]);

                $paying = Payingcar::create($data);

                $transaction = [];
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
                if ($extraExpensesTotal > 0) {
                    $remainingExtraExpenses = $extraExpensesTotal - $paidTotal;

                    if ($remainingExtraExpenses > 0) {
                        $extraExpenseToAdd = min($request->value, $remainingExtraExpenses);

                        if ($extraExpenseToAdd > 0) {
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
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'تعذر حفظ السداد: ' . $e->getMessage());
        }

        return back()->with('success', __('alerts.added_successfully'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'value' => 'required|numeric|min:0.01',
            'image' => 'nullable|mimes:jpg,jpeg,png'
        ]);

        try {
            DB::transaction(function () use ($request, $id, $data) {
                $paying = Payingcar::lockForUpdate()->findOrFail($id);
                $policy = DeliveryPolicy::lockForUpdate()->findOrFail($paying->delivery_policy_id);
                $vault = Vault::lockForUpdate()->firstOrFail();

                $oldValue = (float) $paying->value;
                $newValue = (float) $request->value;

                $extraExpensesTotal = $policy->extraExpenses()->sum('value');
                $paidTotalBeforeUpdate = $policy->payingCars()->sum('value');
                $paidTotalAfterUpdate = $paidTotalBeforeUpdate - $oldValue + $newValue;

                if ($policy->cost) {
                    $calc = $policy->cost - ($paidTotalAfterUpdate - $newValue);
                } else {
                    $calc = $extraExpensesTotal - ($paidTotalAfterUpdate - $newValue);
                }

                if ($newValue > $calc) {
                    throw new \DomainException(__('Delivery Policy is less than your money'));
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
                        if ((float) $vault->amount < $valueDiff) {
                            throw new \DomainException(__('main.car_wallet_does_not_have_enough_amount'));
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
                    $remainingExtraExpensesBeforeUpdate = $extraExpensesTotal - ($paidTotalBeforeUpdate - $oldValue);
                    $extraExpenseAddedBeforeUpdate = min($oldValue, $remainingExtraExpensesBeforeUpdate);

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
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'تعذر تحديث السداد: ' . $e->getMessage());
        }

        return back()->with('success', __('alerts.updated_successfully'));
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $paying = Payingcar::lockForUpdate()->findOrFail($id);
                $policy = DeliveryPolicy::lockForUpdate()->findOrFail($paying->delivery_policy_id);
                $vault = Vault::lockForUpdate()->firstOrFail();

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
                $extraExpensesTotal = $policy->extraExpenses()->sum('value');
                $paidTotalBeforeDelete = $policy->payingCars()->sum('value');
                $paidTotalAfterDelete = $paidTotalBeforeDelete - $paying->value;

                if ($extraExpensesTotal > 0) {
                    $remainingExtraExpensesBeforeThisPayment = $extraExpensesTotal - ($paidTotalBeforeDelete - $paying->value);
                    $extraExpenseAddedAtPayment = min($paying->value, $remainingExtraExpensesBeforeThisPayment);

                    if ($extraExpenseAddedAtPayment > 0) {
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
                    @unlink(public_path($paying->image));
                }

                $paying->delete();
            });
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'msg' => 'تعذر إلغاء السداد: ' . $e->getMessage()], 422);
        }

        return response()->json(['status' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
