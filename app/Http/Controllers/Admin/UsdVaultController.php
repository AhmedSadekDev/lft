<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Bank;
use App\Models\UsdVaultTransaction;
use App\Services\UsdVaultService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UsdVaultController extends Controller
{
    public function __construct(private UsdVaultService $usdVault)
    {
        $this->middleware('permission:usd_vault.index')->only(['index', 'transactions']);
        $this->middleware('permission:usd_vault.manage')->only(['store']);
    }

    public function index()
    {
        try {
            $vault = $this->usdVault->ensureVault();
        } catch (\Throwable $e) {
            return view('admin.usd-vault.index', [
                'vault' => null,
                'agents' => collect(),
                'banks' => collect(),
                'recent' => collect(),
                'error' => 'خزنة الدولار غير جاهزة — طبّق docs/sql/2026-11-22-usd-followup-reefer-module.sql',
            ]);
        }

        return view('admin.usd-vault.index', [
            'vault' => $vault,
            'agents' => Agent::query()->orderBy('name')->get(['id', 'name', 'wallet_usd']),
            'banks' => Bank::query()->orderBy('name')->get(['id', 'name', 'amount_usd']),
            'recent' => UsdVaultTransaction::query()
                ->with(['agent:id,name', 'bank:id,name'])
                ->whereNull('reversed_at')
                ->orderByDesc('id')
                ->limit(25)
                ->get(),
            'error' => null,
        ]);
    }

    public function transactions(Request $request)
    {
        $query = UsdVaultTransaction::query()
            ->with(['agent:id,name', 'bank:id,name', 'creator:id,name'])
            ->orderByDesc('id');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }
        if ($request->filled('from')) {
            $query->whereDate('occurred_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('occurred_at', '<=', $request->to);
        }

        return view('admin.usd-vault.transactions', [
            'transactions' => $query->paginate(40)->appends($request->query()),
            'filters' => $request->only(['category', 'direction', 'from', 'to']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:deposit,withdraw,agent,bank',
            'amount' => 'required|numeric|min:0.01',
            'agent_id' => 'nullable|exists:agents,id',
            'bank_id' => 'nullable|exists:banks,id',
            'description' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:191',
        ]);

        try {
            $amount = (float) $data['amount'];
            $userId = auth()->id();
            match ($data['action']) {
                'deposit' => $this->usdVault->deposit($amount, $data['description'] ?? null, $data['reference'] ?? null, $userId),
                'withdraw' => $this->usdVault->withdraw($amount, $data['description'] ?? null, $data['reference'] ?? null, $userId),
                'agent' => $this->usdVault->transferToAgent($amount, (int) ($data['agent_id'] ?? 0), $data['description'] ?? null, $userId),
                'bank' => $this->usdVault->transferToBank($amount, (int) ($data['bank_id'] ?? 0), $data['description'] ?? null, $userId),
            };
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('usd_vault.index')->with('success', __('alerts.updated_successfully'));
    }
}
