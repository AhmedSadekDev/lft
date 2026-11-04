<?php

namespace App\Http\Controllers\Admin;

use App\Exports\VaultExport;
use App\Models\Agent;
use App\Models\Bank;
use App\Models\Vault;
use App\Services\VaultSpendService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class VaultController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:vault.index')->only(['index', 'spend']);
        $this->middleware('permission:vault.create')->only(['create', 'store']);
        $this->middleware('permission:vault.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:vault.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $vault = Vault::first();
        $agents = Agent::query()->orderBy('name')->pluck('name', 'id');
        $banks = Bank::query()->orderBy('name')->get(['id', 'name', 'amount']);

        return view('admin.vaults.index', compact('vault', 'agents', 'banks'));
    }

    /**
     * صرف موحّد من الخزنة: عهدة مندوب / تحويل بنك / مصروف عام أو خاص.
     */
    public function spend(Request $request, VaultSpendService $service)
    {
        $data = $request->validate([
            'action' => 'required|in:agent,bank,expense',
            'amount' => 'required|numeric|gt:0',
            'agent_id' => 'required_if:action,agent|nullable|exists:agents,id',
            'bank_id' => 'required_if:action,bank|nullable|exists:banks,id',
            'name' => 'required_if:action,bank|nullable|string|max:255',
            'date' => 'nullable|date',
            'reason' => 'required_if:action,expense|nullable|string|max:255',
            'expense_kind' => 'required_if:action,expense|nullable|in:general,special',
        ], [
            'action.required' => 'اختر نوع العملية',
            'amount.required' => 'المبلغ مطلوب',
            'amount.gt' => 'المبلغ يجب أن يكون أكبر من صفر',
            'agent_id.required_if' => 'اختر المندوب',
            'bank_id.required_if' => 'اختر البنك',
            'name.required_if' => 'اسم العملية مطلوب',
            'reason.required_if' => 'سبب المصروف مطلوب',
            'expense_kind.required_if' => 'اختر نوع المصروف',
        ]);

        try {
            $service->spend($data, auth()->id());

            $messages = [
                VaultSpendService::ACTION_AGENT => 'تم تحويل العهدة للمندوب وخصمها من الخزنة',
                VaultSpendService::ACTION_BANK => 'تم التحويل للبنك وخصمه من الخزنة',
                VaultSpendService::ACTION_EXPENSE => 'تم تسجيل المصروف وخصمه من الخزنة',
            ];

            return redirect()
                ->route('vaults.index')
                ->with('success', $messages[$data['action']] ?? __('alerts.added_successfully'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }



    public function export(Request $request)    
    {
        $ids = explode(',', $request->ids);
        return Excel::download(new VaultExport($ids), 'vault.xlsx');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort(404);
        $input = [
            'method'    => 'POST',
            'action'    => route('vaults.store')
        ];

        return view('admin.vaults.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'note' => 'nullable|string',
            'type' => 'required|in:0,1'
        ]);

        Vault::create($request->all());

        return redirect()->route('vaults.index')
            ->with('success', __('alerts.added_successfully'));
    }


    public function edit(Vault $vault)
    {
        $input = [
            'method'    => 'PUT',
            'action'    => route('vaults.update', $vault->id),
            'vault'   => $vault,
        ];

        return view('admin.vaults.edit', $input);
    }


    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            // 'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            // 'note' => 'nullable|string',
            // 'type' => 'required|in:0,1'
        ]);

        // Find the vault transaction
        $transaction = Vault::findOrFail($id);

        // Update the transaction
        $transaction->update($request->all());

        return back()->with('success', __('main.transaction_updated_successfully'));
    }

    public function destroy(Vault $vault)
    {
        $vault->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
