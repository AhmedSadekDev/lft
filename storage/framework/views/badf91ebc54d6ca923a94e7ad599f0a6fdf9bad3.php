<?php if(isset($compnyEmployees)): ?>

<?php $__currentLoopData = $compnyEmployees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($employee->id); ?>"><?php echo e($employee->name); ?></option>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php endif; ?>
<?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\bookings\_employees.blade.php ENDPATH**/ ?>