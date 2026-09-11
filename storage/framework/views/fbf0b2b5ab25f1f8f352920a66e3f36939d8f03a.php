<?php $__env->startSection("content"); ?>
<div class="container">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => __('main.expenses') ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5">
            <div class="card-toolbar">
                <!--begin::Button-->

                <!--end::Button-->
            </div>
        </div>
        <div class="card-body">
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">صورة</th>
                        <th scope="col"><?php echo e(__('admin.title')); ?></th>
                        <th scope="col"><?php echo e(__('admin.value')); ?></th>
                        <th scope="col"><?php echo e(__('main.date')); ?></th>
                        <th scope="col"><?php echo e(__('admin.actions')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $allExpenses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allExpense): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                            <th scope="row"><?php echo e($allExpense->id); ?></th>
                            <td ><?php if($allExpense->image !== null): ?> <img
                                    src="<?php echo e(asset('Admin/images/expenses/' . $allExpense->image_agent_expenses)); ?>" alt="صورة الايصال"
                                    style="width: 100px;" /> <?php else: ?> لا توجد صورة <?php endif; ?></td>
                            <td><?php echo e($allExpense->title ?? ""); ?></td>
                            <td><?php echo e($allExpense->value ?? ""); ?></td>
                            <td><?php echo e($allExpense->created_at ?? ""); ?></td>
                            <td>
                                <?php if(isset($allExpense->agent_id) && $allExpense instanceof \App\Models\AgentExpense): ?>
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                                        onclick="DeleteExpense('<?php echo e($allExpense->id); ?>')">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
    <!--end::Card-->
</div>


<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <script>

        function DeleteExpense(id) {
            Swal.fire({
                title: "<?php echo e(__('alerts.are_you_sure')); ?>",
                text: "<?php echo e(__('alerts.not_revert_information')); ?>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "<?php echo e(__('alerts.confirm')); ?>",
                cancelButtonText: "<?php echo e(__('alerts.cancel')); ?>",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = '<?php echo e(route("expenses.destroy", ":id")); ?>';
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
                        type: 'delete',
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


<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/agents/expenses/index.blade.php ENDPATH**/ ?>