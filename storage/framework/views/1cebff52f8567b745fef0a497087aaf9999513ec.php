
<?php $__env->startSection("content"); ?>
<div class="container">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => __('main.daily_reports') ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5 d-flex justify-content-between align-items-center">
            <div class="card-toolbar d-flex gap-2">
                <!-- زر الفلتر -->
                <button type="button" class="btn btn-primary fw-bold shadow-sm" data-toggle="modal" data-target="#filterModal">
                    <i class="fas fa-filter"></i> فلتر
                </button>
                <!-- زر تصدير Excel -->
                <div class="p-2">
                    <a href="<?php echo e(route('reports.export_excel', request()->query())); ?>" class="btn btn-success">
                        <i class="fas fa-file-excel me-1"></i> تصدير اكسيل
                    </a>
                </div>
                <div class="p-2">
                    <a class="btn btn-primary" href="<?php echo e(route('reports.daily_reports')); ?>"><i class="fas fa-sync-alt me-1"></i>إعادة الضبط</a>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-light">
                        <h5 class="modal-title" id="filterModalLabel">تقرير ب فتره</h5>
                        <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="<?php echo e(route('reports.daily_reports')); ?>" method="get">
                            <div class="form-group">
                                <label for="monthInput">من</label>
                                <input type="date" name="from" value="<?php echo e(old('from')); ?>"
                                    id="monthInput" class="form-control" placeholder="من">
                            </div>
                            <div class="form-group">
                                <label for="yearInput">الي</label>
                                <input type="date" name="to" value="<?php echo e(old('to')); ?>" 
                                       id="yearInput" class="form-control" 
                                       placeholder="الي" >
                            </div>
                            <button class="btn btn-primary" type="submit">فلتر</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col"><?php echo e(__('admin.type')); ?></th>
                        <th scope="col"><?php echo e(__('admin.agent')); ?></th>
                        <th scope="col"><?php echo e(__('admin.action')); ?></th>
                        <th scope="col"><?php echo e(__('admin.value')); ?></th>
                        <th scope="col"><?php echo e(__('admin.direction')); ?></th>
                        <th scope="col"><?php echo e(__('main.date')); ?></th>
                        <th scope="col"><?php echo e(__('main.time')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $log_activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log_activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $isMoneyTransfer = $log_activity->log_type == 'App\Models\MoneyTransfer';
                        $isExpense = $log_activity->log_type == 'App\Models\AgentExpense';
                        $isBookingContainer = $log_activity->log_type == 'App\Models\BookingContainer';
                        
                        $value = 0;
                        $direction = '';
                        $typeBadge = '';
                        
                        if ($isMoneyTransfer) {
                            $value = $log_activity->log?->value ?? 0;
                            $type = $log_activity->log?->type;
                            
                            // تحديد نوع المعاملة
                            if ($type == 1) {
                                $typeBadge = '<span class="badge badge-success">'.__('main.from_dashboard').'</span>';
                                $direction = '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> '.__('admin.deposit').'</span>';
                            } elseif ($type == 2) {
                                $typeBadge = '<span class="badge badge-info">'.__('main.transfer_to_agent').'</span>';
                                $direction = '<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> '.__('admin.withdrawal').'</span>';
                            } elseif ($type == 3) {
                                $typeBadge = '<span class="badge badge-warning">'.__('main.custody_transfer').'</span>';
                                $direction = '<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> '.__('admin.withdrawal').'</span>';
                            } elseif ($type == 4) {
                                $typeBadge = '<span class="badge badge-primary">'.__('main.settle_delivery_policy').'</span>';
                                $direction = '<span class="badge badge-warning"><i class="fas fa-exchange-alt"></i> '.__('admin.settle').'</span>';
                            } elseif ($type == 5) {
                                $typeBadge = '<span class="badge badge-secondary">'.__('main.office_commission').'</span>';
                                $direction = '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> '.__('admin.deposit').'</span>';
                            }
                        } elseif ($isExpense) {
                            $value = $log_activity->log?->value ?? 0;
                            $typeBadge = '<span class="badge badge-danger">'.__('admin.expense').'</span>';
                            $direction = '<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> '.__('admin.withdrawal').'</span>';
                        } elseif ($isBookingContainer) {
                            $typeBadge = '<span class="badge badge-dark">'.__('admin.booking_container').'</span>';
                            $direction = '<span class="badge badge-light">-</span>';
                        }
                    ?>
                    <tr>
                        <th scope="row"><?php echo e($log_activity->id); ?></th>
                        <td><?php echo $typeBadge; ?></td>
                        <td><?php echo e($log_activity?->attacher?->name ?? "-"); ?></td>
                        <td><?php echo e($log_activity->action ?? ""); ?></td>
                        <td>
                            <?php if($value > 0): ?>
                                <strong><?php echo e(number_format($value, 2)); ?></strong>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo $direction; ?></td>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportToExcel() {
            let table = document.getElementById("table");
            let wb = XLSX.utils.table_to_book(table, { sheet: "التقارير اليوميه" });
            XLSX.writeFile(wb, "التقارير اليوميه.xlsx");
        }
    </script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\reports\index.blade.php ENDPATH**/ ?>