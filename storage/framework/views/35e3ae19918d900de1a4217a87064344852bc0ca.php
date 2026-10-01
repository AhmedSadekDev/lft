
<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => 'الإيصالات'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-title">
                    <h3 class="card-label">الإيصالات</h3>
                </div>
                <div class="card-toolbar">
                    <a href="<?php echo e(route('suppliers.index')); ?>" class="btn btn-secondary font-weight-bolder mr-2">الموردين</a>
                    <?php if(auth()->user()->hasPermissionTo('suppliers.create')): ?>
                        <a href="<?php echo e(route('receipts.create')); ?>" class="btn btn-primary font-weight-bolder">
                            <i class="fas fa-plus"></i> إضافة إيصال
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <?php if(session('success')): ?>
                    <div class="alert alert-success"><?php echo e(session('success')); ?></div>
                <?php endif; ?>

                <form method="GET" class="mb-4 row">
                    <div class="col-md-3 mb-2">
                        <select name="payment_source" class="form-control">
                            <option value="">كل مصادر الدفع</option>
                            <option value="safe" <?php echo e(request('payment_source') === 'safe' ? 'selected' : ''); ?>>الخزنة</option>
                            <option value="representative" <?php echo e(request('payment_source') === 'representative' ? 'selected' : ''); ?>>مندوب</option>
                            <option value="supplier" <?php echo e(request('payment_source') === 'supplier' ? 'selected' : ''); ?>>مورد</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="supplier_id" class="form-control">
                            <option value="">كل الموردين</option>
                            <?php $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($supplier->id); ?>" <?php echo e((string) request('supplier_id') === (string) $supplier->id ? 'selected' : ''); ?>>
                                    <?php echo e($supplier->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-primary btn-block">فلترة</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center">
                        <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>التكلفة</th>
                            <th>مصدر الدفع</th>
                            <th>المورد</th>
                            <th>رقم فاتورة المورد</th>
                            <th>الخدمة</th>
                            <th>رقم الطلب</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $receipts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $receipt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($receipt->id); ?></td>
                                <td><?php echo e(optional($receipt->created_at)->format('Y-m-d')); ?></td>
                                <td class="font-weight-bold"><?php echo e(number_format((float) $receipt->cost, 2)); ?></td>
                                <td>
                                    <?php if($receipt->payment_source === 'supplier'): ?> مورد
                                    <?php elseif($receipt->payment_source === 'safe'): ?> الخزنة
                                    <?php elseif($receipt->payment_source === 'representative'): ?> مندوب
                                    <?php else: ?> -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($receipt->supplier->name ?? '-'); ?></td>
                                <td><?php echo e($receipt->supplier_invoice_number ?: '-'); ?></td>
                                <td><?php echo e($receipt->bookingService?->full_name ?? '-'); ?></td>
                                <td>
                                    <?php if($receipt->booking): ?>
                                        <a href="<?php echo e(route('bookings.show', $receipt->booking_id)); ?>">
                                            <?php echo e($receipt->booking->booking_number ?? ('#'.$receipt->booking_id)); ?>

                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(auth()->user()->hasPermissionTo('suppliers.udpate') || auth()->user()->hasPermissionTo('suppliers.update')): ?>
                                        <a href="<?php echo e(route('receipts.edit', $receipt)); ?>" class="btn btn-sm btn-light-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if(auth()->user()->hasPermissionTo('suppliers.delete')): ?>
                                        <button type="button" class="btn btn-sm btn-light-danger"
                                                onclick="DeleteReceipt('<?php echo e($receipt->id); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="text-muted py-4">لا توجد إيصالات</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php echo e($receipts->withQueryString()->links()); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script>
    function DeleteReceipt(id) {
        if (!confirm('هل أنت متأكد من حذف الإيصال؟')) return;
        $.ajax({
            url: '<?php echo e(url('dashboard/receipts')); ?>/' + id,
            type: 'POST',
            data: {_method: 'DELETE', _token: '<?php echo e(csrf_token()); ?>'},
            success: function (res) {
                toastr.success(res.msg || 'تم الحذف');
                location.reload();
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.msg) || 'تعذر الحذف');
            }
        });
    }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/receipts/index.blade.php ENDPATH**/ ?>