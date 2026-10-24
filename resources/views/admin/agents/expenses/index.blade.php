@extends("layouts.admin")
@section("content")
@php
    $agentExpenseRows = $allExpenses->filter(fn ($item) => $item instanceof \App\Models\AgentExpense);
    $pendingCount = $awaitingReviewIds->count();
    $reviewedCount = $agentExpenseRows->reject(fn ($item) => $awaitingReviewIds->has($item->id))->count();
@endphp
<style>
    .lft-exp-page .lft-exp-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    .lft-exp-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid var(--border-color);
        background: var(--fill-subtle);
        color: var(--text-secondary);
        white-space: nowrap;
    }
    .lft-exp-chip strong {
        color: var(--text-primary);
        font-size: 15px;
    }
    .lft-exp-chip.is-pending {
        background: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.28);
        color: #b45309;
    }
    .lft-exp-chip.is-pending strong { color: #b45309; }
    .lft-exp-chip.is-done {
        background: rgba(16, 185, 129, 0.12);
        border-color: rgba(16, 185, 129, 0.28);
        color: #047857;
    }
    .lft-exp-chip.is-done strong { color: #047857; }
    [data-theme="dark"] .lft-exp-chip.is-pending,
    [data-theme="dark"] .lft-exp-chip.is-pending strong { color: #fbbf24; }
    [data-theme="dark"] .lft-exp-chip.is-done,
    [data-theme="dark"] .lft-exp-chip.is-done strong { color: #34d399; }

    .lft-exp-table td,
    .lft-exp-table th {
        vertical-align: middle !important;
    }
    .lft-exp-table tbody tr.lft-exp-row--pending {
        background: rgba(245, 158, 11, 0.04);
    }
    [data-theme="dark"] .lft-exp-table tbody tr.lft-exp-row--pending {
        background: rgba(251, 191, 36, 0.06);
    }

    .lft-exp-thumb {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid var(--border-color);
        background: var(--fill-subtle);
        display: block;
    }
    .lft-exp-thumb-empty {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px dashed var(--border-color);
        color: var(--text-muted);
        background: var(--fill-subtle);
        font-size: 11px;
        text-align: center;
        line-height: 1.3;
        padding: 4px;
    }

    .lft-exp-title {
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 2px;
        line-height: 1.4;
    }
    .lft-exp-meta {
        margin: 0;
        color: var(--text-muted);
        font-size: 12px;
    }
    .lft-exp-value {
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: var(--text-primary);
        white-space: nowrap;
    }
    .lft-exp-date {
        color: var(--text-secondary);
        font-size: 13px;
        white-space: nowrap;
    }

    .lft-exp-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }
    .lft-exp-status.is-pending {
        background: rgba(245, 158, 11, 0.14);
        color: #b45309;
    }
    .lft-exp-status.is-done {
        background: rgba(16, 185, 129, 0.14);
        color: #047857;
    }
    [data-theme="dark"] .lft-exp-status.is-pending { color: #fbbf24; }
    [data-theme="dark"] .lft-exp-status.is-done { color: #34d399; }

    .lft-exp-actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: nowrap;
    }
    .lft-exp-actions .btn {
        border-radius: 10px;
        font-weight: 700;
        padding: 7px 12px;
        box-shadow: none !important;
    }
    .lft-exp-actions .btn i {
        margin-left: 4px;
    }
    .lft-exp-actions .btn-approve {
        background: rgba(16, 185, 129, 0.14) !important;
        color: #047857 !important;
        border: 1px solid rgba(16, 185, 129, 0.28) !important;
    }
    .lft-exp-actions .btn-approve:hover {
        background: rgba(16, 185, 129, 0.22) !important;
    }
    .lft-exp-actions .btn-reject {
        background: rgba(239, 68, 68, 0.12) !important;
        color: #b91c1c !important;
        border: 1px solid rgba(239, 68, 68, 0.28) !important;
    }
    .lft-exp-actions .btn-reject:hover {
        background: rgba(239, 68, 68, 0.2) !important;
    }
    [data-theme="dark"] .lft-exp-actions .btn-approve {
        color: #34d399 !important;
    }
    [data-theme="dark"] .lft-exp-actions .btn-reject {
        color: #fb7185 !important;
    }
    .lft-exp-approved-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 600;
    }
    .lft-exp-actions .btn-icon-delete {
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: rgba(239, 68, 68, 0.08) !important;
        border: 1px solid rgba(239, 68, 68, 0.18) !important;
        color: #ef4444 !important;
    }
</style>

<div class="container-fluid lft-simple-page lft-exp-page">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.expenses') ])

    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header border-0 flex-wrap py-5">
            <div class="card-title mb-2 mb-md-0">
                <h3 class="card-label mb-1">{{ __('main.expenses') }}</h3>
                @isset($agent)
                    <div class="text-muted font-size-sm">{{ $agent->name }}</div>
                @endisset
            </div>
            <div class="card-toolbar d-flex flex-wrap align-items-center" style="gap: 12px;">
                <div class="lft-exp-summary">
                    <span class="lft-exp-chip is-pending">
                        بانتظار المراجعة
                        <strong>{{ $pendingCount }}</strong>
                    </span>
                    <span class="lft-exp-chip is-done">
                        تمت المراجعة
                        <strong>{{ $reviewedCount }}</strong>
                    </span>
                </div>
                @if ($awaitingReviewIds->isNotEmpty())
                    <form id="reviewExpensesForm" method="POST" action="{{ route('expenses.agent_expenses.review', $agent) }}"
                        onsubmit="return lftConfirmForm(this, { title: '{{ __('alerts.are_you_sure') }}', text: 'سيتم اعتماد {{ $awaitingReviewIds->count() }} إيصال وإخفاؤها من تطبيق المندوب. متابعة؟', confirmText: '{{ __('alerts.confirm') }}', cancelText: '{{ __('alerts.cancel') }}' });">
                        @csrf
                        <button type="submit" class="btn btn-primary font-weight-bolder">
                            <i class="fas fa-check-double"></i>
                            مراجعة الكل ({{ $awaitingReviewIds->count() }})
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover lft-exp-table" id="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col" style="width: 56px;">#</th>
                            <th scope="col" style="width: 72px;">صورة</th>
                            <th scope="col">{{ __('admin.title') }}</th>
                            <th scope="col">المندوب / المشرف</th>
                            <th scope="col">{{ __('admin.value') }}</th>
                            <th scope="col">{{ __('main.date') }}</th>
                            <th scope="col">المراجعة</th>
                            <th scope="col" style="min-width: 180px;">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($allExpenses as $allExpense)
                            @php
                                $isExpense = $allExpense instanceof \App\Models\AgentExpense;
                                $isPending = $isExpense && $awaitingReviewIds->has($allExpense->id);
                                $createdAt = method_exists($allExpense, 'getRawOriginal')
                                    ? $allExpense->getRawOriginal('created_at')
                                    : ($allExpense->created_at ?? null);
                                $imageName = $isExpense ? $allExpense->image_agent_expenses : null;
                            @endphp
                            <tr class="{{ $isPending ? 'lft-exp-row--pending' : '' }}">
                                <td class="text-muted font-weight-bold">{{ $allExpense->id }}</td>
                                <td>
                                    @if($imageName)
                                        <a href="{{ asset('Admin/images/expenses/' . $imageName) }}" target="_blank" rel="noopener">
                                            <img class="lft-exp-thumb"
                                                 src="{{ asset('Admin/images/expenses/' . $imageName) }}"
                                                 alt="صورة الإيصال">
                                        </a>
                                    @else
                                        <span class="lft-exp-thumb-empty">لا توجد<br>صورة</span>
                                    @endif
                                </td>
                                <td>
                                    <p class="lft-exp-title">{{ $allExpense->title ?? '—' }}</p>
                                    @if($isExpense && $allExpense->booking?->booking_number)
                                        <p class="lft-exp-meta">طلب {{ $allExpense->booking->booking_number }}</p>
                                    @endif
                                </td>
                                <td>
                                    {{ $isExpense
                                        ? ($allExpense->responsible_name ?: '—')
                                        : ($allExpense->transferer?->name ?? '—') }}
                                </td>
                                <td>
                                    <span class="lft-exp-value">
                                        {{ is_numeric($allExpense->value ?? null) ? number_format((float) $allExpense->value, 2) : ($allExpense->value ?? '—') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="lft-exp-date">
                                        {{ $createdAt ? \Carbon\Carbon::parse($createdAt)->format('Y-m-d H:i') : '—' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($isExpense)
                                        @if ($isPending)
                                            <span class="lft-exp-status is-pending">
                                                <i class="fas fa-clock"></i> بانتظار المراجعة
                                            </span>
                                        @else
                                            <span class="lft-exp-status is-done">
                                                <i class="fas fa-check-circle"></i> تمت المراجعة
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isExpense && isset($allExpense->agent_id))
                                        <div class="lft-exp-actions">
                                            @if ($isPending)
                                                <button type="button"
                                                        class="btn btn-sm btn-approve"
                                                        onclick="ApproveExpense('{{ $allExpense->id }}')">
                                                    <i class="fas fa-check"></i> موافقة
                                                </button>
                                                <button type="button"
                                                        class="btn btn-sm btn-reject"
                                                        onclick="RejectExpense('{{ $allExpense->id }}', '{{ $allExpense->version }}')">
                                                    <i class="fas fa-times"></i> رفض
                                                </button>
                                            @else
                                                <span class="lft-exp-approved-label">
                                                    <i class="fas fa-shield-alt"></i> معتمد
                                                </span>
                                                <button type="button"
                                                        class="btn btn-icon-delete"
                                                        title="حذف"
                                                        onclick="RejectExpense('{{ $allExpense->id }}', '{{ $allExpense->version }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@push('js')
    <script>
        function ajaxHeaders() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
        }

        function ApproveExpense(id) {
            Swal.fire({
                title: 'تأكيد الموافقة؟',
                text: 'سيتم اعتماد الإيصال وخصم العهدة وإخفاؤه من شاشة المندوب.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'موافقة',
                cancelButtonText: "{{ __('alerts.cancel') }}",
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-success mx-1',
                    cancelButton: 'btn btn-secondary mx-1',
                },
            }).then((result) => {
                if (!result.isConfirmed) return;
                ajaxHeaders();
                $.ajax({
                    url: '{{ route("expenses.approve", ":id") }}'.replace(':id', id),
                    type: 'POST',
                    success: function () {
                        Swal.fire({ title: "{{ __('alerts.done') }}", icon: 'success', showConfirmButton: false, timer: 2000 });
                        location.reload();
                    },
                    error: function (xhr) {
                        Swal.fire({
                            title: "{{ __('alerts.error') }}",
                            text: xhr.responseJSON?.message || "{{ __('alerts.error_occurred') }}",
                            icon: 'error',
                        });
                    }
                });
            });
        }

        function RejectExpense(id, version) {
            Swal.fire({
                title: 'رفض الإيصال؟',
                text: "سيتم إلغاء الإيصال وإرجاع قيمته إلى عهدة المندوب. {{ __('alerts.not_revert_information') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'رفض',
                cancelButtonText: "{{ __('alerts.cancel') }}",
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger mx-1',
                    cancelButton: 'btn btn-secondary mx-1',
                },
            }).then((result) => {
                if (!result.isConfirmed) return;
                ajaxHeaders();
                $.ajax({
                    url: '{{ route("expenses.destroy", ":id") }}'.replace(':id', id),
                    data: { version: version },
                    type: 'delete',
                    success: function () {
                        Swal.fire({ title: "{{ __('alerts.done') }}", icon: 'success', showConfirmButton: false, timer: 2000 });
                        location.reload();
                    },
                    error: function (xhr) {
                        Swal.fire({
                            title: "{{ __('alerts.error') }}",
                            text: xhr.responseJSON?.message || "{{ __('alerts.error_occurred') }}",
                            icon: 'error',
                        });
                    }
                });
            });
        }
    </script>
@endpush
