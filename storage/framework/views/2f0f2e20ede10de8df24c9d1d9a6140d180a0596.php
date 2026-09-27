

<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => __('admin.vaults')], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="card card-custom">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(__('admin.edit')); ?></h3>
            </div>
            <form method="POST" action="<?php echo e(route('vaultransactions.update', $item->id)); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <div class="card-body">
                    <div class="form-group">
                        <label><?php echo e(__('admin.name')); ?></label>
                        <input type="text" name="name" class="form-control" value="<?php echo e(old('name', $item->name)); ?>" required>
                    </div>

                    <div class="form-group">
                        <label><?php echo e(__('main.amount')); ?></label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo e(old('amount', $item->amount)); ?>" required>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><?php echo e(__('admin.save')); ?></button>
                    <a href="<?php echo e(route('vaultransactions.index')); ?>" class="btn btn-secondary"><?php echo e(__('admin.cancel')); ?></a>
                </div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\vaulttransactions\edit.blade.php ENDPATH**/ ?>