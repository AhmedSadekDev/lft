@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    @include('layouts.includes.breadcrumb', ['page' => 'سداد مورد - ' . $supplier->name])

    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-money-bill text-success mr-2"></i>
                    سداد مورد - {{ $supplier->name }}
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('suppliers.statement', $supplier) }}" class="btn btn-primary font-weight-bold shadow-sm">
                    <i class="fas fa-file-invoice"></i> كشف الحساب
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success m-3">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger m-3">{{ session('error') }}</div>
        @endif

        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات المورد</h5>
                    <p><strong>الاسم:</strong> {{ $supplier->name }}</p>
                    <p><strong>الرصيد المستحق:</strong>
                        <span class="text-danger font-weight-bold" style="font-size: 1.2em">
                            {{ number_format((float) $supplier->balance, 2) }} جنيه
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <h5 class="font-weight-bold">رصيد الخزنة</h5>
                    <p><strong>المتاح:</strong>
                        {{ number_format((float) optional($vault)->amount ?? 0, 2) }} جنيه
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('suppliers.payment.process', $supplier) }}" id="supplierPaymentForm">
                @csrf

                <div class="form-group">
                    <label class="font-weight-bold required-field">المبلغ</label>
                    <input type="number" name="amount" step="0.01" min="0.01"
                           value="{{ old('amount') }}"
                           class="form-control @error('amount') is-invalid @enderror"
                           required>
                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="font-weight-bold required-field">مصدر السداد</label>
                    <select name="source_type" id="source_type"
                            class="form-control @error('source_type') is-invalid @enderror" required>
                        <option value="">اختر المصدر</option>
                        <option value="safe" {{ old('source_type') === 'safe' ? 'selected' : '' }}>الخزنة</option>
                        <option value="representative" {{ old('source_type') === 'representative' ? 'selected' : '' }}>مندوب</option>
                    </select>
                    @error('source_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group" id="agent_source_wrap" style="display: none;">
                    <label class="font-weight-bold required-field">المندوب</label>
                    <select name="source_id" id="source_id"
                            class="form-control @error('source_id') is-invalid @enderror">
                        <option value="">اختر المندوب</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}"
                                    data-wallet="{{ $agent->wallet }}"
                                    {{ (string) old('source_id') === (string) $agent->id ? 'selected' : '' }}>
                                {{ $agent->name }} — رصيد: {{ number_format((float) $agent->wallet, 2) }}
                            </option>
                        @endforeach
                    </select>
                    @error('source_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">ملاحظات</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn btn-success font-weight-bold"
                        onclick="return confirm('تأكيد تسجيل السداد؟');">
                    <i class="fas fa-check"></i> تسجيل السداد
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    function toggleAgentSource() {
        var type = document.getElementById('source_type').value;
        var wrap = document.getElementById('agent_source_wrap');
        var select = document.getElementById('source_id');
        if (type === 'representative') {
            wrap.style.display = 'block';
            select.setAttribute('required', 'required');
        } else {
            wrap.style.display = 'none';
            select.removeAttribute('required');
            select.value = '';
        }
    }
    document.getElementById('source_type').addEventListener('change', toggleAgentSource);
    toggleAgentSource();
</script>
@endpush
