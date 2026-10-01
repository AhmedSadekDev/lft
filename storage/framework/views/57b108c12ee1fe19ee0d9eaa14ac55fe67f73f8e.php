
<?php $__env->startSection("content"); ?>
<div class="container">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => __('main.financial_custody_agents') ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5">
            <div class="card-toolbar">
                <!--begin::Button-->

                <a href="<?php echo e($route_create); ?>" class="btn btn-primary font-weight-bolder">
                    <span class="svg-icon svg-icon-md">
                        <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                <rect x="0" y="0" width="24" height="24" />
                                <circle fill="#000000" cx="9" cy="15" r="6" />
                                <path d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z" fill="#000000" opacity="0.3" />
                            </g>
                        </svg>
                        <!--end::Svg Icon-->
                    </span><?php echo e(__('admin.add')); ?>

                </a>

                <!--end::Button-->
            </div>
        </div>
        <div class="card-body">
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col"><?php echo e(__('admin.type')); ?></th>
                        <th scope="col"><?php echo e(__('admin.transferer')); ?></th>
                        <th scope="col"><?php echo e(__('admin.agent')); ?></th>
                        <th scope="col"><?php echo e(__('admin.direction')); ?></th>
                        <th scope="col"><?php echo e(__('admin.financial_custody')); ?></th>
                        <th scope="col"><?php echo e(__('admin.created_at')); ?></th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $financial_custody_agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $financial_custody_agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $isAgentReceiver = $financial_custody_agent->transfered_type == 'App\Models\Agent';
                        ?>
                        <tr>
                            <th scope="row"><?php echo e($financial_custody_agent->id); ?></th>
                            <td>
                                <?php if($financial_custody_agent->type == 1): ?>
                                    <span class="badge badge-success"><?php echo e(__('main.from_dashboard')); ?></span>
                                <?php elseif($financial_custody_agent->type == 2): ?>
                                    <span class="badge badge-info"><?php echo e(__('main.transfer_to_agent')); ?></span>
                                <?php elseif($financial_custody_agent->type == 3): ?>
                                    <span class="badge badge-warning"><?php echo e(__('main.custody_transfer')); ?></span>
                                <?php elseif($financial_custody_agent->type == 4): ?>
                                    <span class="badge badge-primary"><?php echo e(__('main.settle_delivery_policy')); ?></span>
                                <?php elseif($financial_custody_agent->type == 5): ?>
                                    <span class="badge badge-secondary"><?php echo e(__('main.office_commission')); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-light">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($financial_custody_agent->transferer?->name ?? '-'); ?></td>
                            <td>
                                <?php if($isAgentReceiver): ?>
                                    <?php echo e($financial_custody_agent->transfered?->name ?? '-'); ?>

                                <?php else: ?>
                                    <?php echo e($financial_custody_agent->transferer?->name ?? '-'); ?>

                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($isAgentReceiver): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-arrow-down"></i> <?php echo e(__('admin.deposit')); ?>

                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-danger">
                                        <i class="fas fa-arrow-up"></i> <?php echo e(__('admin.withdrawal')); ?>

                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($isAgentReceiver): ?>
                                    <span class="text-success">+ <?php echo e($financial_custody_agent->value); ?></span>
                                <?php else: ?>
                                    <span class="text-danger">- <?php echo e($financial_custody_agent->value); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($financial_custody_agent->created_at); ?></td>
                            <td>
                                <?php if($financial_custody_agent->type == 1): ?>
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete" onclick="Delete('<?php echo e($financial_custody_agent->id); ?>', '<?php echo e($financial_custody_agent->transfered_id); ?>')">
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

        function Delete(id, agent_id) {
            Swal.fire({
                title: "<?php echo e(__('alerts.are_you_sure')); ?>",
                text: "<?php echo e(__('alerts.not_revert_information')); ?>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "<?php echo e(__('alerts.confirm')); ?>",
                cancelButtonText: "<?php echo e(__('alerts.cancel')); ?>",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = '<?php echo e(route("financial_custody_agents.destroy", ":id")); ?>' + '?agent_id=' + agent_id;
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
                                title: "<?php echo e(__('alerts.done')); ?>",
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

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/financial_custody_agents/index.blade.php ENDPATH**/ ?>