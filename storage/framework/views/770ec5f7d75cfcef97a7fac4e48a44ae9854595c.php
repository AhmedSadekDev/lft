
<?php $__env->startSection("content"); ?>
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                <?php echo e(__('main.delivery_policies')); ?>

            </div>
            <div class="mt-3">
                <a href="<?php echo e(route('bookings.show', $booking->id)); ?>" class="btn btn-secondary float-right">
                    <?php echo e(__('main.back')); ?>

                </a>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col"><?php echo e(__('main.drivers')); ?></th>
                        <th scope="col"><?php echo e(__('admin.value')); ?></th>
                        <th scope="col"><?php echo e(__('admin.car_number')); ?></th>
                        <th scope="col"><?php echo e(__('main.date')); ?></th>
                        <th scope="col"><?php echo e(__('admin.actions')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $booking_policies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allExpense): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                            <th scope="row"><?php echo e($allExpense->id); ?></th>

                            <td><?php echo e($allExpense->driver->name ?? ""); ?></td>
                            <td><?php echo e($allExpense->money_transfer->value ?? ""); ?></td>
                            <td><?php echo e($allExpense->car->car_number ?? ""); ?></td>
                            <td><?php echo e($allExpense->created_at ?? ""); ?></td>
                            <td>
                                <?php if($allExpense->is_settled != 1): ?>
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                                        onclick="deleteDeliveryPolicy('<?php echo e($allExpense->id); ?>')">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="badge badge-success"><?php echo e(__('admin.settle')); ?></span>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <script>
        function deleteDeliveryPolicy(id) {
            Swal.fire({
                title: "<?php echo e(__('alerts.are_you_sure')); ?>",
                text: "<?php echo e(__('alerts.not_revert_information')); ?>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "<?php echo e(__('alerts.confirm')); ?>",
                cancelButtonText: "<?php echo e(__('alerts.cancel')); ?>",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = '<?php echo e(route("bookings.delete_delivery_policy", ":id")); ?>';
                    url = url.replace(':id', id);
                    var token = '<?php echo e(csrf_token()); ?>';
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        method: 'DELETE',
                        success: function(response, textStatus, xhr) {
                            Swal.fire({
                                title: "<?php echo e(__('alerts.done')); ?>",
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                            location.reload();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: "<?php echo e(__('alerts.error')); ?>",
                                text: xhr.responseJSON?.message || "<?php echo e(__('alerts.error_occurred')); ?>",
                                icon: 'error',
                            });
                        }
                    });
                }
            });
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/bookings/container_policies.blade.php ENDPATH**/ ?>