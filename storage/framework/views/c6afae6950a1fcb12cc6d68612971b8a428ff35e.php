
<?php $__env->startSection('content'); ?>
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">إضافة مورد جديد</div>
            <div class="mt-3">
                <a href="<?php echo e(route('suppliers.index')); ?>" class="btn btn-secondary float-right">
                    <?php echo e(__('main.back')); ?>

                </a>
            </div>
        </div>
        <?php echo $__env->make('admin.suppliers.form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/suppliers/create.blade.php ENDPATH**/ ?>