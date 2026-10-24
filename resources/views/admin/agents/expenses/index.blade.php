@extends("layouts.admin")
@section("content")
<div class="container-fluid lft-simple-page">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.expenses') ])
    <!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header border-0">
            <div class="card-title">
                <h3 class="card-label">{{ __('main.expenses') }}</h3>
            </div>
            <div class="card-toolbar">
                @if ($awaitingReviewIds->isNotEmpty())
                    <form id="reviewExpensesForm" method="POST" action="{{ route('expenses.agent_expenses.review', $agent) }}"
                        onsubmit="return lftConfirmForm(this, { title: '{{ __('alerts.are_you_sure') }}', text: 'سيتم اعتماد {{ $awaitingReviewIds->count() }} إيصال وإخفاؤها من تطبيق المندوب. متابعة؟', confirmText: '{{ __('alerts.confirm') }}', cancelText: '{{ __('alerts.cancel') }}' });">
                        @csrf
                        <button type="submit" class="btn btn-primary font-weight-bolder">
                            <i class="fas fa-check-double"></i> تمت مراجعة الإيصالات ({{ $awaitingReviewIds->count() }})
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">صورة</th>
                        <th scope="col">{{ __('admin.title') }}</th>
                        <th scope="col">المندوب / المشرف</th>
                        <th scope="col">{{ __('admin.value') }}</th>
                        <th scope="col">{{ __('main.date') }}</th>
                        <th scope="col">المراجعة</th>
                        <th scope="col">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($allExpenses as $allExpense)
                    <tr>
                            <th scope="row">{{$allExpense->id}}</th>
                            <td >@if($allExpense->image !== null) <img
                                    src="{{ asset('Admin/images/expenses/' . $allExpense->image_agent_expenses) }}" alt="صورة الايصال"
                                    style="width: 100px;" /> @else لا توجد صورة @endif</td>
                            <td>{{ $allExpense->title ?? "" }}</td>
                            <td>{{ $allExpense instanceof \App\Models\AgentExpense ? ($allExpense->responsible_name ?: '—') : ($allExpense->transferer?->name ?? '—') }}</td>
                            <td>{{ $allExpense->value ?? "" }}</td>
                            @php $createdAt = $allExpense->getRawOriginal('created_at'); @endphp
                            <td>{{ $createdAt ? \Carbon\Carbon::parse($createdAt)->format('Y-m-d H:i') : '' }}</td>
                            <td>
                                @if ($allExpense instanceof \App\Models\AgentExpense)
                                    @if ($awaitingReviewIds->has($allExpense->id))
                                        <span class="badge badge-warning">بانتظار المراجعة</span>
                                    @else
                                        <span class="badge badge-success">تمت المراجعة</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if(isset($allExpense->agent_id) && $allExpense instanceof \App\Models\AgentExpense)
                                    @if ($awaitingReviewIds->has($allExpense->id))
                                        <button type="button" class="btn btn-sm btn-success mr-1"
                                            onclick="ApproveExpense('{{ $allExpense->id }}')">
                                            <i class="fas fa-check"></i> موافقة
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="RejectExpense('{{ $allExpense->id }}', '{{ $allExpense->version }}')">
                                            <i class="fas fa-times"></i> رفض
                                        </button>
                                    @else
                                        <span class="text-muted small mr-1">معتمد</span>
                                        <button type="button" class="btn btn-icon btn-light btn-hover-danger btn-sm"
                                            title="حذف"
                                            onclick="RejectExpense('{{ $allExpense->id }}', '{{ $allExpense->version }}')">
                                            <i class="fas fa-trash text-danger"></i>
                                        </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <!--end::Card-->
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
