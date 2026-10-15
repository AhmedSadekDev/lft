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
use App\Services\BankTransactionService;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $item = BankTrnsaction::with(['bank', 'transBank', 'company'])->findOrfail($id);

        return view('admin.banktransactions.edit', compact('item'));
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
            $bank = Bank::lockForUpdate()->findOrFail($request->bank_id);
            $vault = Vault::lockForUpdate()->firstOrFail();
            // Handle image upload
            if ($request->hasFile('image')) {
                $imageName = time() . '_transaction.' . $request->image->extension();
                $imagePath = $request->type == 1 ? 'companyInvoice' : 'banks';
                $this->uploadImage($request->image, $imageName, $imagePath);
                $data['image'] = "Admin/images/{$imagePath}/{$imageName}";
            }
            switch ($request->type) {
                case 2:
                    $transBank = Bank::lockForUpdate()->findOrFail($request->trans_bank_id);
    
                    if ((float) $bank->amount < (float) $request->amount) {
                        DB::rollBack();
                        return redirect()->back()->with('error', __('main.bank_wallet_does_not_have_enough_amount'));
                    }

                    $bank->amount -= $request->amount;
                    $transBank->amount += $request->amount;
    
                    $bank->save();
                    $transBank->save();

                    BankTrnsaction::create([
                        'bank_id' => $bank->id,
                        'trans_bank_id' => $transBank->id,
                        'name' => $request->name,
                        'date' => $request->date,
                        'amount' => $request->amount,
                        'type' => 2,
                        'user_id' => auth()->user()->id,
                        'image' => $data['image'] ?? null
                    ]);
                    break;
    
                case 0:
                    if ((float) $bank->amount < (float) $request->amount) {
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
                    $company = Company::lockForUpdate()->findOrFail($request->company_id);
                    
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
            'amount' => 'required|numeric|gt:0',
            'date' => 'nullable',
            'image' => 'nullable|mimes:jpg,png,jpeg',
        ]);
        unset($data['image']);

        try {
            if ($request->hasFile('image')) {
                $imageName = time() . '_transaction.' . $request->image->extension();
                $this->uploadImage($request->image, $imageName, 'banks');
                $data['image'] = 'Admin/images/banks/' . $imageName;
            }

            $transaction = app(BankTransactionService::class)->update((int) $id, $data);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', __('main.transaction_failed') . ': ' . $e->getMessage());
        }

        return to_route('banktransactions.index', $transaction->bank_id)->with('success', __('alerts.updated_successfully'));
    }


    public function destroy($id)
    {
        try {
            app(BankTransactionService::class)->delete((int) $id);

            return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
        } catch (HttpExceptionInterface $e) {
            return response()->json(['staus' => false, 'msg' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json(['staus' => false, 'msg' => $e->getMessage()], 500);
        }
    }
}
