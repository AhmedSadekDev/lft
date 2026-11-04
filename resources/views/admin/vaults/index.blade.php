@extends('layouts.admin')
@section('content')
    <div class="container-fluid lft-simple-page">
        @include('layouts.includes.breadcrumb', ['page' => __('main.vault')])

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
            <div class="card-header border-0">
                <div class="card-title">
                    <h3 class="card-label">{{ __('main.vault') }}</h3>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('vaultransactions.index') }}" class="btn btn-light-primary font-weight-bolder">
                        <i class="fas fa-list"></i> حركات الخزنة
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-responsive-xl" id="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ __('main.amount') }}</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($vault)
                            <tr>
                                <th scope="row">{{ $vault->id }}</th>
                                <td>
                                    <a href="{{ route('vaultransactions.index') }}">
                                        {{ number_format((float) $vault->amount, 2) }}
                                    </a>
                                </td>
                                <td>
                                    @if (auth()->user()->hasPermissionTo('vaults.update') || auth()->user()->hasPermissionTo('vault.update'))
                                        <a href="{{ route('vaults.edit', $vault->id) }}"
                                            class="btn btn-icon btn-light btn-hover-primary btn-sm">
                                            <i class="fas fa-edit text-primary"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="3" class="text-center text-muted">لا توجد خزنة في النظام</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        @if ($vault)
            <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
                <div class="card-header border-0">
                    <div class="card-title">
                        <h3 class="card-label">صرف من الخزنة</h3>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('vaults.spend') }}" method="post" id="vaultSpendForm" class="lft-simple-form">
                        @csrf

                        <div class="row">
                            <div class="col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label for="spendAction" class="font-weight-bold required-field">نوع العملية</label>
                                    <select name="action" id="spendAction" class="form-control" required>
                                        <option value="">اختر العملية</option>
                                        <option value="agent" @selected(old('action') === 'agent')>تحويل لمندوب (عهدة)</option>
                                        <option value="bank" @selected(old('action') === 'bank')>تحويل لبنك</option>
                                        <option value="expense" @selected(old('action') === 'expense')>صرف مصروف</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label for="spendAmount" class="font-weight-bold required-field">{{ __('main.amount') }}</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" id="spendAmount"
                                           value="{{ old('amount') }}" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="row spend-panel" id="panel-agent" style="display: none;">
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label for="spendAgentId" class="font-weight-bold required-field">{{ __('admin.agent') }}</label>
                                    <select name="agent_id" id="spendAgentId" class="form-control selectpicker"
                                            data-live-search="true" title="{{ __('admin.agent') }}">
                                        <option value="">{{ __('admin.agent') }}</option>
                                        @foreach ($agents as $id => $name)
                                            <option value="{{ $id }}" @selected((string) old('agent_id') === (string) $id)>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row spend-panel" id="panel-bank" style="display: none;">
                            <div class="col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label for="spendBankId" class="font-weight-bold required-field">البنك</label>
                                    <select name="bank_id" id="spendBankId" class="form-control">
                                        <option value="">اختر البنك</option>
                                        @foreach ($banks as $bank)
                                            <option value="{{ $bank->id }}" @selected((string) old('bank_id') === (string) $bank->id)>
                                                {{ $bank->name }} ({{ number_format((float) $bank->amount, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label for="spendName" class="font-weight-bold required-field">{{ __('main.operation_name') }}</label>
                                    <input type="text" name="name" id="spendName" value="{{ old('name') }}"
                                           class="form-control" maxlength="255">
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label for="spendDate" class="font-weight-bold">{{ __('main.date') }}</label>
                                    <input type="date" name="date" id="spendDate"
                                           value="{{ old('date', now()->toDateString()) }}" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row spend-panel" id="panel-expense" style="display: none;">
                            <div class="col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label for="spendExpenseKind" class="font-weight-bold required-field">نوع المصروف</label>
                                    <select name="expense_kind" id="spendExpenseKind" class="form-control">
                                        <option value="general" @selected(old('expense_kind', 'general') === 'general')>مصروف عام</option>
                                        <option value="special" @selected(old('expense_kind') === 'special')>مصروف خاص</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8 col-sm-12">
                                <div class="form-group">
                                    <label for="spendReason" class="font-weight-bold required-field">السبب</label>
                                    <input type="text" name="reason" id="spendReason" value="{{ old('reason') }}"
                                           class="form-control" maxlength="255"
                                           placeholder="مثال: مرتبات / إيجار / مصروف إداري">
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border">
                            رصيد الخزنة الحالي:
                            <strong>{{ number_format((float) $vault->amount, 2) }}</strong>
                        </div>

                        <button type="submit" class="btn btn-primary font-weight-bold">
                            <i class="fas fa-check"></i> تنفيذ الصرف
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('js')
    <script>
        (function () {
            var actionSelect = document.getElementById('spendAction');
            if (!actionSelect) {
                return;
            }

            function syncPanels() {
                var action = actionSelect.value;
                document.querySelectorAll('.spend-panel').forEach(function (panel) {
                    panel.style.display = 'none';
                });

                var panel = document.getElementById('panel-' + action);
                if (panel) {
                    panel.style.display = '';
                }
            }

            actionSelect.addEventListener('change', syncPanels);
            syncPanels();
        })();
    </script>
@endpush
