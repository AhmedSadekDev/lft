

<?php $__env->startSection("content"); ?>
<div class="container-fluid">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => 'الحسابات' ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-calculator text-primary mr-2"></i>
                    إدارة الحسابات
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="<?php echo e(route('accounts.checks.index')); ?>"
                   class="btn btn-warning font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-money-check"></i> الشيكات
                </a>
                <a href="<?php echo e(route('accounts.financial-position')); ?>"
                   class="btn btn-info font-weight-bold shadow-sm">
                    <i class="fas fa-chart-line"></i> تقرير الموقف المالي
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- شريط البحث -->
            <form action="<?php echo e(route('accounts.index')); ?>" method="get" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="input-group input-group-solid">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                            </div>
                            <input type="text" name="search" class="form-control form-control-solid"
                                   placeholder="ابحث عن الشركة..."
                                   value="<?php echo e(request('search')); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary font-weight-bold w-100">
                            <i class="fas fa-search mr-1"></i> بحث
                        </button>
                    </div>
                    <?php if(request('search')): ?>
                        <div class="col-md-2">
                            <a href="<?php echo e(route('accounts.index')); ?>"
                               class="btn btn-secondary font-weight-bold w-100">
                                <i class="fas fa-times mr-1"></i> إلغاء
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </form>

            <!-- الجدول -->
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th>#</th>
                            <th>اسم الشركة</th>
                            <th>البريد الإلكتروني</th>
                            <th>الهاتف</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-center"><?php echo e($company->id); ?></td>
                            <td><?php echo e($company->name); ?></td>
                            <td><?php echo e($company->email); ?></td>
                            <td><?php echo e($company->phone); ?></td>
                            <td class="text-center">
                                <a href="<?php echo e(route('accounts.statement', $company->id)); ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-file-invoice"></i> كشف حساب
                                </a>
                                <a href="<?php echo e(route('accounts.payment', $company->id)); ?>"
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-money-bill"></i> سداد
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p class="font-weight-bold">لا توجد شركات</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if($companies->hasPages()): ?>
                <div class="d-flex justify-content-center mt-4">
                    <?php echo e($companies->links('pagination::bootstrap-4')); ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/accounts/index.blade.php ENDPATH**/ ?>