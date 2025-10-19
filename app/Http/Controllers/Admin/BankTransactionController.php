<?php

namespace App\Http\Controllers\Admin;

use App\Models\Bank;
use App\Models\Vault;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Models\BankTrnsaction;
use App\Models\companyInvoices;
use App\Http\Traits\ImagesTrait;
use App\Models\VaultTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BankTransactionExport;

class BankTransactionController extends Controller
{
    use ImagesTrait;

    public function __construct()
    {
        $this->middleware('permission:bank.index')->only('index');
        $this->middleware('permission:bank.create')->only(['create', 'store']);
        $this->middleware('permission:bank.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:bank.delete')->only('destroy');
    }
    public function index(Request $request, $id)
    {
        $bank = Bank::findOrfail($id);
        $banktransactions = BankTrnsaction::query();

        if ($request->filled('date_from')) {
            $banktransactions->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $banktransactions->whereDate('created_at', '<=', $request->date_to);
        }

        $banktransactions = $banktransactions->where('bank_id', $id)->get();

        return view('admin.banktransactions.index', compact('bank', 'banktransactions'));
    }

    public function export(Request $request, $id)
    {

        $ids = explode(',', $request->ids);
        return Excel::download(new BankTransactionExport($ids), 'transactions.xlsx');
    }

    public function create($id)
    {
        $bank = Bank::findOrfail($id);
        $banks = Bank::where('id', '!=', $id)->get();
        $companies  = Company::all();

        return view('admin.banktransactions.create', compact('bank', 'banks', 'companies'));
    }


    public function edit($id)
    {
        $item = BankTrnsaction::findOrfail($id);

        $banks = Bank::where('id', '!=', $id)->get();

        $companies  = Company::all();

        return view('admin.banktransactions.edit', compact('item', 'banks', 'companies'));
    }



    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'date' => 'nullable',
            'type' => 'required|in:0,1,2',
            'trans_bank_id' => 'required_if:type,2|exists:banks,id',
            'company_id' => 'required_if:type,1|exists:companies,id',
            'image' => 'nullable|mimes:jpg,png,jpeg', // Changed to nullable to handle cases without images
        ]);
        $data['user_id'] = auth()->user()->id;
    
        DB::beginTransaction();
    
        try {
            $bank = Bank::findOrFail($request->bank_id);
            $vault = Vault::firstOrFail();
            // Handle image upload
            if ($request->hasFile('image')) {
                $imageName = time() . '_transaction.' . $request->image->extension();
                $imagePath = $request->type == 1 ? 'companyInvoice' : 'banks';
                $this->uploadImage($request->image, $imageName, $imagePath);
                $data['image'] = "Admin/images/{$imagePath}/{$imageName}";
            }
            switch ($request->type) {
                case 2:
                    $transBank = Bank::findOrFail($request->trans_bank_id);
    
                    $bank->amount -= $request->amount;
                    $transBank->amount += $request->amount;
    
                    $transBank->save();
                    break;
    
                case 0:
                    if ($bank->amount < $request->amount) {
                        DB::rollBack();
                        INFO('bank_wallet_does_not_have_enough_amount');
                        return redirect()->back()->with('error', __('main.bank_wallet_does_not_have_enough_amount'));
                    }
    
                    $bank->amount -= $request->amount;
                    $vault->amount += $request->amount;
    
                    $vault->save();
                    VaultTransaction::create([
                        'bank_id' => $bank->id,
                        'name' => $request->name,
                        'amount' => $request->amount,
                        'type' => 0
                    ]);
    
                    BankTrnsaction::create([
                        'bank_id' => $bank->id,
                        'name' => $request->name,
                        'date' => $request->date,
                        'amount' => $request->amount,
                        'type' => $request->type,
                        'user_id' => auth()->user()->id,
                        'image' => $data['image'] ?? null // Use null if image is not set
                    ]);
                    break;
    
                case 1:
                    $company = Company::findOrFail($request->company_id);
                    
                    $company->wallet -= $request->amount;
                    $company->save();
    
                    // Distribute the amount across invoices
                    $totalAmountToDistribute = $request->amount;
    
                    // Calculate total remaining amounts for all invoices
                    $totalRemaining = 0;
                    foreach ($company->bookings as $booking) {
                        $invoice = $booking->invoice;
                        $invoiceTotalBeforeTax = $invoice->invoice_total_before_tax;
                        $vatValue = $invoice->value_added_tax_amount;
                        $saleValue = $invoice->sales_tax_amount;
                        $discountValue = $invoice->discount_amount;
                        
                        $taxedServicesTotal = $invoice->taxed_services_total_before_vat ?? 0;
                        $untaxedServicesTotal = $invoice->untaxed_services_total_before_vat ?? 0;
                        $transportationTotal = $invoice->transportation_total_before_vat ?? 0;
                    
                        $finalValue = $invoiceTotalBeforeTax + $taxedServicesTotal + $untaxedServicesTotal + $vatValue - $saleValue - $discountValue;
                        $remain = $finalValue - ($invoice->invoicePayments->sum('value') ?? 0);
                        
                        // Sum the total remaining amount
                        $totalRemaining += $remain;
                    }
    
                    // Check if the amount to distribute is less than or equal to the total remaining
                    if ($totalAmountToDistribute > $totalRemaining) {
                        return back()->with('error', 'المبلغ المتبقى '. $totalRemaining);
                    }
    
                    // Distribute the amount across invoices
                    foreach ($company->bookings as $booking) {
                        $invoice = $booking->invoice;
                        $invoiceTotalBeforeTax = $invoice->invoice_total_before_tax;
                        $vatValue = $invoice->value_added_tax_amount;
                        $saleValue = $invoice->sales_tax_amount;
                        $discountValue = $invoice->discount_amount;
                        
                        $taxedServicesTotal = $invoice->taxed_services_total_before_vat ?? 0;
                        $untaxedServicesTotal = $invoice->untaxed_services_total_before_vat ?? 0;
                        $transportationTotal = $invoice->transportation_total_before_vat ?? 0;
                    
                        $finalValue = $invoiceTotalBeforeTax + $taxedServicesTotal + $untaxedServicesTotal + $vatValue - $saleValue - $discountValue;
                        $remain = $finalValue - ($invoice->invoicePayments->sum('value') ?? 0);
                        
                        if ($remain > 0) {
                            $amountToPay = min($remain, $totalAmountToDistribute);
                            $totalAmountToDistribute -= $amountToPay;
    
                            // Create invoice payment
                            $paymentData = [
                                'value' => $amountToPay,
                                'image' => $data['image'] ?? null // Ensure this is correctly set
                            ];
    
                            $invoice->invoicePayments()->create($paymentData);
                            
                            if ($totalAmountToDistribute <= 0) {
                                break;
                            }
                        }
                    }
    
                    $bank->amount += $request->amount;
    
                    // Adding transaction record for type 1
                    BankTrnsaction::create([
                        'bank_id' => $bank->id,
                        'name' => $request->name,
                        'amount' => $request->amount,
                        'date' => $request->date,
                        'type' => $request->type,
                        'user_id' => auth()->user()->id,
                        'image' => $data['image'] ?? null
                    ]);
    
                    // Create company invoice if type is 1
                    $company->companyInvoices()->create([
                        'image' => $data['image'] ?? null,
                        'total' => $request->amount,
                        'user_id' => $data['user_id'],
                        'type' => 0
                    ]);
                    break;
            }
    
            $bank->save();
    
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction failed: ' . $e->getMessage());
            return redirect()->back()->with('error', __('main.transaction_failed') . ': ' . $e->getMessage());
        }
    
        return redirect()->route('banktransactions.index', $request->bank_id)->with('success', __('alerts.added_successfully'));
    }






    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'date' => 'nullable',
            'type' => 'required|in:0,1',
            'image' => 'nullable|mimes:jpg,png,jpeg',
        ]);

        $data['user_id'] = auth()->user()->id;

        $transaction = BankTrnsaction::findOrFail($id);
        $vault = Vault::first();
        $originalAmount = $transaction->amount;
        $newAmount = $request->amount;
        $amountDifference = $newAmount - $originalAmount;

        $bank = Bank::findOrFail($transaction->bank_id);
        $transBank = $request->type == 2 ? Bank::findOrFail($request->trans_bank_id) : null;

        DB::beginTransaction();

        try {
            $transaction = BankTrnsaction::findOrFail($id);
            $originalAmount = $transaction->amount;
            $newAmount = $request->amount;
            $amountDifference = $newAmount - $originalAmount;

            $bank = Bank::findOrFail($transaction->bank_id);
            $transBank = $request->type == 2 ? Bank::findOrFail($request->trans_bank_id) : null;
            $company = $request->type == 1 ? Company::findOrFail($request->company_id) : null;
            $vault = Vault::first(); // Modify this to get the correct vault

            if ($request->type == 0) { // Withdraw transaction
                if ($newAmount > $originalAmount) {
                    $bank->amount -= $amountDifference;
                    $vault->amount += $amountDifference;

                    VaultTransaction::create([
                        'bank_id'  => $bank->id,
                        'name' => $request->name,
                        'amount' => $amountDifference,
                        'type' => 1
                    ]);
                } else {
                    $bank->amount += abs($amountDifference);
                    $vault->amount -= abs($amountDifference);

                    VaultTransaction::create([
                        'bank_id'  => $bank->id,
                        'name' => $request->name,
                        'amount' => $amountDifference,
                        'type' => 0
                    ]);
                }
            } elseif ($request->type == 1) { // Deposit transaction
                if ($newAmount > $originalAmount) {
                    $bank->amount += $amountDifference;
                    $company->wallet -= $amountDifference;
                } else {
                    $bank->amount -= abs($amountDifference);
                    $company->wallet += $amountDifference;
                }
            } elseif ($request->type == 2) { // Transfer to another bank
                if ($newAmount > $originalAmount) {
                    $bank->amount -= $amountDifference;
                    $transBank->amount += $amountDifference;
                } else {
                    $bank->amount += abs($amountDifference);
                    $transBank->amount -= abs($amountDifference);
                }

                $transBank->save();
            }

            if ($request->hasFile('image')) {
                $imageName = time() . '_transaction.' . $request->image->extension();
                $this->uploadImage($request->image, $imageName, 'banks');
                $transaction->image = 'Admin/images/banks/' . $imageName;
            }

            $transaction->amount = $newAmount;
            $transaction->date = $request->date;
            $transaction->type = $request->type;
            $transaction->save();
            $bank->save();
            if ($transBank) {
                $transBank->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('main.transaction_failed') . ': ' . $e->getMessage());
        }

        // return redirect()->route('banktransactions.index', $request->bank_id)->with('success', __('main.transaction_successful'));

        return to_route('banktransactions.index', $transaction->bank_id)->with('success', __('alerts.added_successfully'));
    }


    public function destroy($id)
    {
        $shipment = BankTrnsaction::findOrFail($id);

        $shipment->delete();

        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
