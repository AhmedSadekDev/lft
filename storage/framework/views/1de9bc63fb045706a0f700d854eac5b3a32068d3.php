
<?php $__env->startSection("content"); ?>
    <!--begin::Bankd-->
    <div class="bankd bankd-custom gutter-b">
        <div class="bankd-header">
            <div class="bankd-title">
                <?php echo e(__('main.banks')); ?>

            </div>
        </div>
        <?php echo $__env->make('admin.banks.form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/banks/edit.blade.php ENDPATH**/ ?>