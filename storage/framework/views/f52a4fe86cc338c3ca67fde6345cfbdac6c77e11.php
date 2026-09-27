

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => 'سداد مورد - ' . $supplier->name], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-money-bill text-success mr-2"></i>
                    سداد مورد - <?php echo e($supplier->name); ?>

                </h3>
            </div>
            <div class="card-toolbar">
                <a href="<?php echo e(route('suppliers.statement', $supplier)); ?>" class="btn btn-primary font-weight-bold shadow-sm">
                    <i class="fas fa-file-invoice"></i> كشف الحساب
                </a>
            </div>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success m-3"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="alert alert-danger m-3"><?php echo e(session('error')); ?></div>
        <?php endif; ?>

        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات المورد</h5>
                    <p><strong>الاسم:</strong> <?php echo e($supplier->name); ?></p>
                    <p><strong>الرصيد المستحق:</strong>
                        <span class="text-danger font-weight-bold" style="font-size: 1.2em">
                            <?php echo e(number_format((float) $supplier->balance, 2)); ?> جنيه
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <h5 class="font-weight-bold">رصيد الخزنة</h5>
                    <p><strong>المتاح:</strong>
                        <?php echo e(number_format((float) optional($vault)->amount ?? 0, 2)); ?> جنيه
                    </p>
                </div>
            </div>

            <form method="POST" action="<?php echo e(route('suppliers.payment.process', $supplier)); ?>" id="supplierPaymentForm">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label class="font-weight-bold required-field">المبلغ</label>
                    <input type="number" name="amount" step="0.01" min="0.01"
                           value="<?php echo e(old('amount')); ?>"
                           class="form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           required>
                    <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold required-field">مصدر السداد</label>
                    <select name="source_type" id="source_type"
                            class="form-control <?php $__errorArgs = ['source_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                        <option value="">اختر المصدر</option>
                        <option value="safe" <?php echo e(old('source_type') === 'safe' ? 'selected' : ''); ?>>الخزنة</option>
                        <option value="representative" <?php echo e(old('source_type') === 'representative' ? 'selected' : ''); ?>>مندوب</option>
                    </select>
                    <?php $__errorArgs = ['source_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group" id="agent_source_wrap" style="display: none;">
                    <label class="font-weight-bold required-field">المندوب</label>
                    <select name="source_id" id="source_id"
                            class="form-control <?php $__errorArgs = ['source_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <option value="">اختر المندوب</option>
                        <?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($agent->id); ?>"
                                    data-wallet="<?php echo e($agent->wallet); ?>"
                                    <?php echo e((string) old('source_id') === (string) $agent->id ? 'selected' : ''); ?>>
                                <?php echo e($agent->name); ?> — رصيد: <?php echo e(number_format((float) $agent->wallet, 2)); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['source_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">ملاحظات</label>
                    <textarea name="notes" rows="3" class="form-control"><?php echo e(old('notes')); ?></textarea>
                </div>

                <button type="submit" class="btn btn-success font-weight-bold"
                        onclick="return confirm('تأكيد تسجيل السداد؟');">
                    <i class="fas fa-check"></i> تسجيل السداد
                </button>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\suppliers\payment.blade.php ENDPATH**/ ?>