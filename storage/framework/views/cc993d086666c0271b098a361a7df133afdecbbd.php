
<?php $__env->startSection("content"); ?>
<div class="container">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => __('main.reports') ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
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
                        <th scope="col"><?php echo e(__('admin.agent')); ?></th>
                        <th scope="col"><?php echo e(__('admin.action')); ?></th>
                        <th scope="col"><?php echo e(__('main.date')); ?></th>
                        <th scope="col"><?php echo e(__('main.time')); ?></th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $log_activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log_activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                            <th scope="row"><?php echo e($log_activity->id); ?></th>
                            <td>
                                <?php echo e($log_activity?->attacher?->name ?? ""); ?>

                            </td>
                    
                            <td><?php echo e($log_activity->action ?? ""); ?></td>
                            <td><?php echo e($log_activity->date ?? ""); ?></td>
                            <td><?php echo e($log_activity->time ?? ""); ?></td>
                          
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

        function Delete(id) {
            Swal.fire({
                title: "<?php echo e(__('alerts.are_you_sure')); ?>",
                text: "<?php echo e(__('alerts.not_revert_information')); ?>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "<?php echo e(__('alerts.confirm')); ?>",
                cancelButtonText: "<?php echo e(__('alerts.cancel')); ?>",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = '<?php echo e(route("agents.destroy", ":id")); ?>';
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
                            console.log(response, xhr.status);
                            if(xhr.status == 200){
                                Swal.fire({
                                    title: "<?php echo e(__('alerts.done')); ?>",
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                });
                                location.reload();
                                //getNotify();
                            }
                        }
                    });
                }
            });
        }

       
    </script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/agents/reports/index.blade.php ENDPATH**/ ?>