<div class="col-md-12 mt-2 p-5">
    <!-- Button trigger modal -->
    @if(isset($booking))
        <a href="{{ route('booking-services.create', ['booking' => $booking->id]) }}">
            <button class="btn btn-primary float-right" data-target="#serviceModal" type="button" {{-- onclick=" serviceModal('{{ $booking->id }}')" --}}>
                <i class="fa fa-plus text-white"></i> {{ __('admin.add') }}
            </button>
        </a>
    @endif
</div>

<table class="table table-striped" id="extensions_id" style="width:100%">
    <thead>
        <tr>
            <th>
                #
            </th>
            <th>
                {{ __('admin.service') }}
            </th>
            <th>
                {{ __('admin.note') }}
            </th>
            <th>المندوب / المشرف</th>
            <th>
                {{ __('admin.cost') }}
            </th>
            <th>
                {{ __('admin.receipt_image') }}
            </th>
            <th></th>
        </tr>
    </thead>
    <tbody id="serviceTableRows">
        @forelse (isset($booking_services) ? $booking_services : [] as $service)
            <tr id="service_{{ $service->id }}">
                <td>
                    {{ $service->id }}
                </td>
                <td>
                    {{ $service->full_name }}
                    @if(($service->payment_type ?? null) === 'supplier')
                        <br><small class="text-info">مورد{{ $service->supplier?->name ? ': ' . $service->supplier->name : '' }}</small>
                    @endif
                </td>
                <td>
                    {{ $service->note }}
                </td>
                <td>{{ $service->responsible_name ?: '—' }}</td>
                <td class="services_total_price" data-price="{{ $service->price }}">
                    @if(($service->currency ?? 'EGP') === 'USD' && $service->foreign_amount)
                        <span class="d-block">{{ number_format((float) $service->foreign_amount, 2) }} $</span>
                        <small class="text-muted">{{ number_format((float) $service->price, 2) }} ج.م
                            @if($service->exchange_rate)
                                (× {{ rtrim(rtrim(number_format((float) $service->exchange_rate, 6, '.', ''), '0'), '.') }})
                            @endif
                        </small>
                    @else
                        {{ number_format((float) $service->price, 2) }} ج.م
                    @endif
                </td>
                <td>
                    @if(filled($service->getRawOriginal('image')) && $service->image)
                        <a href="{{ $service->image }}" target="_blank" rel="noopener" style="display: inline-block;">
                            <img src="{{ $service->image }}" alt="Receipt"
                                 style="max-width: 80px; max-height: 80px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #e0e0e0;"
                                 onmouseover="this.style.borderColor='#007bff'"
                                 onmouseout="this.style.borderColor='#e0e0e0'">
                        </a>
                    @else
                        <span class="text-muted">{{ __('admin.no_receipt_image') }}</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex gap-2">
                        @if (auth()->user()->hasPermissionTo('services.update') && isset($booking))
                            <a href="{{ route('booking-services.edit', ['booking' => $booking->id, 'booking_service' => $service->id]) }}"
                               class="btn btn-icon btn-light btn-hover-primary btn-sm">
                                <i class="fas fa-edit text-primary"></i>
                            </a>
                        @endif
                        @if (auth()->user()->hasPermissionTo('services.delete') && isset($booking) && $booking->invoice)
                            <button class="btn btn-icon btn-light btn-hover-warning btn-sm"
                                title="إخفاء من الفاتورة"
                                onclick="serviceHideFromInvoice(event, '{{ $booking->id }}', '{{ $service->id }}')">
                                <i class="fas fa-eye-slash text-warning"></i>
                            </button>
                        @elseif (auth()->user()->hasPermissionTo('services.delete'))
                            <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                                onclick="serviceDelete(event, '{{ $service->id }}')">
                                <i class="fas fa-trash text-danger"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
        @endforelse

        @if (isset($expensesServices))
            @php
                $invoiceForOverrides = isset($booking) ? $booking->invoice : null;
                $overrideSvc = $invoiceForOverrides ? app(\App\Services\InvoicePrintOverrideService::class) : null;
            @endphp
            @foreach($expensesServices as $expense)
                @php
                    $hiddenFromInvoice = $invoiceForOverrides && $overrideSvc
                        ? $overrideSvc->isExpenseHidden($invoiceForOverrides, (int) $expense->id)
                        : false;
                    $invoiceDisplayValue = $invoiceForOverrides && $overrideSvc
                        ? $overrideSvc->effectiveExpenseAmount($invoiceForOverrides, $expense)
                        : (float) $expense->value;
                @endphp
                <tr id="agent_expense_{{ $expense->id }}" @if($hiddenFromInvoice) class="table-secondary" @endif>
                    <td>
                        {{ $expense->id }}
                    </td>
                    <td>
                        {{ $expense->service?->name ?? __('main.not_found') }}
                        @if($hiddenFromInvoice)
                            <br><small class="text-warning">مخفي من الفاتورة</small>
                        @endif
                    </td>
                    <td>
                        {{ filled($expense->notes) ? $expense->notes : '—' }}
                    </td>
                    <td>{{ $expense->responsible_name ?: '—' }}</td>
                    <td class="services_total_price" data-price="{{ $invoiceDisplayValue }}">
                        @if($invoiceForOverrides && abs($invoiceDisplayValue - (float) $expense->value) > 0.009)
                            <span class="d-block">{{ number_format($invoiceDisplayValue, 2) }} <small class="text-muted">(فاتورة)</small></span>
                            <small class="text-muted">أصل المندوب: {{ number_format((float) $expense->value, 2) }}</small>
                        @else
                            {{ number_format((float) $expense->value, 2) }}
                        @endif
                    </td>
                    <td>
                        @php
                            $agentReceiptFile = $expense->getRawOriginal('image_agent_expenses');
                            $agentReceiptUrl = filled($agentReceiptFile)
                                ? asset('Admin/images/expenses/' . $agentReceiptFile)
                                : null;
                        @endphp
                        @if($agentReceiptUrl)
                            <a href="{{ $agentReceiptUrl }}" target="_blank" rel="noopener" style="display: inline-block;">
                                <img src="{{ $agentReceiptUrl }}" alt="Receipt"
                                     style="max-width: 80px; max-height: 80px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #e0e0e0;"
                                     onmouseover="this.style.borderColor='#007bff'"
                                     onmouseout="this.style.borderColor='#e0e0e0'">
                            </a>
                        @else
                            <span class="text-muted">{{ __('admin.no_receipt_image') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            @if (auth()->user()->hasPermissionTo('services.update') && isset($booking) && ! $hiddenFromInvoice)
                                <a href="{{ route('booking-agent-expenses.edit', ['booking' => $booking->id, 'agent_expense' => $expense->id]) }}"
                                   class="btn btn-icon btn-light btn-hover-primary btn-sm"
                                   title="{{ $booking->invoice ? 'تعديل قيمة الفاتورة فقط' : __('admin.edit') }}">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                            @endif
                            @if (auth()->user()->hasPermissionTo('services.delete') && isset($booking) && $booking->invoice && ! $hiddenFromInvoice)
                                <button type="button" class="btn btn-icon btn-light btn-hover-warning btn-sm"
                                    title="إخفاء من الفاتورة (يبقى عند المندوب)"
                                    onclick="agentExpenseHideFromInvoice(event, '{{ $booking->id }}', '{{ $expense->id }}')">
                                    <i class="fas fa-eye-slash text-warning"></i>
                                </button>
                            @elseif (auth()->user()->hasPermissionTo('services.delete') && ! ($booking->invoice ?? null))
                                <button type="button" class="btn btn-icon btn-light btn-hover-danger btn-sm"
                                    onclick="agentExpenseDelete(event, '{{ $expense->id }}')">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        @endif

        @if (isset($supplierReceipts))
            @foreach($supplierReceipts as $receipt)
                <tr id="supplier_receipt_{{ $receipt->id }}">
                    <td>
                        {{ $receipt->id }}
                    </td>
                    <td>
                        إيصال مورد{{ $receipt->supplier?->name ? ': ' . $receipt->supplier->name : '' }}
                        @if($receipt->supplier_invoice_number)
                            <br><small class="text-muted">فاتورة: {{ $receipt->supplier_invoice_number }}</small>
                        @endif
                    </td>
                    <td>
                        {{ $receipt->notes }}
                    </td>
                    <td>{{ $receipt->responsible_name ?: '—' }}</td>
                    <td class="services_total_price" data-price="{{ $receipt->cost }}">
                        {{ $receipt->cost }}
                    </td>
                    <td>
                        <span class="text-muted">{{ __('admin.no_receipt_image') }}</span>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            @if (auth()->user()->hasPermissionTo('suppliers.udpate') || auth()->user()->hasPermissionTo('suppliers.update') || auth()->user()->roles->pluck('name')->contains('Admin'))
                                <a href="{{ route('receipts.edit', $receipt) }}"
                                   class="btn btn-icon btn-light btn-hover-primary btn-sm">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        @endif


    </tbody>
</table>

@push('js')
    <script>
        function serviceDelete(e, id) {
            e.preventDefault();
            Swal.fire({
                title: "{{ __('alerts.are_you_sure') }}",
                text: "{{ __('alerts.not_revert_information') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('alerts.confirm') }}",
                cancelButtonText: "{{ __('alerts.cancel') }}",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('booking-services.destroy', ':id') }}";
                    url = url.replace(':id', id);
                    var token = '{{ csrf_token() }}';

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        success: function(response) {
                            $('#service_' + id).remove();
                            Swal.fire({
                                title: "{{ __('alerts.success') }}",
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            var message = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : (xhr.responseText || thrownError);
                            Swal.fire({
                                title: message,
                                icon: 'error',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        }
                    });
                }
            });
        }

        function agentExpenseHideFromInvoice(e, bookingId, expenseId) {
            e.preventDefault();
            Swal.fire({
                title: "{{ __('alerts.are_you_sure') }}",
                text: 'سيتم إخفاء الإيصال من الفاتورة فقط، ويبقى في حساب المندوب والتطبيق.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('alerts.confirm') }}",
                cancelButtonText: "{{ __('alerts.cancel') }}",
            }).then((result) => {
                if (!result.isConfirmed) return;
                var url = "{{ url('/dashboard/bookings') }}/" + bookingId + "/agent-expenses/" + expenseId + "/hide-from-invoice";
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });
                $.ajax({
                    url: url,
                    type: 'POST',
                    success: function(response) {
                        $('#agent_expense_' + expenseId).fadeOut(200, function () { $(this).remove(); });
                        Swal.fire({
                            title: (response && response.message) ? response.message : "{{ __('alerts.success') }}",
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                        });
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : '{{ __('alerts.error_occurred') }}';
                        Swal.fire({ title: message, icon: 'error', showConfirmButton: false, timer: 3000 });
                    }
                });
            });
        }

        function serviceHideFromInvoice(e, bookingId, serviceId) {
            e.preventDefault();
            Swal.fire({
                title: "{{ __('alerts.are_you_sure') }}",
                text: 'سيتم إخفاء الخدمة من الفاتورة فقط بدون استرداد من الخزنة/البنك/المندوب.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('alerts.confirm') }}",
                cancelButtonText: "{{ __('alerts.cancel') }}",
            }).then((result) => {
                if (!result.isConfirmed) return;
                var url = "{{ url('/dashboard/bookings') }}/" + bookingId + "/booking-services/" + serviceId + "/hide-from-invoice";
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });
                $.ajax({
                    url: url,
                    type: 'POST',
                    success: function(response) {
                        $('#service_' + serviceId).fadeOut(200, function () { $(this).remove(); });
                        Swal.fire({
                            title: (response && response.message) ? response.message : "{{ __('alerts.success') }}",
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                        });
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : '{{ __('alerts.error_occurred') }}';
                        Swal.fire({ title: message, icon: 'error', showConfirmButton: false, timer: 3000 });
                    }
                });
            });
        }

        function agentExpenseDelete(e, id) {
            e.preventDefault();
            Swal.fire({
                title: "{{ __('alerts.are_you_sure') }}",
                text: "{{ __('alerts.not_revert_information') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('alerts.confirm') }}",
                cancelButtonText: "{{ __('alerts.cancel') }}",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('expenses.destroy', ':id') }}".replace(':id', id);
                    var token = '{{ csrf_token() }}';

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        success: function(response) {
                            $('#agent_expense_' + id).remove();
                            Swal.fire({
                                title: "{{ __('alerts.success') }}",
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        },
                        error: function(xhr) {
                            var message = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : (xhr.responseText || '{{ __('alerts.error_occurred') }}');
                            Swal.fire({
                                title: message,
                                icon: 'error',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        }
                    });
                }
            });
        }
    </script>
@endpush
