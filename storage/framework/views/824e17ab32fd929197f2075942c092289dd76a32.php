

<?php $__env->startSection("content"); ?>
<div class="container-fluid">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => 'الشيكات' ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-money-check text-primary mr-2"></i>
                    الشيكات
                </h3>
            </div>
            <div class="card-toolbar">
                <form method="GET" action="<?php echo e(route('accounts.checks.index')); ?>" class="d-flex">
                    <input type="text"
                           name="search"
                           class="form-control mr-2"
                           placeholder="بحث برقم الشيك..."
                           value="<?php echo e(request('search')); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> بحث
                    </button>
                    <?php if(request('search')): ?>
                        <a href="<?php echo e(route('accounts.checks.index')); ?>" class="btn btn-secondary mr-2">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show m-3">
                <?php echo e(session('success')); ?>

                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show m-3">
                <?php echo e(session('error')); ?>

                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead style="background: linear-gradient(135deg, #DC143C 0%, #B22222 100%); color: #fff;">
                        <tr>
                            <th>#</th>
                            <th>رقم الشيك</th>
                            <th>اسم البنك</th>
                            <th>الشركة</th>
                            <th>القيمة</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $checks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $check): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="<?php echo e($check->check_due_date < now() && !$check->check_paid_at ? 'table-danger' : ''); ?>">
                                <td><?php echo e($loop->iteration + ($checks->currentPage() - 1) * $checks->perPage()); ?></td>
                                <td><strong><?php echo e($check->check_number); ?></strong></td>
                                <td><?php echo e($check->check_bank_name); ?></td>
                                <td><?php echo e($check->company->name ?? ($check->invoice->booking->company->name ?? '-')); ?></td>
                                <td class="font-weight-bold"><?php echo e(number_format($check->value, 2)); ?> ج.م</td>
                                <td>
                                    <span class="<?php echo e($check->check_due_date < now() && !$check->check_paid_at ? 'text-danger font-weight-bold' : ''); ?>">
                                        <?php echo e($check->check_due_date ? \Carbon\Carbon::parse($check->check_due_date)->format('Y-m-d') : '-'); ?>

                                    </span>
                                </td>
                                <td>
                                    <?php if($check->check_paid_at): ?>
                                        <span class="badge badge-success">تم الاستحقاق</span>
                                    <?php elseif($check->check_due_date < now()): ?>
                                        <span class="badge badge-danger">متأخر</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">قيد الانتظار</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!$check->check_paid_at): ?>
                                        <form action="<?php echo e(route('accounts.checks.mark-paid', $check->id)); ?>" method="POST" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('هل أنت متأكد من استحقاق هذا الشيك؟ سيتم خصم القيمة من حساب الشركة.')">
                                                <i class="fas fa-check"></i> تم الاستحقاق
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">تم الاستحقاق في: <?php echo e(\Carbon\Carbon::parse($check->check_paid_at)->format('Y-m-d H:i')); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="text-center">لا توجد شيكات</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <?php echo e($checks->links()); ?>

            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/accounts/checks.blade.php ENDPATH**/ ?>