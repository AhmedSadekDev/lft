
<?php $__env->startSection("content"); ?>
<div class="container">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => __('main.staticPages') ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
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
                        <th scope="col"><?php echo e(__('admin.title')); ?></th>
                        <th scope="col"><?php echo e(__('admin.description')); ?></th>
                        <th scope="col"><?php echo e(__('admin.image')); ?></th>
                        <th scope="col"><?php echo e(__('admin.created_at')); ?></th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $staticPages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staticPage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <th scope="row"><?php echo e($staticPage->id); ?></th>
                            <td><?php echo e($staticPage->title); ?></td>
                            <td><?php echo e($staticPage->description); ?></td>
                            <td>
                                <a href="<?php echo e(url($staticPage->image) ?? ''); ?>" target="_blank" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                    <i class="fas fa-image text-danger"></i>
                                </a>
                            </td>
                            <td><?php echo e($staticPage->created_at); ?></td>
                            <td>
                                <?php if(auth()->user()->hasPermissionTo('staticPages.update')): ?>
                                <a href="<?php echo e(route('staticPages.edit',$staticPage->id)); ?>" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                                <?php endif; ?>
                                <?php if(auth()->user()->hasPermissionTo('staticPages.delete')): ?>
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete" onclick="Delete('<?php echo e($staticPage->id); ?>')">
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
            "use strict";s
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
                    var url = '<?php echo e(route("staticPages.destroy", ":id")); ?>';
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
                            console.log(response);
                            location.reload();
                            Swal.fire({
                                title: <?php echo e(__('alerts.done')); ?>,
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

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/staticPages/index.blade.php ENDPATH**/ ?>