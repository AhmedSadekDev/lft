
<?php $__env->startSection("content"); ?>
    <!--begin::Bankd-->
    <div class="bankd bankd-custom gutter-b">
        <div class="bankd-header">
            <div class="bankd-title mb-3">
                <?php echo e(__('main.bank')); ?>

            </div>
        </div>
        <?php echo $__env->make('admin.vaults.form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\vaults\create.blade.php ENDPATH**/ ?>