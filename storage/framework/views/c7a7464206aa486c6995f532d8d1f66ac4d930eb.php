<?php $__env->startSection("content"); ?>
<div class="container-fluid">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => 'تقرير الأرباح والخسائر' ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-chart-line text-primary mr-2"></i>
                    تقرير الأرباح والخسائر
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="<?php echo e(route('accounts.profit-loss.export.excel', request()->all())); ?>"
                   class="btn btn-primary font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-file-excel"></i> تصدير Excel
                </a>
                <a href="<?php echo e(route('accounts.profit-loss.export.pdf', request()->all())); ?>"
                   class="btn btn-danger font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-file-pdf"></i> تصدير PDF
                </a>
                <button type="button" class="btn btn-secondary font-weight-bold shadow-sm" data-toggle="modal" data-target="#filterModal">
                    <i class="fas fa-filter"></i> فلتر
                </button>
            </div>
        </div>

        <!-- Modal الفلتر -->
        <div class="modal fade" id="filterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold">
                            <i class="fas fa-calendar-alt mr-2"></i>فلترة التقرير
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form action="<?php echo e(route('accounts.profit-loss')); ?>" method="get">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">من تاريخ</label>
                                        <input type="date" name="from" value="<?php echo e($fromDate); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">إلى تاريخ</label>
                                        <input type="date" name="to" value="<?php echo e($toDate); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="font-weight-bold">الشركة (اختياري - اتركه فارغاً لجميع الشركات)</label>
                                        <select name="company_id" class="form-control">
                                            <option value="">جميع الشركات</option>
                                            <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($company->id); ?>" <?php echo e($companyId == $company->id ? 'selected' : ''); ?>>
                                                    <?php echo e($company->name); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">تطبيق</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- ملخص التقرير -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <h5 class="font-weight-bold mb-3">
                            <i class="fas fa-info-circle mr-2"></i>
                            ملخص التقرير للفترة من <?php echo e($fromDate); ?> إلى <?php echo e($toDate); ?>

                        </h5>
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>إجمالي التكلفة:</strong>
                                    <span class="text-danger font-weight-bold"><?php echo e(number_format($totalCost, 2)); ?> ج.م</span>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>إجمالي الإيرادات:</strong>
                                    <span class="text-primary font-weight-bold"><?php echo e(number_format($totalRevenue, 2)); ?> ج.م</span>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>صافي الربح/الخسارة:</strong>
                                    <span class="font-weight-bold <?php echo e($totalProfitLoss >= 0 ? 'text-success' : 'text-danger'); ?>">
                                        <?php echo e(number_format($totalProfitLoss, 2)); ?> ج.م
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- جدول التقرير -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" style="font-size: 12px;">
                    <thead class="thead-dark">
                        <tr class="text-center">
                            <th style="width: 5%;">#</th>
                            <th style="width: 8%;">رقم الطلب</th>
                            <th style="width: 10%;">رقم الفاتورة</th>
                            <th style="width: 12%;">اسم الشركة</th>
                            <th style="width: 8%;">تاريخ الفاتورة</th>
                            <th style="width: 25%;">وصف المصروفات</th>
                            <th style="width: 10%;" class="text-danger">التكلفة الفعلية</th>
                            <th style="width: 10%;" class="text-primary">سعر الفاتورة</th>
                            <th style="width: 12%;" class="<?php echo e($totalProfitLoss >= 0 ? 'text-success' : 'text-danger'); ?>">الربح/الخسارة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $reportData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $date = $row['invoice_date'] instanceof \Carbon\Carbon ? $row['invoice_date'] : \Carbon\Carbon::parse($row['invoice_date']);
                            ?>
                            <tr>
                                <td class="text-center"><?php echo e($index + 1); ?></td>
                                <td class="text-center"><?php echo e($row['booking_number']); ?></td>
                                <td class="text-center"><?php echo e($row['invoice_number']); ?></td>
                                <td class="text-center"><?php echo e($row['company_name']); ?></td>
                                <td class="text-center"><?php echo e($date->format('Y-m-d')); ?></td>
                                <td style="font-size: 11px;">
                                    <?php if($row['expenses_details']->count() > 0): ?>
                                        <ul class="mb-0 pl-3" style="list-style: none;">
                                            <?php $__currentLoopData = $row['expenses_details']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $expense): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <li>• <?php echo e($expense['description']); ?>: <?php echo e(number_format($expense['value'], 2)); ?> ج.م</li>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted">لا توجد مصروفات</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center text-danger font-weight-bold"><?php echo e(number_format($row['total_cost'], 2)); ?></td>
                                <td class="text-center text-primary font-weight-bold"><?php echo e(number_format($row['invoice_total'], 2)); ?></td>
                                <td class="text-center font-weight-bold <?php echo e($row['profit_loss'] >= 0 ? 'text-success' : 'text-danger'); ?>">
                                    <?php echo e(number_format($row['profit_loss'], 2)); ?>

                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">لا توجد بيانات في هذه الفترة</td>
                            </tr>
                        <?php endif; ?>

                        <!-- صف الإجمالي -->
                        <?php if($reportData->count() > 0): ?>
                            <tr class="table-info font-weight-bold">
                                <td colspan="6" class="text-center">الإجمالي</td>
                                <td class="text-center text-danger"><?php echo e(number_format($totalCost, 2)); ?></td>
                                <td class="text-center text-primary"><?php echo e(number_format($totalRevenue, 2)); ?></td>
                                <td class="text-center <?php echo e($totalProfitLoss >= 0 ? 'text-success' : 'text-danger'); ?>">
                                    <?php echo e(number_format($totalProfitLoss, 2)); ?>

                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/accounts/profit-loss-report.blade.php ENDPATH**/ ?>