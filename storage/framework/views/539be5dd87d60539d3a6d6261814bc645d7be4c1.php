

<?php $__env->startSection("content"); ?>
<div class="container-fluid">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => 'سداد - ' . $company->name ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-money-bill text-success mr-2"></i>
                    سداد حساب - <?php echo e($company->name); ?>

                </h3>
            </div>
            <div class="card-toolbar">
                <a href="<?php echo e(route('accounts.statement', $company->id)); ?>"
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

        <div class="card-body">
            <!-- معلومات الشركة والحساب -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات الشركة</h5>
                    <p><strong>الاسم:</strong> <?php echo e($company->name); ?></p>
                    <p><strong>البريد:</strong> <?php echo e($company->email); ?></p>
                    <p><strong>الهاتف:</strong> <?php echo e($company->phone); ?></p>
                </div>
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات الحساب</h5>
                    <p><strong>الرصيد المستحق:</strong>
                        <span class="text-danger font-weight-bold" style="font-size: 1.2em">
                            <?php echo e(number_format($currentBalance, 2)); ?> جنيه
                        </span>
                    </p>
                </div>
            </div>

            <!-- الفواتير غير المسددة -->
            <div id="invoices_section">
                <?php if($unpaidInvoices->count() > 0): ?>
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
                                    <?php $__currentLoopData = $unpaidInvoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox"
                                                       name="invoice_ids[]"
                                                       class="invoice-checkbox"
                                                       value="<?php echo e($invoice['id']); ?>"
                                                       data-remaining="<?php echo e($invoice['remaining']); ?>">
                                            </td>
                                            <td><?php echo e($invoice['invoice_number']); ?></td>
                                            <td><?php echo e($invoice['booking_number']); ?></td>
                                            <td><?php echo e(\Carbon\Carbon::parse($invoice['date'])->format('Y-m-d')); ?></td>
                                            <td class="font-weight-bold"><?php echo e(number_format($invoice['total'], 2)); ?> ج.م</td>
                                            <td class="text-success"><?php echo e(number_format($invoice['paid'], 2)); ?> ج.م</td>
                                            <td class="text-danger font-weight-bold"><?php echo e(number_format($invoice['remaining'], 2)); ?> ج.م</td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3">
                            <strong>المجموع المحدد:</strong> <span id="selected_total" class="font-weight-bold">0.00</span> ج.م
                            <span id="selected_count" class="ml-3">(0 فاتورة)</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        لا توجد فواتير غير مسددة
                    </div>
                <?php endif; ?>
            </div>

            <!-- معلومات الرصيد الافتتاحي -->
            <div id="opening_balance_section" style="display: none;">
                <div class="alert alert-info">
                    <h5 class="font-weight-bold mb-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        سداد الرصيد الافتتاحي
                    </h5>
                    <?php if($company->opening_balance && $company->opening_balance > 0): ?>
                        <p class="mb-2">
                            <strong>الرصيد الافتتاحي الحالي:</strong>
                            <span class="text-danger font-weight-bold" style="font-size: 1.2em">
                                <?php echo e(number_format($company->opening_balance, 2)); ?> ج.م
                            </span>
                        </p>
                        <p class="mb-0">
                            <small class="text-muted">
                                سيتم خصم المبلغ المدفوع من الرصيد الافتتاحي فقط، وليس من الفواتير.
                            </small>
                        </p>
                    <?php else: ?>
                        <p class="mb-0">
                            <span class="text-muted">لا يوجد رصيد افتتاحي حالياً</span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- نموذج السداد -->
            <form action="<?php echo e(route('accounts.payment.process', $company->id)); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
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
                            <small class="text-muted" id="amount_hint">
                                <?php if($company->opening_balance && $company->opening_balance > 0): ?>
                                    الرصيد الافتتاحي: <?php echo e(number_format($company->opening_balance, 2)); ?> جنيه
                                <?php else: ?>
                                    الرصيد المستحق: <?php echo e(number_format($currentBalance, 2)); ?> جنيه
                                <?php endif; ?>
                            </small>
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

                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold required-field">نوع طريقة السداد <span class="text-danger">*</span></label>
                            <select name="payment_type"
                                    id="payment_type"
                                    class="form-control <?php $__errorArgs = ['payment_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    required>
                                <option value="">اختر نوع السداد</option>
                                <option value="bank_transfer" <?php echo e(old('payment_type') == 'bank_transfer' ? 'selected' : ''); ?>>تحويل بنكي</option>
                                <option value="check" <?php echo e(old('payment_type') == 'check' ? 'selected' : ''); ?>>شيك</option>
                            </select>
                            <?php $__errorArgs = ['payment_type'];
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

                    <!-- حقل اختيار البنك (للتحويل البنكي والشيك) -->
                    <div class="col-md-12" id="bank_transfer_field" style="display: none;">
                        <div class="form-group">
                            <label class="font-weight-bold required-field">البنك <span class="text-danger">*</span></label>
                            <select name="bank_id"
                                    id="bank_id"
                                    class="form-control <?php $__errorArgs = ['bank_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <option value="">اختر البنك</option>
                                <?php $__currentLoopData = $banks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bank): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($bank->id); ?>" <?php echo e(old('bank_id') == $bank->id ? 'selected' : ''); ?>>
                                        <?php echo e($bank->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['bank_id'];
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

                    <!-- حقول الشيك -->
                    <div class="col-md-12" id="check_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold required-field">اسم البنك <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="check_bank_name"
                                           id="check_bank_name"
                                           class="form-control <?php $__errorArgs = ['check_bank_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           value="<?php echo e(old('check_bank_name')); ?>"
                                           placeholder="اسم البنك">
                                    <?php $__errorArgs = ['check_bank_name'];
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
                                    <label class="font-weight-bold required-field">رقم الشيك <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="check_number"
                                           id="check_number"
                                           class="form-control <?php $__errorArgs = ['check_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           value="<?php echo e(old('check_number')); ?>"
                                           placeholder="رقم الشيك">
                                    <?php $__errorArgs = ['check_number'];
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
                                    <label class="font-weight-bold required-field">قيمة الشيك <span class="text-danger">*</span></label>
                                    <input type="number"
                                           name="check_value"
                                           id="check_value"
                                           class="form-control <?php $__errorArgs = ['check_value'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           step="0.01"
                                           min="0.01"
                                           value="<?php echo e(old('check_value')); ?>"
                                           placeholder="قيمة الشيك">
                                    <?php $__errorArgs = ['check_value'];
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
                                    <label class="font-weight-bold required-field">تاريخ استحقاق الشيك <span class="text-danger">*</span></label>
                                    <input type="date"
                                           name="check_due_date"
                                           id="check_due_date"
                                           class="form-control <?php $__errorArgs = ['check_due_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           value="<?php echo e(old('check_due_date')); ?>">
                                    <?php $__errorArgs = ['check_due_date'];
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
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">الملاحظات</label>
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

                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">صورة الإيصال</label>
                            <input type="file"
                                   name="image"
                                   class="form-control-file <?php $__errorArgs = ['image'];
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
                            <small class="text-muted">حجم الصورة: أقل من 2MB</small>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-success btn-lg font-weight-bold">
                        <i class="fas fa-check mr-2"></i>تسجيل السداد
                    </button>
                    <a href="<?php echo e(route('accounts.statement', $company->id)); ?>"
                       class="btn btn-secondary btn-lg">
                        <i class="fas fa-times mr-2"></i>إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $__env->startPush('js'); ?>
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
        var openingBalance = parseFloat(<?php echo e($company->opening_balance ?? 0); ?>);
        var currentBalance = parseFloat(<?php echo e($currentBalance); ?>);

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
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/accounts/payment.blade.php ENDPATH**/ ?>