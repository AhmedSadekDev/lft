
<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => __('main.agents')], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->
                    <?php if(auth()->user()->hasPermissionTo('agents.create')): ?>
                        <a href="<?php echo e(route('agents.create')); ?>" class="btn btn-primary font-weight-bolder">
                            <span class="svg-icon svg-icon-md">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                    width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24" />
                                        <circle fill="#000000" cx="9" cy="15" r="6" />
                                        <path
                                            d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z"
                                            fill="#000000" opacity="0.3" />
                                    </g>
                                </svg>
                                <!--end::Svg Icon-->
                            </span><?php echo e(__('admin.add')); ?>

                        </a>
                    <?php endif; ?>
                    <!--end::Button-->
                </div>
            </div>
            <div class="card-body">
                <table class="table table-responsive-xl" id="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col"><?php echo e(__('admin.name')); ?></th>
                            <th scope="col"><?php echo e(__('admin.email')); ?></th>
                            <th scope="col"><?php echo e(__('admin.phone')); ?></th>
                            <th scope="col"><?php echo e(__('admin.financial_custody')); ?></th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <th scope="row"><?php echo e($agent->id); ?></th>
                                <td>
                                    <a href="<?php echo e(route('agent_car_tranfer.index', $agent->id)); ?>">
                                        <?php echo e($agent->name); ?>

                                    </a>
                                </td>
                                <td>
                                    <?php echo e($agent->email); ?>

                                </td>

                                <td>
                                    <?php echo e($agent->phone); ?>

                                </td>

                                <td>
                                    <?php echo e($agent->wallet); ?>

                                </td>


                                <td>
                                    <div class="row">
                                        <div class="col-md-3 mr-3">
                                            <?php if(auth()->user()->hasPermissionTo('agents.update')): ?>
                                                <a href="<?php echo e(route('agents.edit', $agent->id)); ?>"
                                                    class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                                    <i class="fas fa-edit text-primary"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-3">
                                            <?php if(auth()->user()->hasPermissionTo('agents.delete')): ?>
                                                <button class="btn btn-icon btn-light btn-hover-danger btn-sm mx-3 delete"
                                                    onclick="Delete('<?php echo e($agent->id); ?>')">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <a class="btn btn-primary btn-sm"
                                            href="<?php echo e(route('reports.agent_reports', $agent->id)); ?>"><?php echo e(__('main.reports')); ?></a>
                                        <a class="btn btn-primary btn-sm ml-4 "
                                            href="<?php echo e(route('expenses.agent_expenses', $agent->id)); ?>"><?php echo e(__('main.expenses')); ?></a>

                                    </div>
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
                    var url = '<?php echo e(route('agents.destroy', ':id')); ?>';
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
                            if (xhr.status == 200) {
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

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/agents/index.blade.php ENDPATH**/ ?>