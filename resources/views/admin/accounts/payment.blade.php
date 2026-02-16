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
                    <p><strong>الرصيد المستحق:</strong>
                        <span class="text-danger font-weight-bold" style="font-size: 1.2em">
                            {{ number_format($currentBalance, 2) }} جنيه
                        </span>
                    </p>
                </div>
            </div>

            <!-- الفواتير غير المسددة -->
            <div id="invoices_section">
                @if($unpaidInvoices->count() > 0)
                    <div class="mb-4">
                        <h5 class="font-weight-bold mb-3">الفواتير غير المسددة</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead style="background: linear-gradient(135deg, #DC143C 0%, #B22222 100%); color: #fff;">
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="select_all" title="تحديد الكل">
                                        </th>
                                        <th>رقم الفاتورة</th>
                                        <th>رقم الطلب</th>
                                        <th>التاريخ</th>
                                        <th>إجمالي الفاتورة</th>
                                        <th>المسدد</th>
                                        <th>المتبقي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unpaidInvoices as $invoice)
                                        <tr>
                                            <td>
                                                <input type="checkbox"
                                                       name="invoice_ids[]"
                                                       class="invoice-checkbox"
                                                       value="{{ $invoice['id'] }}"
                                                       data-remaining="{{ $invoice['remaining'] }}">
                                            </td>
                                            <td>{{ $invoice['invoice_number'] }}</td>
                                            <td>{{ $invoice['booking_number'] }}</td>
                                            <td>{{ \Carbon\Carbon::parse($invoice['date'])->format('Y-m-d') }}</td>
                                            <td class="font-weight-bold">{{ number_format($invoice['total'], 2) }} ج.م</td>
                                            <td class="text-success">{{ number_format($invoice['paid'], 2) }} ج.م</td>
                                            <td class="text-danger font-weight-bold">{{ number_format($invoice['remaining'], 2) }} ج.م</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3">
                            <strong>المجموع المحدد:</strong> <span id="selected_total" class="font-weight-bold">0.00</span> ج.م
                            <span id="selected_count" class="ml-3">(0 فاتورة)</span>
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
                    @if($company->opening_balance && $company->opening_balance > 0)
                        <p class="mb-2">
                            <strong>الرصيد الافتتاحي الحالي:</strong>
                            <span class="text-danger font-weight-bold" style="font-size: 1.2em">
                                {{ number_format($company->opening_balance, 2) }} ج.م
                            </span>
                        </p>
                        <p class="mb-0">
                            <small class="text-muted">
                                سيتم خصم المبلغ المدفوع من الرصيد الافتتاحي فقط، وليس من الفواتير.
                            </small>
                        </p>
                    @else
                        <p class="mb-0">
                            <span class="text-muted">لا يوجد رصيد افتتاحي حالياً</span>
                        </p>
                    @endif
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
                                @if($company->opening_balance && $company->opening_balance > 0)
                                    الرصيد الافتتاحي: {{ number_format($company->opening_balance, 2) }} جنيه
                                @else
                                    الحد الأقصى: {{ number_format($currentBalance, 2) }} جنيه
                                @endif
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

                    <!-- حقل اختيار البنك (للتحويل البنكي) -->
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
    $(document).ready(function() {
        // تحديد/إلغاء تحديد الكل
        $('#select_all').on('change', function() {
            $('.invoice-checkbox').prop('checked', $(this).prop('checked'));
            updateSelectedTotal();
        });

        // تحديث المجموع عند تغيير الاختيار
        $('.invoice-checkbox').on('change', function() {
            updateSelectedTotal();
            $('#select_all').prop('checked', $('.invoice-checkbox:checked').length === $('.invoice-checkbox').length);
        });

        function updateSelectedTotal() {
            var total = 0;
            var count = 0;
            var selectedIds = [];

            $('.invoice-checkbox:checked').each(function() {
                total += parseFloat($(this).data('remaining')) || 0;
                count++;
                selectedIds.push($(this).val());
            });

            $('#selected_total').text(total.toFixed(2));
            $('#selected_count').text('(' + count + ' فاتورة)');
            $('#invoice_ids_input').val(selectedIds.join(','));
            $('#amount_input').val(total > 0 ? total.toFixed(2) : '');
        }

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
                $('#check_fields').show();
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

            // تحديث hint المبلغ
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

            // تحديث hint المبلغ
            $('#amount_hint').text('الحد الأقصى: ' + currentBalance.toLocaleString('ar-EG', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' جنيه');
            $('#amount_input').attr('max', currentBalance);
        }
    }

    // تشغيل عند تحميل الصفحة
    togglePaymentTarget();
</script>
@endpush
@endsection
