<div class="form-group">
    <label for="company_id"><?php echo e(__('main.company')); ?></label>
    <select name="company_id" id="company_id" class="form-control selectpicker select-company">
        <option value=""><?php echo e(__('admin.select')); ?></option>
        <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($company->id); ?>" <?php echo e(old('company_id', $booking?->company_id ?? '') == $company->id ? 'selected' : ''); ?>>
                <?php echo e($company->name); ?>

            </option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
</div>
<?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\bookings\company_employee.blade.php ENDPATH**/ ?>