
<?php $__env->startSection('content'); ?>
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                <?php echo e(__('admin.edit_service')); ?>

            </div>
            <div class="mt-3">
                <a href="<?php echo e(route('bookings.show', $booking->id)); ?>" class="btn btn-secondary float-right">
                    <?php echo e(__('main.back')); ?>

                </a>
            </div>
        </div>

        <?php echo $__env->make('admin.components.booking-agent-expenses.form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/bookings/booking-agent-expenses/edit.blade.php ENDPATH**/ ?>