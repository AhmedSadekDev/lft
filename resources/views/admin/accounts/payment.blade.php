@extends("layouts.admin")

@section("content")
<div class="container-fluid">
    @include("layouts.includes.breadcrumb", [ 'page' => 'سداد - ' . $company->name ])

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-money-bill text-success mr-2"></i>
                    سداد حساب - {{ $company->name }}
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('accounts.statement', $company->id) }}"
                   class="btn btn-primary font-weight-bold shadow-sm">
                    <i class="fas fa-file-invoice"></i> كشف الحساب
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show m-3">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show m-3">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif

        <div class="card-body">
            <!-- معلومات الشركة والحساب -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات الشركة</h5>
                    <p><strong>الاسم:</strong> {{ $company->name }}</p>
                    <p><strong>البريد:</strong> {{ $company->email }}</p>
                    <p><strong>الهاتف:</strong> {{ $company->phone }}</p>
                </div>
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات الحساب</h5>
                    @php $cb = $currentBalances ?? ['EGP' => $currentBalance ?? 0, 'USD' => 0]; @endphp
                    <p><strong>المستحق ج.م:</strong>
                        <span class="text-danger font-weight-bold" style="font-size: 1.2em">
                            {{ number_format($cb['EGP'] ?? 0, 2) }} ج.م
                        </span>
                    </p>
                    <p><strong>المستحق $:</strong>
                        <span class="text-danger font-weight-bold" style="font-size: 1.2em">
                            {{ number_format($cb['USD'] ?? 0, 2) }} $
                        </span>
                    </p>
                    <p class="text-muted mb-0">
                        افتتاحي ج.م: {{ number_format($company->opening_balance ?? 0, 2) }}
                        —
                        افتتاحي $: {{ number_format($company->opening_balance_usd ?? 0, 2) }}
                    </p>
                </div>
            </div>

            <!-- الفواتير غير المسددة -->
            <div id="invoices_section">
                @if($unpaidInvoices->count() > 0)
                    <div class="mb-4">
                        <h5 class="font-weight-bold mb-3">الفواتير غير المسددة (بالأقسام)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead style="background: linear-gradient(135deg, #DC143C 0%, #B22222 100%); color: #fff;">
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="select_all" title="تحديد الكل">
                                        </th>
                                        <th>رقم الفاتورة / القسم</th>
                                        <th>رقم الطلب</th>
                                        <th>التاريخ</th>
                                        <th>متبقي</th>
                                        <th>مبلغ السداد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unpaidInvoices as $invoiceIndex => $invoice)
                                        <tr class="invoice-parent-row table-light" data-invoice-id="{{ $invoice['id'] }}">
                                            <td>
                                                <input type="checkbox"
                                                       class="invoice-checkbox parent-invoice-checkbox"
                                                       value="{{ $invoice['id'] }}"
                                                       data-invoice-id="{{ $invoice['id'] }}"
                                                       data-remaining-egp="{{ $invoice['remaining_egp'] ?? $invoice['remaining'] }}"
                                                       data-remaining-usd="{{ $invoice['remaining_usd'] ?? 0 }}">
                                            </td>
                                            <td class="font-weight-bold">{{ $invoice['invoice_number'] }}</td>
                                            <td>{{ $invoice['booking_number'] }}</td>
                                            <td>{{ \Carbon\Carbon::parse($invoice['date'])->format('Y-m-d') }}</td>
                                            <td class="text-danger font-weight-bold">
                                                {{ number_format($invoice['remaining_egp'] ?? $invoice['remaining'], 2) }} ج.م
                                                @if(($invoice['remaining_usd'] ?? 0) > 0)
                                                    <br>{{ number_format($invoice['remaining_usd'], 2) }} $
                                                @endif
                                            </td>
                                            <td class="text-muted small">اختر الأقسام بالأسفل</td>
                                        </tr>
                                        @foreach(($invoice['sections'] ?? []) as $section)
                                            @php
                                                $secKey = $section['key'] ?? '';
                                                $secCurrency = $section['currency'] ?? 'EGP';
                                                $secRemaining = (float) ($section['remaining'] ?? 0);
                                                $secLabel = $section['label'] ?? $secKey;
                                            @endphp
                                            <tr class="section-row child-of-{{ $invoice['id'] }}"
                                                data-invoice-id="{{ $invoice['id'] }}"
                                                data-section="{{ $secKey }}"
                                                data-currency="{{ $secCurrency }}">
                                                <td class="ps-4">
                                                    <input type="checkbox"
                                                           class="section-checkbox"
                                                           data-invoice-id="{{ $invoice['id'] }}"
                                                           data-section="{{ $secKey }}"
                                                           data-currency="{{ $secCurrency }}"
                                                           data-remaining="{{ $secRemaining }}">
                                                    <input type="hidden"
                                                           class="alloc-invoice-id"
                                                           name="allocations[{{ $invoiceIndex }}_{{ $secKey }}][invoice_id]"
                                                           value="{{ $invoice['id'] }}"
                                                           disabled>
                                                    <input type="hidden"
                                                           class="alloc-section"
                                                           name="allocations[{{ $invoiceIndex }}_{{ $secKey }}][section]"
                                                           value="{{ $secKey }}"
                                                           disabled>
                                                </td>
                                                <td class="ps-4">↳ {{ $secLabel }}</td>
                                                <td>{{ $invoice['booking_number'] }}</td>
                                                <td></td>
                                                <td class="text-danger">
                                                    {{ number_format($secRemaining, 2) }}
                                                    {{ $secCurrency === 'USD' ? '$' : 'ج.م' }}
                                                </td>
                                                <td style="max-width: 140px;">
                                                    <input type="number"
                                                           step="0.01"
                                                           min="0.01"
                                                           max="{{ $secRemaining }}"
                                                           class="form-control form-control-sm section-amount-input"
                                                           name="allocations[{{ $invoiceIndex }}_{{ $secKey }}][amount]"
                                                           value="{{ number_format($secRemaining, 2, '.', '') }}"
                                                           data-remaining="{{ $secRemaining }}"
                                                           disabled>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3">
                            <strong>المجموع المحدد حسب عملة السداد:</strong>
                            <span id="selected_total" class="font-weight-bold">0.00</span>
                            <span id="selected_currency_label">ج.م</span>
                            <span id="selected_count" class="ml-3">(0 بند)</span>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        لا توجد فواتير غير مسددة
                    </div>
                @endif
            </div>

            <!-- معلومات الرصيد الافتتاحي -->
            <div id="opening_balance_section" style="display: none;">
                <div class="alert alert-info">
                    <h5 class="font-weight-bold mb-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        سداد الرصيد الافتتاحي
                    </h5>
                    <p class="mb-2">
                        <strong>افتتاحي ج.م:</strong>
                        <span class="text-danger font-weight-bold">{{ number_format($company->opening_balance ?? 0, 2) }} ج.م</span>
                    </p>
                    <p class="mb-2">
                        <strong>افتتاحي $:</strong>
                        <span class="text-danger font-weight-bold">{{ number_format($company->opening_balance_usd ?? 0, 2) }} $</span>
                    </p>
                    <p class="mb-0">
                        <small class="text-muted">
                            اختر عملة السداد؛ يُخصم من الرصيد الافتتاحي لنفس العملة فقط.
                        </small>
                    </p>
                </div>
            </div>

            <!-- نموذج السداد -->
            <form action="{{ route('accounts.payment.process', $company->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="invoice_ids" id="invoice_ids_input" value="">

                <!-- نوع السداد: فواتير أو رصيد افتتاحي -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold required-field">نوع السداد <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="custom-control custom-radio">
                                        <input type="radio"
                                               class="custom-control-input"
                                               id="payment_type_invoices"
                                               name="payment_target"
                                               value="invoices"
                                               checked
                                               onchange="togglePaymentTarget()">
                                        <label class="custom-control-label" for="payment_type_invoices">
                                            <strong>سداد الفواتير</strong>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="custom-control custom-radio">
                                        <input type="radio"
                                               class="custom-control-input"
                                               id="payment_type_opening_balance"
                                               name="payment_target"
                                               value="opening_balance"
                                               onchange="togglePaymentTarget()">
                                        <label class="custom-control-label" for="payment_type_opening_balance">
                                            <strong>سداد الرصيد الافتتاحي</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold required-field">عملة السداد <span class="text-danger">*</span></label>
                            <select name="currency" id="payment_currency" class="form-control @error('currency') is-invalid @enderror" required>
                                <option value="EGP" {{ old('currency', 'EGP') === 'EGP' ? 'selected' : '' }}>جنيه مصري (ج.م)</option>
                                <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>دولار أمريكي ($)</option>
                            </select>
                            @error('currency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">السداد يخصم فقط من مستحقات نفس العملة.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold required-field">المبلغ <span class="text-danger">*</span></label>
                            <input type="number"
                                   name="amount"
                                   id="amount_input"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   step="0.01"
                                   min="0.01"
                                   value="{{ old('amount') }}"
                                   required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted" id="amount_hint">
                                المستحق ج.م: {{ number_format(($currentBalances['EGP'] ?? $currentBalance), 2) }}
                                —
                                المستحق $: {{ number_format(($currentBalances['USD'] ?? 0), 2) }}
                            </small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold required-field">تاريخ السداد <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="payment_date"
                                   class="form-control @error('payment_date') is-invalid @enderror"
                                   value="{{ old('payment_date', date('Y-m-d')) }}"
                                   required>
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold required-field">نوع طريقة السداد <span class="text-danger">*</span></label>
                            <select name="payment_type"
                                    id="payment_type"
                                    class="form-control @error('payment_type') is-invalid @enderror"
                                    required>
                                <option value="">اختر نوع السداد</option>
                                <option value="bank_transfer" {{ old('payment_type') == 'bank_transfer' ? 'selected' : '' }}>تحويل بنكي</option>
                                <option value="check" {{ old('payment_type') == 'check' ? 'selected' : '' }}>شيك</option>
                            </select>
                            @error('payment_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- حقل اختيار البنك (للتحويل البنكي والشيك) -->
                    <div class="col-md-12" id="bank_transfer_field" style="display: none;">
                        <div class="form-group">
                            <label class="font-weight-bold required-field">البنك <span class="text-danger">*</span></label>
                            <select name="bank_id"
                                    id="bank_id"
                                    class="form-control @error('bank_id') is-invalid @enderror">
                                <option value="">اختر البنك</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>
                                        {{ $bank->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('bank_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- حقول الشيك -->
                    <div class="col-md-12" id="check_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold required-field">اسم البنك <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="check_bank_name"
                                           id="check_bank_name"
                                           class="form-control @error('check_bank_name') is-invalid @enderror"
                                           value="{{ old('check_bank_name') }}"
                                           placeholder="اسم البنك">
                                    @error('check_bank_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold required-field">رقم الشيك <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="check_number"
                                           id="check_number"
                                           class="form-control @error('check_number') is-invalid @enderror"
                                           value="{{ old('check_number') }}"
                                           placeholder="رقم الشيك">
                                    @error('check_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold required-field">قيمة الشيك <span class="text-danger">*</span></label>
                                    <input type="number"
                                           name="check_value"
                                           id="check_value"
                                           class="form-control @error('check_value') is-invalid @enderror"
                                           step="0.01"
                                           min="0.01"
                                           value="{{ old('check_value') }}"
                                           placeholder="قيمة الشيك">
                                    @error('check_value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold required-field">تاريخ استحقاق الشيك <span class="text-danger">*</span></label>
                                    <input type="date"
                                           name="check_due_date"
                                           id="check_due_date"
                                           class="form-control @error('check_due_date') is-invalid @enderror"
                                           value="{{ old('check_due_date') }}">
                                    @error('check_due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">الملاحظات</label>
                            <textarea name="notes"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">صورة الإيصال</label>
                            <input type="file"
                                   name="image"
                                   class="form-control-file @error('image') is-invalid @enderror"
                                   accept="image/*">
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">حجم الصورة: أقل من 2MB</small>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-success btn-lg font-weight-bold">
                        <i class="fas fa-check mr-2"></i>تسجيل السداد
                    </button>
                    <a href="{{ route('accounts.statement', $company->id) }}"
                       class="btn btn-secondary btn-lg">
                        <i class="fas fa-times mr-2"></i>إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
    function setSectionEnabled($row, enabled) {
        var $cb = $row.find('.section-checkbox');
        var $amount = $row.find('.section-amount-input');
        var $hidInvoice = $row.find('.alloc-invoice-id');
        var $hidSection = $row.find('.alloc-section');
        var remaining = parseFloat($amount.data('remaining')) || 0;

        $cb.prop('checked', enabled);
        $amount.prop('disabled', !enabled);
        $hidInvoice.prop('disabled', !enabled);
        $hidSection.prop('disabled', !enabled);
        if (enabled) {
            $amount.val(remaining.toFixed(2));
        }
    }

    function updateSelectedTotal() {
        var total = 0;
        var count = 0;
        var selectedIds = {};
        var currency = $('#payment_currency').val() || 'EGP';

        $('.section-checkbox:checked').each(function() {
            var $row = $(this).closest('tr');
            var rowCurrency = $(this).data('currency') || 'EGP';
            if (rowCurrency !== currency) {
                return;
            }
            var amount = parseFloat($row.find('.section-amount-input').val()) || 0;
            total += amount;
            count++;
            selectedIds[$(this).data('invoice-id')] = true;
        });

        $('#selected_total').text(total.toFixed(2));
        $('#selected_currency_label').text(currency === 'USD' ? '$' : 'ج.م');
        $('#selected_count').text('(' + count + ' بند)');
        $('#invoice_ids_input').val(Object.keys(selectedIds).join(','));
        $('#amount_input').val(total > 0 ? total.toFixed(2) : '');
    }

    $(document).ready(function() {
        $('#select_all').on('change', function() {
            var checked = $(this).prop('checked');
            var currency = $('#payment_currency').val() || 'EGP';
            $('.parent-invoice-checkbox').prop('checked', checked);
            $('.section-row').each(function() {
                var rowCurrency = $(this).data('currency') || 'EGP';
                setSectionEnabled($(this), checked && rowCurrency === currency);
            });
            updateSelectedTotal();
        });

        $('.parent-invoice-checkbox').on('change', function() {
            var invoiceId = $(this).data('invoice-id');
            var checked = $(this).prop('checked');
            var currency = $('#payment_currency').val() || 'EGP';
            $('.section-row.child-of-' + invoiceId).each(function() {
                var rowCurrency = $(this).data('currency') || 'EGP';
                setSectionEnabled($(this), checked && rowCurrency === currency);
            });
            updateSelectedTotal();
        });

        $(document).on('change', '.section-checkbox', function() {
            var $row = $(this).closest('tr');
            setSectionEnabled($row, $(this).prop('checked'));
            var invoiceId = $(this).data('invoice-id');
            var anyChecked = $('.section-row.child-of-' + invoiceId + ' .section-checkbox:checked').length > 0;
            $('.parent-invoice-checkbox[data-invoice-id="' + invoiceId + '"]').prop('checked', anyChecked);
            updateSelectedTotal();
        });

        $(document).on('input', '.section-amount-input', function() {
            var max = parseFloat($(this).data('remaining')) || 0;
            var val = parseFloat($(this).val()) || 0;
            if (val > max) {
                $(this).val(max.toFixed(2));
            }
            updateSelectedTotal();
        });

        $('#payment_currency').on('change', function() {
            var currency = $(this).val() || 'EGP';
            $('.section-checkbox:checked').each(function() {
                if (($(this).data('currency') || 'EGP') !== currency) {
                    setSectionEnabled($(this).closest('tr'), false);
                }
            });
            $('.parent-invoice-checkbox').each(function() {
                var invoiceId = $(this).data('invoice-id');
                var any = $('.section-row.child-of-' + invoiceId + ' .section-checkbox:checked').length > 0;
                $(this).prop('checked', any);
            });
            updateSelectedTotal();
        });

        // تحديث نوع السداد
        $('#payment_type').on('change', function() {
            var paymentType = $(this).val();

            // إخفاء جميع الحقول أولاً
            $('#bank_transfer_field').hide();
            $('#check_fields').hide();

            // إزالة required من جميع الحقول
            $('#bank_id').removeAttr('required');
            $('#check_bank_name').removeAttr('required');
            $('#check_number').removeAttr('required');
            $('#check_value').removeAttr('required');
            $('#check_due_date').removeAttr('required');

            // إظهار الحقول المناسبة حسب نوع السداد
            if (paymentType === 'bank_transfer') {
                $('#bank_transfer_field').show();
                $('#bank_id').attr('required', 'required');
            } else if (paymentType === 'check') {
                $('#bank_transfer_field').show();
                $('#check_fields').show();
                $('#bank_id').attr('required', 'required');
                $('#check_bank_name').attr('required', 'required');
                $('#check_number').attr('required', 'required');
                $('#check_value').attr('required', 'required');
                $('#check_due_date').attr('required', 'required');
            }
        });

        // تشغيل عند تحميل الصفحة إذا كان هناك قيمة قديمة
        $('#payment_type').trigger('change');
    });

    function togglePaymentTarget() {
        var paymentTarget = $('input[name="payment_target"]:checked').val();
        var openingBalance = parseFloat({{ $company->opening_balance ?? 0 }});
        var currentBalance = parseFloat({{ $currentBalance }});

        if (paymentTarget === 'opening_balance') {
            // إخفاء قسم الفواتير
            $('#invoices_section').hide();
            $('#opening_balance_section').show();

            // تحديث hint المبلغ ووضع حد أقصى للرصيد الافتتاحي
            if (openingBalance > 0) {
                $('#amount_hint').text('الرصيد الافتتاحي: ' + openingBalance.toLocaleString('ar-EG', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' جنيه');
                $('#amount_input').attr('max', openingBalance);
            } else {
                $('#amount_hint').text('لا يوجد رصيد افتتاحي');
                $('#amount_input').removeAttr('max');
            }

            // إزالة required من invoice_ids
            $('#invoice_ids_input').val('');
            $('.invoice-checkbox').prop('checked', false);
            updateSelectedTotal();
        } else {
            // إظهار قسم الفواتير
            $('#invoices_section').show();
            $('#opening_balance_section').hide();

            // تحديث hint المبلغ وإزالة الحد الأقصى (لأن السداد للفواتير يمكن أن يكون لأي مبلغ)
            $('#amount_hint').text('الرصيد المستحق: ' + currentBalance.toLocaleString('ar-EG', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' جنيه (يمكن إدخال أي مبلغ)');
            $('#amount_input').removeAttr('max'); // إزالة الحد الأقصى للسماح بأي مبلغ
        }
    }

    // تشغيل عند تحميل الصفحة
    togglePaymentTarget();
</script>
@endpush
@endsection
