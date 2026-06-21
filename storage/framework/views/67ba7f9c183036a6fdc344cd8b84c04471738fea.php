<div class="col-md-12 mt-2 p-5">
    <a href="<?php echo e(route('booking-containers.create', ['booking' => $booking->id])); ?>">
        <button class="btn btn-primary float-right" data-target="#serviceModal" type="button">
            <i class="fa fa-plus text-white"></i> <?php echo e(__('admin.add')); ?>

        </button>
    </a>
</div>

<div class="table-responsive">
    <table class="table table-striped" id="transportation_id" style="width:100%">
        <thead>
            <tr>
                <?php if($booking->type_of_action != 2): ?>
                    <th>
                        <?php echo e(__('admin.container_no')); ?>

                    </th>
                    <th>
                        <?php echo e(__('admin.navigational_torrent')); ?>

                    </th>
                <?php endif; ?>
                <th>
                    <?php echo e(__('admin.container_type')); ?>

                </th>
                <th>
                    <div class="col-md-12">
                        <?php echo e(__('admin.factory') . ':'); ?>

                    </div>
                    <div class="col-md-12">
                        <small class="badge badge-pill badge-light">
                            <?php echo e(__('admin.branch')); ?>

                        </small>
                    </div>
                </th>
                <th>
                    <?php echo e(__('admin.departure_location')); ?>

                </th>
                <th>
                    <?php echo e(__('admin.loading_location')); ?>

                </th>
                <th>
                    <?php echo e(__('admin.aging_location')); ?>

                </th>
                <th>
                    <?php echo e(__('admin.cost')); ?>

                </th>
                <th><?php echo e(__('admin.action')); ?></th>
            </tr>
        </thead>
        <tbody id="transportationsTableRows">
            <?php if(!is_null($booking->bookingContainers)): ?>
                <?php $__currentLoopData = $booking->bookingContainers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $container): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr id="transportation_<?php echo e($container->id); ?>">
                        <?php if($booking->type_of_action != 2): ?>
                            <td><?php echo e($container->container_no); ?></td>
                            <td><?php echo e($container->sail_of_number); ?></td>
                        <?php endif; ?>
                        <td><?php echo e($container->container?->full_name); ?></td>
                        <td>
                            <div class="col-md-12">
                                <?php echo e($container->branch?->factory->name . ':'); ?>

                            </div>
                            <div class="col-md-12">
                                <small class="badge badge-pill badge-light">
                                    <?php echo e($container->branch?->name); ?>

                                </small>
                            </div>
                        </td>
                        <td><?php echo e($container->departure->title ?? ''); ?></td>
                        <td><?php echo e($container->loading->title ?? ''); ?></td>
                        <td><?php echo e($container->aging->title ?? ''); ?></td>

                        <td class="prices"><?php echo e($container->price); ?></td>

                        <td>
                            <div class="d-flex">
                                <a class="mx-2"
                                    href="<?php echo e(route('expenses.booking_container_expenses', $container->id)); ?>">
                                    <?php echo e(__('main.expenses')); ?>

                                </a>

                                <a class="mx-2"
                                    href="<?php echo e(route('bookings.booking_container_papers', $container->id)); ?>">
                                    <?php echo e(__('admin.papers')); ?>

                                </a>

                                <a class="mx-2"
                                    href="<?php echo e(route('bookings.booking_container_policies', $container->id)); ?>">
                                    <?php echo e(__('main.delivery_policies')); ?>

                                </a>

                                <?php if($container->delivery_policies->count()): ?>
                                    <a class="mx-2"
                                        href="<?php echo e(route('booking_contrainer_extra_costs', $container->id)); ?>">
                                        <?php echo e(__('main.extra_expense')); ?>

                                    </a>
                                <?php endif; ?>


                                <!-- Button trigger modal -->
                                <a
                                    href="<?php echo e(route('booking-containers.edit', ['booking_container' => $container->id])); ?>">
                                    <button class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3" type="button">
                                        <i class="fas fa-edit text-primary"></i>
                                    </button>
                                </a>



                                <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                                    onclick="containerDelete(event, '<?php echo e($container->id); ?>')">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $__env->startPush('js'); ?>
    <script>
        function containerDelete(e, id) {
            e.preventDefault();
            Swal.fire({
                title: "<?php echo e(__('alerts.are_you_sure')); ?>",
                text: "<?php echo e(__('alerts.not_revert_information')); ?>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "<?php echo e(__('alerts.confirm')); ?>",
                cancelButtonText: "<?php echo e(__('alerts.cancel')); ?>",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "<?php echo e(route('booking-containers.destroy', ':id')); ?>";
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
                        success: function(response) {
                            $('#transportation_' + id).remove();
                            Swal.fire({
                                title: "<?php echo e(__('alerts.success')); ?>",
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            var message = xhr.responseJSON.message;
                            Swal.fire({
                                title: message,
                                icon: 'error',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        }
                    });
                }
            });
        }
    </script>
<?php $__env->stopPush(); ?>
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/components/booking-containers/table.blade.php ENDPATH**/ ?>