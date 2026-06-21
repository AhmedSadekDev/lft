<div class="col-md-12 mt-2 p-5">
    <!-- Button trigger modal -->
    <?php if(isset($booking)): ?>
        <a href="<?php echo e(route('booking-services.create', ['booking' => $booking->id])); ?>">
            <button class="btn btn-primary float-right" data-target="#serviceModal" type="button" >
                <i class="fa fa-plus text-white"></i> <?php echo e(__('admin.add')); ?>

            </button>
        </a>
    <?php endif; ?>
</div>

<table class="table table-striped" id="extensions_id" style="width:100%">
    <thead>
        <tr>
            <th>
                #
            </th>
            <th>
                <?php echo e(__('admin.service')); ?>

            </th>
            <th>
                <?php echo e(__('admin.note')); ?>

            </th>
            <th>
                <?php echo e(__('admin.cost')); ?>

            </th>
            <th>
                <?php echo e(__('admin.receipt_image')); ?>

            </th>
            <th></th>
        </tr>
    </thead>
    <tbody id="serviceTableRows">
        <?php $__empty_1 = true; $__currentLoopData = isset($booking_services) ? $booking_services : []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr id="service_<?php echo e($service->id); ?>">
                <td>
                    <?php echo e($service->id); ?>

                </td>
                <td>
                    <?php echo e($service->full_name); ?>

                </td>
                <td>
                    <?php echo e($service->note); ?>

                </td>
                <td class="services_total_price" data-price="<?php echo e($service->price); ?>">
                    <?php echo e($service->price); ?>

                </td>
                <td>
                    <?php if(filled($service->getRawOriginal('image')) && $service->image): ?>
                        <a href="<?php echo e($service->image); ?>" target="_blank" rel="noopener" style="display: inline-block;">
                            <img src="<?php echo e($service->image); ?>" alt="Receipt"
                                 style="max-width: 80px; max-height: 80px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #e0e0e0;"
                                 onmouseover="this.style.borderColor='#007bff'"
                                 onmouseout="this.style.borderColor='#e0e0e0'">
                        </a>
                    <?php else: ?>
                        <span class="text-muted"><?php echo e(__('admin.no_receipt_image')); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <?php if(auth()->user()->hasPermissionTo('services.update') && isset($booking)): ?>
                            <a href="<?php echo e(route('booking-services.edit', ['booking' => $booking->id, 'booking_service' => $service->id])); ?>"
                               class="btn btn-icon btn-light btn-hover-primary btn-sm">
                                <i class="fas fa-edit text-primary"></i>
                            </a>
                        <?php endif; ?>
                        <?php if(auth()->user()->hasPermissionTo('services.delete')): ?>
                            <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                                onclick="serviceDelete(event, '<?php echo e($service->id); ?>')">
                                <i class="fas fa-trash text-danger"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <?php endif; ?>

        <?php if(isset($expensesServices)): ?>
            <?php $__currentLoopData = $expensesServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $expense): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr id="agent_expense_<?php echo e($expense->id); ?>">
                    <td>
                        <?php echo e($expense->id); ?>

                    </td>
                    <td>
                        <?php echo e($expense->service->name); ?>

                    </td>
                    <td>
                        <?php echo e($expense->notes); ?>

                    </td>
                    <td class="services_total_price" data-price="<?php echo e($expense->value); ?>">
                        <?php echo e($expense->value); ?>

                    </td>
                    <td>
                        <?php
                            $agentReceiptFile = $expense->getRawOriginal('image_agent_expenses');
                            $agentReceiptUrl = filled($agentReceiptFile)
                                ? asset('Admin/images/expenses/' . $agentReceiptFile)
                                : null;
                        ?>
                        <?php if($agentReceiptUrl): ?>
                            <a href="<?php echo e($agentReceiptUrl); ?>" target="_blank" rel="noopener" style="display: inline-block;">
                                <img src="<?php echo e($agentReceiptUrl); ?>" alt="Receipt"
                                     style="max-width: 80px; max-height: 80px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #e0e0e0;"
                                     onmouseover="this.style.borderColor='#007bff'"
                                     onmouseout="this.style.borderColor='#e0e0e0'">
                            </a>
                        <?php else: ?>
                            <span class="text-muted"><?php echo e(__('admin.no_receipt_image')); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <?php if(auth()->user()->hasPermissionTo('services.update') && isset($booking)): ?>
                                <a href="<?php echo e(route('booking-agent-expenses.edit', ['booking' => $booking->id, 'agent_expense' => $expense->id])); ?>"
                                   class="btn btn-icon btn-light btn-hover-primary btn-sm">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                            <?php endif; ?>
                            <?php if(auth()->user()->hasPermissionTo('services.delete')): ?>
                                <button type="button" class="btn btn-icon btn-light btn-hover-danger btn-sm"
                                    onclick="agentExpenseDelete(event, '<?php echo e($expense->id); ?>')">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>


    </tbody>
</table>

<?php $__env->startPush('js'); ?>
    <script>
        function serviceDelete(e, id) {
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
                    var url = "<?php echo e(route('booking-services.destroy', ':id')); ?>";
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
                            $('#service_' + id).remove();
                            Swal.fire({
                                title: "<?php echo e(__('alerts.success')); ?>",
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            var message = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : (xhr.responseText || thrownError);
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

        function agentExpenseDelete(e, id) {
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
                    var url = "<?php echo e(route('expenses.destroy', ':id')); ?>".replace(':id', id);
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
                            $('#agent_expense_' + id).remove();
                            Swal.fire({
                                title: "<?php echo e(__('alerts.success')); ?>",
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        },
                        error: function(xhr) {
                            var message = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : (xhr.responseText || '<?php echo e(__('alerts.error_occurred')); ?>');
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
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/components/booking-services/table.blade.php ENDPATH**/ ?>