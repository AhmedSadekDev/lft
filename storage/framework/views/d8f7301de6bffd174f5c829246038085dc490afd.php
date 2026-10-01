

<?php $__env->startSection("content"); ?>
<div class="container-fluid">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => 'الموقف المالي - السيارات' ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-chart-line text-info mr-2"></i>
                    الموقف المالي - السيارات
                </h3>
            </div>
            <div class="card-toolbar">
                <form action="<?php echo e(route('accounts.cars.financial-position')); ?>" method="get" class="d-flex align-items-center">
                    <input type="date"
                           name="date"
                           class="form-control mr-2"
                           value="<?php echo e($reportDate); ?>"
                           style="width: 200px;">
                    <button type="submit" class="btn btn-primary font-weight-bold">
                        <i class="fas fa-filter mr-1"></i> فلترة
                    </button>
                </form>
            </div>
        </div>

        <div class="card-body">
            <!-- ملخص التقرير -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <div class="card-body text-white">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <i class="fas fa-calendar-alt fa-lg mb-2"></i>
                                    </div>
                                    <div class="font-weight-bold" style="font-size: 14px; opacity: 0.9;">تاريخ التقرير</div>
                                    <div class="font-weight-bold" style="font-size: 18px;"><?php echo e(\Carbon\Carbon::parse($reportDate)->format('Y-m-d')); ?></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <i class="fas fa-car fa-lg mb-2"></i>
                                    </div>
                                    <div class="font-weight-bold" style="font-size: 14px; opacity: 0.9;">عدد السيارات (رصيد غير صفر)</div>
                                    <div class="font-weight-bold" style="font-size: 18px;"><?php echo e($carsWithDebts->count()); ?></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <i class="fas fa-money-bill-wave fa-lg mb-2"></i>
                                    </div>
                                    <div class="font-weight-bold" style="font-size: 14px; opacity: 0.9;">صافي إجمالي الرصيد</div>
                                    <div class="font-weight-bold" style="font-size: 22px; color: #fff;"><?php echo e(number_format($totalDebts, 2)); ?> ج.م</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الجدول -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" style="font-size: 14px;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #DC143C 0%, #B22222 100%); color: #fff;">
                            <th class="text-center" style="width: 60px; border: 1px solid rgba(255,255,255,0.3);">#</th>
                            <th style="border: 1px solid rgba(255,255,255,0.3);">رقم السيارة</th>
                            <th class="text-center" style="border: 1px solid rgba(255,255,255,0.3);">الرصيد المستحق</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $carsWithDebts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $car): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr style="transition: all 0.3s ease;">
                            <td class="text-center align-middle">
                                <span class="badge badge-secondary badge-pill"><?php echo e($index + 1); ?></span>
                            </td>
                            <td class="align-middle">
                                <span class="font-weight-bold text-primary">
                                    <i class="fas fa-car mr-1"></i><?php echo e($car['car_number']); ?>

                                </span>
                            </td>
                            <td class="text-center align-middle">
                                <?php $b = (float) $car['balance']; ?>
                                <span class="badge badge-pill font-weight-bold <?php echo e($b >= 0 ? 'badge-danger' : 'badge-info'); ?>" style="font-size: 13px; padding: 6px 12px;">
                                    <?php echo e($b < 0 ? '-' : ''); ?><?php echo e(number_format(abs($b), 2)); ?> ج.م
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                    <p class="font-weight-bold">لا توجد سيارات برصيد غير صفر في هذا التاريخ</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if($carsWithDebts->count() > 0): ?>
                    <tfoot>
                        <tr style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-top: 3px solid #DC143C;">
                            <td colspan="2" class="text-right font-weight-bold align-middle" style="font-size: 16px;">
                                <i class="fas fa-calculator mr-2 text-primary"></i>صافي الإجمالي:
                            </td>
                            <td class="text-center align-middle">
                                <?php $tb = (float) $totalDebts; ?>
                                <span class="badge font-weight-bold <?php echo e($tb >= 0 ? 'badge-danger' : 'badge-info'); ?>" style="font-size: 16px; padding: 8px 16px;">
                                    <?php echo e($tb < 0 ? '-' : ''); ?><?php echo e(number_format(abs($tb), 2)); ?> ج.م
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <style>
            .table tbody tr:hover {
                background-color: #f8f9fa !important;
                transform: scale(1.01);
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .table thead th {
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-size: 13px;
            }
            .btn-icon:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
        </style>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/accounts/cars-financial-position-report.blade.php ENDPATH**/ ?>