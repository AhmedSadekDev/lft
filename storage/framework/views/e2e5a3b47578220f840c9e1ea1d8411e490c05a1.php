

<?php $__env->startSection("content"); ?>
<div class="container-fluid">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => 'سداد حساب - ' . $car->car_number ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-money-bill text-success mr-2"></i>
                    سداد حساب - <?php echo e($car->car_number); ?>

                </h3>
            </div>
            <div class="card-toolbar">
                <a href="<?php echo e(route('accounts.car.statement', $car->id)); ?>"
                   class="btn btn-primary font-weight-bold shadow-sm">
                    <i class="fas fa-file-invoice"></i> كشف الحساب
                </a>
            </div>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show m-3">
                <?php echo e(session('success')); ?>

                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show m-3">
                <?php echo e(session('error')); ?>

                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if(session('payment_group_uuid')): ?>
            <?php
                $receiptPrintUrl = route('accounts.car.statement.payment-receipt', [
                    'carId' => $car->id,
                    'group' => session('payment_group_uuid'),
                ]);
                $receiptIds = session('processed_shipments', []);
                $receiptAmounts = session('processed_shipment_amounts', []);
                $receiptPdfQuery = ['carId' => $car->id, 'shipment_ids' => implode(',', $receiptIds)];
                if (count($receiptAmounts) === count($receiptIds) && count($receiptIds) > 0) {
                    $receiptPdfQuery['amounts'] = implode(',', array_map(static fn ($v) => (string) (float) $v, $receiptAmounts));
                }
            ?>
            <div class="mx-3 mb-0">
                <div class="card border-info shadow-sm">
                    <div class="card-header bg-info text-white font-weight-bold py-3">
                        <i class="fas fa-file-invoice mr-2"></i> بيان سداد نقلات (معاينة وطباعة — نفس شكل كشف الحساب)
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            المعاينة أدناه مطابقة لبيان السداد في كشف الحساب. استخدم «طباعة البيان» أو «فتح في نافذة جديدة» ثم طباعة من المتصفح.
                        </p>
                        <iframe id="carPaymentPageReceiptIframe"
                                class="w-100 border rounded"
                                title="بيان سداد نقلات"
                                data-src="<?php echo e($receiptPrintUrl); ?>"
                                style="height: 480px; min-height: 320px; background: #fff;"></iframe>
                        <div class="d-flex flex-wrap align-items-center mt-3">
                            <button type="button" class="btn btn-primary font-weight-bold js-print-car-payment-page-receipt ml-2 mb-2">
                                <i class="fas fa-print ml-1"></i> طباعة البيان
                            </button>
                            <a href="<?php echo e($receiptPrintUrl); ?>"
                               class="btn btn-outline-primary font-weight-bold ml-2 mb-2"
                               target="_blank"
                               rel="noopener">
                                <i class="fas fa-external-link-alt ml-1"></i> فتح في نافذة جديدة
                            </a>
                            <?php if(count($receiptIds) > 0): ?>
                                <a href="<?php echo e(route('accounts.car.payment.export.pdf', $receiptPdfQuery)); ?>"
                                   class="btn btn-outline-danger font-weight-bold ml-2 mb-2">
                                    <i class="fas fa-file-pdf ml-1"></i> تحميل PDF
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif(session('processed_shipments')): ?>
            <?php
                $receiptIds = session('processed_shipments', []);
                $receiptAmounts = session('processed_shipment_amounts', []);
                $receiptPdfQuery = ['carId' => $car->id, 'shipment_ids' => implode(',', $receiptIds)];
                if (count($receiptAmounts) === count($receiptIds) && count($receiptIds) > 0) {
                    $receiptPdfQuery['amounts'] = implode(',', array_map(static fn ($v) => (string) (float) $v, $receiptAmounts));
                }
            ?>
            <div class="alert alert-light border m-3 mb-0">
                <a href="<?php echo e(route('accounts.car.payment.export.pdf', $receiptPdfQuery)); ?>"
                   class="btn btn-danger font-weight-bold">
                    <i class="fas fa-file-pdf mr-2"></i> طباعة بيان السداد PDF
                </a>
            </div>
        <?php endif; ?>

        <div class="card-body">
            <!-- معلومات السيارة والحساب -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات السيارة</h5>
                    <p><strong>رقم السيارة:</strong> <?php echo e($car->car_number); ?></p>
                </div>
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات الحساب</h5>
                    <p><strong>الرصيد المستحق (نقلات غير مسددة):</strong>
                        <span class="text-danger font-weight-bold" style="font-size: 1.2em">
                            <?php echo e(number_format($currentBalance, 2)); ?> جنيه
                        </span>
                    </p>
                    <p><strong>الرصيد النهائي (مطابق لكشف الحساب):</strong>
                        <span class="font-weight-bold <?php echo e(isset($finalBalance) && $finalBalance >= 0 ? 'text-danger' : 'text-success'); ?>" style="font-size: 1.1em">
                            <?php echo e(number_format($finalBalance ?? 0, 2)); ?> جنيه
                        </span>
                    </p>
                </div>
            </div>

            <!-- النقلات غير المسددة -->
            <?php if($unpaidShipments->count() > 0): ?>
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
                                <?php $__currentLoopData = $unpaidShipments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $shipment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox"
                                                   name="shipment_ids[]"
                                                   class="shipment-checkbox"
                                                   value="<?php echo e($shipment['id']); ?>"
                                                   data-remaining="<?php echo e($shipment['remaining']); ?>">
                                        </td>
                                        <td><?php echo e($index + 1); ?></td>
                                        <td><?php echo e($shipment['container_numbers'] ?: '-'); ?></td>
                                        <td><?php echo e(\Carbon\Carbon::parse($shipment['date'])->format('Y-m-d')); ?></td>
                                        <td class="font-weight-bold"><?php echo e(number_format($shipment['cost'], 2)); ?> ج.م</td>
                                        <td class="text-info"><?php echo e(number_format($shipment['financial_custody'], 2)); ?> ج.م</td>
                                        <td class="text-warning"><?php echo e(number_format($shipment['extra_expenses'], 2)); ?> ج.م</td>
                                        <td class="text-success"><?php echo e(number_format($shipment['paid'], 2)); ?> ج.م</td>
                                        <td class="text-danger font-weight-bold"><?php echo e(number_format($shipment['remaining'], 2)); ?> ج.م</td>
                                        <td><?php echo e($shipment['departure'] ?: '-'); ?></td>
                                        <td><?php echo e($shipment['loading'] ?: '-'); ?></td>
                                        <td><?php echo e($shipment['aging'] ?: '-'); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
            <?php else: ?>
                <div class="alert alert-warning">
                    لا توجد نقلات غير مسددة
                </div>
            <?php endif; ?>

            <!-- نموذج السداد -->
            <form action="<?php echo e(route('accounts.car.payment.process', $car->id)); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="shipment_ids" id="shipment_ids_input" value="">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold required-field">المبلغ <span class="text-danger">*</span></label>
                            <input type="number"
                                   name="amount"
                                   id="amount_input"
                                   class="form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   step="0.01"
                                   min="0.01"
                                   value="<?php echo e(old('amount')); ?>"
                                   required>
                            <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <small class="text-muted" id="amount_hint">اختر نقلاتاً لعرض أقصى مبلغ يمكن سداده منها.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold required-field">تاريخ السداد <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="payment_date"
                                   class="form-control <?php $__errorArgs = ['payment_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   value="<?php echo e(old('payment_date', date('Y-m-d'))); ?>"
                                   required>
                            <?php $__errorArgs = ['payment_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">ملاحظات</label>
                            <textarea name="notes"
                                      class="form-control <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                      rows="3"><?php echo e(old('notes')); ?></textarea>
                            <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">صورة السداد (اختياري)</label>
                            <input type="file"
                                   name="image"
                                   class="form-control <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   accept="image/*">
                            <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success btn-lg font-weight-bold">
                            <i class="fas fa-check-circle mr-2"></i>
                            تسجيل السداد
                        </button>
                        <a href="<?php echo e(route('accounts.car.statement', $car->id)); ?>" class="btn btn-secondary btn-lg font-weight-bold ml-2">
                            <i class="fas fa-times mr-2"></i>
                            إلغاء
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $__env->startPush('js'); ?>
<script>
    $(document).ready(function() {
        var $receiptIframe = $('#carPaymentPageReceiptIframe');
        if ($receiptIframe.length) {
            var src = $receiptIframe.data('src');
            if (src) {
                $receiptIframe.attr('src', src);
            }
        }

        $(document).on('click', '.js-print-car-payment-page-receipt', function () {
            var iframe = document.getElementById('carPaymentPageReceiptIframe');
            if (!iframe || !iframe.contentWindow) {
                return;
            }
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) {
                var fallback = iframe.getAttribute('src');
                if (fallback) {
                    window.open(fallback, '_blank');
                }
            }
        });

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
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\accounts\car-payment.blade.php ENDPATH**/ ?>