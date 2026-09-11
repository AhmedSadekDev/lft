<?php
    $currentPaymentSource = old(
        'payment_source',
        isset($receipt) ? $receipt->payment_source : 'supplier'
    );
    $currentSupplierId = old(
        'supplier_id',
        $selectedSupplierId ?? (isset($receipt) ? $receipt->supplier_id : null)
    );
    $currentBookingId = old('booking_id', isset($receipt) ? $receipt->booking_id : null);
    $currentBookingNumber = old(
        'booking_number',
        isset($receipt) && $receipt->booking ? $receipt->booking->booking_number : null
    );
    $bookingService = $booking_service ?? ($receipt->bookingService ?? null);
    $currentServiceTypeId = old(
        'service_type_id',
        $service_type_id ?? ($bookingService?->service?->service_category_id)
    );
    $currentServiceId = old('service_id', $bookingService->service_id ?? null);
?>

<?php if($method == 'POST'): ?>
    <?php echo Form::open(['url' => $action, 'method' => $method, 'id' => 'receiptForm', 'files' => true, 'enctype' => 'multipart/form-data']); ?>

<?php elseif($method == 'PUT'): ?>
    <?php echo Form::model($receipt, ['url' => [$action], 'method' => $method, 'id' => 'receiptForm', 'files' => true, 'enctype' => 'multipart/form-data']); ?>

<?php endif; ?>

<div class="card-body">
    <?php if(session('error')): ?>
        <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <div class="alert alert-light border mb-4">
        املأ بيانات الإيصال بنفس طريقة إيصالات الطلب: اختر الطلب + نوع الخدمة + الخدمة، وسيظهر تلقائياً في الطلب وفي قسم الإيصالات بالفاتورة.
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <?php echo Form::label('booking_number', 'رقم البوكينج', ['class' => 'required-field']); ?>

                <?php echo Form::text('booking_number', $currentBookingNumber, [
                    'class' => 'form-control',
                    'id' => 'booking_number',
                    'placeholder' => 'أدخل رقم البوكينج',
                    'autocomplete' => 'off',
                ]); ?>

                <small class="text-muted">مطلوب لربط الإيصال بالطلب والفاتورة</small>
                <?php $__errorArgs = ['booking_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger d-block"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php $__errorArgs = ['booking_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger d-block"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <?php echo Form::label('booking_id', 'أو اختر الطلب من القائمة'); ?>

                <select name="booking_id" class="form-control selectpicker" id="booking_id" data-live-search="true">
                    <option value="">اختر الطلب</option>
                    <?php $__currentLoopData = $bookings ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($booking->id); ?>"
                            data-booking-number="<?php echo e($booking->booking_number); ?>"
                            <?php echo e((string) $currentBookingId === (string) $booking->id ? 'selected' : ''); ?>>
                            <?php echo e($booking->booking_number ?: ('#' . $booking->id)); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <?php echo Form::label('service_type_id', __('admin.service_type'), ['class' => 'required-field']); ?>

                <?php echo Form::select(
                    'service_type_id',
                    array_replace(['to_be_disabled' => __('admin.select')], collect($service_types ?? [])->toArray()),
                    $currentServiceTypeId,
                    ['id' => 'service_type_id', 'class' => 'form-control', 'required' => 'required']
                ); ?>

                <?php $__errorArgs = ['service_type_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <?php echo Form::label('service_id', __('admin.service'), ['class' => 'required-field']); ?>

                <?php
                    $serviceOptions = ['to_be_disabled' => __('admin.select')];
                    if ($bookingService && $bookingService->service) {
                        $serviceOptions[$bookingService->service_id] = $bookingService->service->name;
                    }
                ?>
                <?php echo Form::select('service_id', $serviceOptions, $currentServiceId, [
                    'id' => 'service_id',
                    'class' => 'form-control',
                    'required' => 'required',
                ]); ?>

                <?php $__errorArgs = ['service_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <?php echo Form::label('cost', 'التكلفة', ['class' => 'required-field']); ?>

                <?php echo Form::number('cost', old('cost', isset($receipt) ? $receipt->cost : null), [
                    'class' => 'form-control',
                    'id' => 'cost',
                    'step' => '0.01',
                    'min' => '0',
                    'required' => true,
                ]); ?>

                <?php $__errorArgs = ['cost'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <?php echo Form::label('payment_source', 'مصدر الدفع / نوع الدفع', ['class' => 'required-field']); ?>

                <?php echo Form::select('payment_source', [
                    '' => 'اختر نوع الدفع',
                    'supplier' => 'مورد',
                    'safe' => 'الخزنة',
                    'representative' => 'مندوب',
                ], $currentPaymentSource, [
                    'class' => 'form-control',
                    'id' => 'payment_source',
                    'required' => true,
                ]); ?>

                <?php $__errorArgs = ['payment_source'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="col-md-6 js-supplier-fields" style="display: none;">
            <div class="form-group">
                <?php echo Form::label('supplier_id', 'المورد', ['class' => 'required-field']); ?>

                <?php echo Form::select('supplier_id', ['' => 'اختر المورد'] + collect($suppliers ?? [])->toArray(), $currentSupplierId, [
                    'class' => 'form-control selectpicker',
                    'id' => 'supplier_id',
                    'data-live-search' => 'true',
                ]); ?>

                <?php $__errorArgs = ['supplier_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="col-md-6 js-supplier-fields" style="display: none;">
            <div class="form-group">
                <?php echo Form::label('supplier_invoice_number', 'رقم فاتورة المورد', ['class' => 'required-field']); ?>

                <?php echo Form::text('supplier_invoice_number', old('supplier_invoice_number', isset($receipt) ? $receipt->supplier_invoice_number : null), [
                    'class' => 'form-control',
                    'id' => 'supplier_invoice_number',
                    'placeholder' => 'رقم فاتورة المورد',
                ]); ?>

                <?php $__errorArgs = ['supplier_invoice_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <?php echo Form::label('notes', 'ملاحظات'); ?>

                <?php echo Form::textarea('notes', old('notes', isset($receipt) ? $receipt->notes : null), [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => __('admin.note'),
                ]); ?>

                <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <?php echo Form::label('image', __('admin.receipt_image')); ?>

                <input type="file" accept="image/*" name="image" id="input_receipt_image" class="form-control">
                <?php if($bookingService && $bookingService->image && $bookingService->getRawOriginal('image')): ?>
                    <div class="mt-3">
                        <small class="text-muted d-block mb-2"><?php echo e(__('admin.current_image')); ?>:</small>
                        <a href="<?php echo e($bookingService->image); ?>" target="_blank">
                            <img src="<?php echo e($bookingService->image); ?>" alt="Receipt"
                                 style="max-width: 180px; max-height: 180px; object-fit: cover; border-radius: 8px; border: 2px solid #e0e0e0;">
                        </a>
                    </div>
                <?php endif; ?>
                <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
    </div>
</div>

<div class="card-footer">
    <?php if($method == 'POST'): ?>
        <?php echo Form::submit(__('admin.save'), ['class' => 'btn btn-primary']); ?>

    <?php else: ?>
        <?php echo Form::submit(__('admin.update'), ['class' => 'btn btn-primary']); ?>

    <?php endif; ?>
</div>
<?php echo Form::close(); ?>


<?php $__env->startPush('js'); ?>
<script>
    function toggleSupplierFields() {
        var source = document.getElementById('payment_source').value;
        var fields = document.querySelectorAll('.js-supplier-fields');
        var supplierSelect = document.getElementById('supplier_id');
        var invoiceInput = document.getElementById('supplier_invoice_number');

        if (source === 'supplier') {
            fields.forEach(function (el) { el.style.display = 'block'; });
            supplierSelect.setAttribute('required', 'required');
            invoiceInput.setAttribute('required', 'required');
        } else {
            fields.forEach(function (el) { el.style.display = 'none'; });
            supplierSelect.removeAttribute('required');
            invoiceInput.removeAttribute('required');
        }
    }

    function loadServices(serviceTypeId, selectedServiceId) {
        if (!serviceTypeId || serviceTypeId === 'to_be_disabled') {
            return;
        }

        var url = "<?php echo e(route('services.getServices', ':id')); ?>".replace(':id', serviceTypeId);

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        $.ajax({
            url: url,
            type: 'GET',
            success: function (res) {
                $('#service_id').empty();
                $('#service_id').append(`<option value="to_be_disabled"><?php echo e(__('admin.choose_service')); ?></option>`);
                $.each(res, function (i, v) {
                    var selected = (selectedServiceId && String(i) === String(selectedServiceId)) ? 'selected' : '';
                    $('#service_id').append(`<option value="${i}" ${selected}>${v}</option>`);
                });
            }
        });
    }

    document.getElementById('payment_source').addEventListener('change', toggleSupplierFields);
    toggleSupplierFields();

    $('#booking_id').on('changed.bs.select change', function () {
        var selected = $(this).find('option:selected');
        var number = selected.data('booking-number') || '';
        if ($(this).val()) {
            $('#booking_number').val(number);
        }
    });

    $('#service_type_id').on('change', function () {
        loadServices($(this).val());
    });

    $(document).ready(function () {
        var serviceTypeId = $('#service_type_id').val();
        var selectedServiceId = <?php echo json_encode($currentServiceId, 15, 512) ?>;
        if (serviceTypeId && serviceTypeId !== 'to_be_disabled') {
            loadServices(serviceTypeId, selectedServiceId);
        }
    });
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/receipts/form.blade.php ENDPATH**/ ?>