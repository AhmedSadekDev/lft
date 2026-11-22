@extends('layouts.admin')
@section('content')
<div class="container-fluid lft-simple-page">
    @include('layouts.includes.breadcrumb', ['page' => __('main.usd_vault')])

    @if(!empty($error))
        <div class="alert alert-warning">{{ $error }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-4">
            <div class="card card-custom gutter-b lft-simple-card">
                <div class="card-body text-center">
                    <div class="text-muted font-weight-bold mb-2">رصيد خزنة الدولار</div>
                    <div style="font-size:2rem;font-weight:800;color:#0f766e;">
                        ${{ number_format((float) ($vault->balance ?? 0), 2) }}
                    </div>
                    <a href="{{ route('usd_vault.transactions') }}" class="btn btn-sm btn-light-primary mt-3">كل الحركات</a>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            @if($vault && auth()->user()->hasPermissionTo('usd_vault.manage'))
            <div class="card card-custom gutter-b lft-simple-card">
                <div class="card-header border-0">
                    <h3 class="card-label mb-0">عملية دولار</h3>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('usd_vault.store') }}" class="lft-simple-form">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="font-weight-bold required-field">نوع العملية</label>
                                <select name="action" id="usdAction" class="form-control" required>
                                    <option value="deposit">إيداع في الخزنة</option>
                                    <option value="withdraw">سحب من الخزنة</option>
                                    <option value="agent">صرف عهدة لمندوب</option>
                                    <option value="bank">تحويل لبنك (دولار)</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="font-weight-bold required-field">المبلغ USD</label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required value="{{ old('amount') }}">
                            </div>
                            <div class="form-group col-md-4" id="usdAgentWrap" style="display:none;">
                                <label class="font-weight-bold">المندوب</label>
                                <select name="agent_id" class="form-control">
                                    <option value="">—</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}">{{ $agent->name }} (${{ number_format((float)($agent->wallet_usd ?? 0), 2) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-4" id="usdBankWrap" style="display:none;">
                                <label class="font-weight-bold">البنك</label>
                                <select name="bank_id" class="form-control">
                                    <option value="">—</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }} (${{ number_format((float)($bank->amount_usd ?? 0), 2) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">البيان</label>
                                <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">مرجع</label>
                                <input type="text" name="reference" class="form-control" value="{{ old('reference') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary font-weight-bolder">تنفيذ</button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header border-0">
            <h3 class="card-label mb-0">أحدث الحركات</h3>
        </div>
        <div class="card-body">
            <table class="table table-responsive-xl no-datatable">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>الاتجاه</th>
                        <th>التصنيف</th>
                        <th>المبلغ</th>
                        <th>المندوب/البنك</th>
                        <th>التاريخ</th>
                        <th>البيان</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $tx)
                        <tr>
                            <td>{{ $tx->id }}</td>
                            <td>{{ $tx->direction === 'in' ? 'وارد' : 'منصرف' }}</td>
                            <td>{{ $tx->category }}</td>
                            <td>${{ number_format((float)$tx->amount, 2) }}</td>
                            <td>{{ $tx->agent?->name ?? $tx->bank?->name ?? '—' }}</td>
                            <td>{{ optional($tx->occurred_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $tx->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">لا توجد حركات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    var action = document.getElementById('usdAction');
    var agentWrap = document.getElementById('usdAgentWrap');
    var bankWrap = document.getElementById('usdBankWrap');
    if (!action) return;
    function sync() {
        agentWrap.style.display = action.value === 'agent' ? '' : 'none';
        bankWrap.style.display = action.value === 'bank' ? '' : 'none';
    }
    action.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
