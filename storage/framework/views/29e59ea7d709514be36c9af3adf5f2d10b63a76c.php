
<?php $__env->startSection('content'); ?>
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                <?php echo e(__('main.extra_expense')); ?>

            </div>
            <div class="mt-3">
                <a href="<?php echo e(route('booking_contrainer_extra_costs', $bookingContainer->id)); ?>" class="btn btn-secondary float-right">
                    <?php echo e(__('main.back')); ?>

                </a>
            </div>
        </div>
        <div class="card-body">
            <form id="myForm" action="<?php echo e(route('booking_contrainer_extra_costs_store')); ?>" method="post">
              <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label for="name"><?php echo e(__('main.operation_name')); ?></label>
                    <input type="text" class="form-control" id="name" name="name" required>
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label for="value"><?php echo e(__('admin.cost')); ?></label>
                    <input type="number" class="form-control" id="value" name="value" required>
                    <?php $__errorArgs = ['value'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>


                <div class="form-group">
                    <label for="booking_container_id"><?php echo e(__('main.container')); ?></label>
                    <select class="form-control" name="booking_container_id" id="booking_container_id">
                        <?php $__currentLoopData = $booking_containers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking_container): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($booking_container->id); ?>" <?php if($booking_container->id == $bookingContainer->id): ?> selected <?php endif; ?>>
                                <?php echo e($booking_container->container_no); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['booking_container_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                      <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label for="car_id"><?php echo e(__("admin.car_number")); ?></label>
                    <select class="form-control" name="car_id" id="car_id">
                        <?php $__currentLoopData = $cars; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $car): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($car->id); ?>" <?php if($car->id == $bookingContainer->delivery_policies->first()->car_id): ?> selected <?php endif; ?>>
                                <?php echo e($car->car_number); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['car_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                      <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label for="driver_id"><?php echo e(__("main.drivers")); ?></label>
                    <select class="form-control" name="driver_id" id="driver_id">
                        <?php $__currentLoopData = $drivers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $driver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($driver->id); ?>" <?php if($driver->id == $bookingContainer->delivery_policies->first()->driver_id): ?> selected <?php endif; ?>>
                                <?php echo e($driver->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['driver_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                      <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                  <button type="submit" class="btn btn-primary"><?php echo e(__("admin.save")); ?></button>
                </div>

            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php $__env->startPush('js'); ?>
<script>
    $(document).ready(function() {
        $('#myForm').on('submit', function(e) {
            // Show a confirmation dialog
            var confirmed = confirm("تم إصدار فاتوره لهذا الطلب هل تريد المتابعه ؟");
            
            // If the user clicks "Cancel", prevent the form from submitting
            if (!confirmed) {
                e.preventDefault();
            }
        });
    });
</script>

<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/extra_expenses/create.blade.php ENDPATH**/ ?>