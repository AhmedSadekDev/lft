@extends("layouts.admin")

@section("content")
<div class="container-fluid">
    @include("layouts.includes.breadcrumb", [ 'page' => 'سداد حساب - ' . $car->car_number ])

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-money-bill text-success mr-2"></i>
                    سداد حساب - {{ $car->car_number }}
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('accounts.car.statement', $car->id) }}"
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
            <!-- معلومات السيارة والحساب -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات السيارة</h5>
                    <p><strong>رقم السيارة:</strong> {{ $car->car_number }}</p>
                </div>
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات الحساب</h5>
                    <p><strong>الرصيد المستحق (نقلات غير مسددة):</strong>
                        <span class="text-danger font-weight-bold" style="font-size: 1.2em">
                            {{ number_format($currentBalance, 2) }} جنيه
                        </span>
                    </p>
                    <p><strong>الرصيد النهائي (مطابق لكشف الحساب):</strong>
                        <span class="font-weight-bold {{ isset($finalBalance) && $finalBalance >= 0 ? 'text-danger' : 'text-success' }}" style="font-size: 1.1em">
                            {{ number_format($finalBalance ?? 0, 2) }} جنيه
                        </span>
                    </p>
                </div>
            </div>

            <!-- النقلات غير المسددة -->
            @if($unpaidShipments->count() > 0)
                <div class="mb-4">
                    <h5 class="font-weight-bold mb-3">النقلات غير المسددة</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead style="background: linear-gradient(135deg, #DC143C 0%, #B22222 100%); color: #fff;">
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select_all" title="تحديد الكل">
                                    </th>
                                    <th>#</th>
                                    <th>رقم الحاوية</th>
                                    <th>تاريخ النقلة</th>
                                    <th>التكلفة</th>
                                    <th>العهدة</th>
                                    <th>المصروفات الإضافية</th>
                                    <th>المسدد</th>
                                    <th>المتبقي</th>
                                    <th>خروج</th>
                                    <th>تحميل</th>
                                    <th>تسليم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unpaidShipments as $index => $shipment)
                                    <tr>
                                        <td>
                                            <input type="checkbox"
                                                   name="shipment_ids[]"
                                                   class="shipment-checkbox"
                                                   value="{{ $shipment['id'] }}"
                                                   data-remaining="{{ $shipment['remaining'] }}">
                                        </td>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $shipment['container_numbers'] ?: '-' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($shipment['date'])->format('Y-m-d') }}</td>
                                        <td class="font-weight-bold">{{ number_format($shipment['cost'], 2) }} ج.م</td>
                                        <td class="text-info">{{ number_format($shipment['financial_custody'], 2) }} ج.م</td>
                                        <td class="text-warning">{{ number_format($shipment['extra_expenses'], 2) }} ج.م</td>
                                        <td class="text-success">{{ number_format($shipment['paid'], 2) }} ج.م</td>
                                        <td class="text-danger font-weight-bold">{{ number_format($shipment['remaining'], 2) }} ج.م</td>
                                        <td>{{ $shipment['departure'] ?: '-' }}</td>
                                        <td>{{ $shipment['loading'] ?: '-' }}</td>
                                        <td>{{ $shipment['aging'] ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info mt-3">
                        <strong>أقصى متبقي للنقلات المحددة:</strong> <span id="selected_total" class="font-weight-bold">0.00</span> ج.م
                        <span id="selected_count" class="ml-3">(0 نقلة)</span>
                        <div class="small text-muted mt-2">
                            يمكنك إدخال مبلغ أقل في حقل «المبلغ» أدناه لسداد جزئي (توزيع تلقائي من الأقدم للأحدث بين النقلات المحددة).
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    لا توجد نقلات غير مسددة
                </div>
            @endif

            <!-- نموذج السداد -->
            <form action="{{ route('accounts.car.payment.process', $car->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="shipment_ids" id="shipment_ids_input" value="">

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
                            <small class="text-muted" id="amount_hint">اختر نقلاتاً لعرض أقصى مبلغ يمكن سداده منها.</small>
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

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">ملاحظات</label>
                            <textarea name="notes"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">صورة السداد (اختياري)</label>
                            <input type="file"
                                   name="image"
                                   class="form-control @error('image') is-invalid @enderror"
                                   accept="image/*">
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success btn-lg font-weight-bold">
                            <i class="fas fa-check-circle mr-2"></i>
                            تسجيل السداد
                        </button>
                        @if(session('processed_shipments'))
                            @php
                                $receiptIds = session('processed_shipments', []);
                                $receiptAmounts = session('processed_shipment_amounts', []);
                                $receiptQuery = ['carId' => $car->id, 'shipment_ids' => implode(',', $receiptIds)];
                                if (count($receiptAmounts) === count($receiptIds) && count($receiptIds) > 0) {
                                    $receiptQuery['amounts'] = implode(',', array_map(static fn ($v) => (string) (float) $v, $receiptAmounts));
                                }
                            @endphp
                            <a href="{{ route('accounts.car.payment.export.pdf', $receiptQuery) }}"
                               class="btn btn-danger btn-lg font-weight-bold ml-2">
                                <i class="fas fa-file-pdf mr-2"></i>
                                طباعة بيان السداد PDF
                            </a>
                        @endif
                        <a href="{{ route('accounts.car.statement', $car->id) }}" class="btn btn-secondary btn-lg font-weight-bold ml-2">
                            <i class="fas fa-times mr-2"></i>
                            إلغاء
                        </a>
                    </div>
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
            $('.shipment-checkbox').prop('checked', this.checked);
            updateSelectedTotal();
        });

        // تحديث المجموع عند تحديد/إلغاء تحديد نقلة
        $('.shipment-checkbox').on('change', function() {
            updateSelectedTotal();
            // تحديث حالة "تحديد الكل"
            var totalCheckboxes = $('.shipment-checkbox').length;
            var checkedCheckboxes = $('.shipment-checkbox:checked').length;
            $('#select_all').prop('checked', totalCheckboxes === checkedCheckboxes);
        });

        function updateSelectedTotal() {
            var total = 0;
            var count = 0;
            var selectedIds = [];

            $('.shipment-checkbox:checked').each(function() {
                var remaining = parseFloat($(this).data('remaining')) || 0;
                total += remaining;
                count++;
                selectedIds.push($(this).val());
            });

            $('#selected_total').text(total.toLocaleString('ar-EG', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#selected_count').text('(' + count + ' نقلة)');
            $('#shipment_ids_input').val(selectedIds.join(','));
            if (total > 0) {
                $('#amount_input').val(parseFloat(total.toFixed(2)));
                $('#amount_hint').text('أقصى مبلغ يمكن سداده من النقلات المحددة: ' + total.toLocaleString('ar-EG', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ج.م (يمكن تقليل المبلغ لسداد جزئي).');
            } else {
                $('#amount_input').val('');
                $('#amount_hint').text('اختر نقلاتاً لعرض أقصى مبلغ يمكن سداده منها.');
            }
        }

        // منع إرسال النموذج بدون تحديد نقلات أو مبلغ غير صالح
        $('form').on('submit', function(e) {
            var selectedIds = $('#shipment_ids_input').val();
            if (!selectedIds || selectedIds.trim() === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'يجب تحديد نقلات على الأقل'
                });
                return false;
            }
            var maxTotal = 0;
            $('.shipment-checkbox:checked').each(function() {
                maxTotal += parseFloat($(this).data('remaining')) || 0;
            });
            var amount = parseFloat($('#amount_input').val());
            if (!amount || amount < 0.01) {
                e.preventDefault();
                Swal.fire({ icon: 'error', title: 'خطأ', text: 'أدخل مبلغاً صحيحاً (0.01 على الأقل)' });
                return false;
            }
            if (amount - maxTotal > 0.009) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'المبلغ أكبر من إجمالي المتبقي للنقلات المحددة (' + maxTotal.toFixed(2) + ' ج.م)'
                });
                return false;
            }
        });
    });
</script>
@endpush

@endsection
