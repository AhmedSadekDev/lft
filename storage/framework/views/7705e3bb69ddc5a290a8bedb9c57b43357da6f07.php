
<?php $__env->startSection("content"); ?>
<div class="container">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => __('main.contactUs') ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-body">
            <table class="table table-responsive-sm" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col"><?php echo e(__('admin.email')); ?></th>
                        <th scope="col"><?php echo e(__('admin.message')); ?></th>
                        <th scope="col"><?php echo e(__('admin.created_at')); ?></th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <th scope="row"><?php echo e($contact->id); ?></th>
                            <td><?php echo e($contact->email); ?></td>
                            <td><?php echo e($contact->message); ?></td>
                            <td><?php echo e($contact->created_at); ?></td>
                            <td>
                                <?php if(auth()->user()->hasPermissionTo('contactUs.delete')): ?>
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete" onclick="Delete('<?php echo e($contact->id); ?>')">
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
        (function($) {
            "use strict";
        })(jQuery);

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
                    var url = '<?php echo e(route("contactUs.destroy", ":id")); ?>';
                    url = url.replace(':id', id);
                    var token = '<?php echo e(csrf_token()); ?>';
                    console.log(url, token);
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
                            console.log(response);
                            location.reload();
                            Swal.fire({
                                title: `<?php echo e(__('alerts.done')); ?>`,
                                icon: 'success',
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

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/contactUs/index.blade.php ENDPATH**/ ?>