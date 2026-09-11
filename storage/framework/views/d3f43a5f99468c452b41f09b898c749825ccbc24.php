
<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => __('main.suppliers')], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-title">
                    <h3 class="card-label"><?php echo e(__('main.suppliers')); ?></h3>
                </div>
                <div class="card-toolbar">
                    <?php if(auth()->user()->hasPermissionTo('suppliers.index')): ?>
                        <a href="<?php echo e(route('receipts.index')); ?>" class="btn btn-light-info font-weight-bolder mr-2">
                            <i class="fas fa-receipt"></i> الإيصالات
                        </a>
                    <?php endif; ?>
                    <?php if(auth()->user()->hasPermissionTo('suppliers.create')): ?>
                        <a href="<?php echo e(route('suppliers.create')); ?>" class="btn btn-primary font-weight-bolder">
                            <i class="fas fa-plus"></i> <?php echo e(__('admin.add')); ?>

                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <form method="GET" action="<?php echo e(route('suppliers.index')); ?>" class="mb-4">
                    <div class="input-group" style="max-width: 420px;">
                        <input type="text" name="search" value="<?php echo e(request('search')); ?>" class="form-control"
                               placeholder="بحث باسم المورد...">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">بحث</button>
                        </div>
                    </div>
                </form>

                <?php if(session('success')): ?>
                    <div class="alert alert-success"><?php echo e(session('success')); ?></div>
                <?php endif; ?>
                <?php if(session('error')): ?>
                    <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center">
                        <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th><?php echo e(__('admin.name')); ?></th>
                            <th>الرصيد</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($supplier->id); ?></td>
                                <td>
                                    <a href="<?php echo e(route('suppliers.statement', $supplier)); ?>">
                                        <?php echo e($supplier->name); ?>

                                    </a>
                                </td>
                                <td class="<?php echo e((float) $supplier->balance > 0 ? 'text-danger' : 'text-success'); ?> font-weight-bold">
                                    <?php echo e(number_format((float) $supplier->balance, 2)); ?> ج.م
                                </td>
                                <td>
                                    <a href="<?php echo e(route('suppliers.statement', $supplier)); ?>"
                                       class="btn btn-sm btn-light-primary" title="كشف حساب">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    <?php if(auth()->user()->hasPermissionTo('suppliers.create')): ?>
                                        <a href="<?php echo e(route('suppliers.payment', $supplier)); ?>"
                                           class="btn btn-sm btn-light-success" title="سداد">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if(auth()->user()->hasPermissionTo('suppliers.udpate') || auth()->user()->hasPermissionTo('suppliers.update')): ?>
                                        <a href="<?php echo e(route('suppliers.edit', $supplier)); ?>"
                                           class="btn btn-sm btn-light-warning" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if(auth()->user()->hasPermissionTo('suppliers.delete')): ?>
                                        <button type="button" class="btn btn-sm btn-light-danger"
                                                onclick="DeleteSupplier('<?php echo e($supplier->id); ?>')" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="text-muted py-4">لا يوجد موردون</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php echo e($suppliers->withQueryString()->links()); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script>
    function DeleteSupplier(id) {
        if (!confirm('هل أنت متأكد من حذف المورد؟')) return;
        $.ajax({
            url: '<?php echo e(url('dashboard/suppliers')); ?>/' + id,
            type: 'POST',
            data: {_method: 'DELETE', _token: '<?php echo e(csrf_token()); ?>'},
            success: function (res) {
                toastr.success(res.msg || 'تم الحذف');
                location.reload();
            },
            error: function () {
                toastr.error('تعذر حذف المورد');
            }
        });
    }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/suppliers/index.blade.php ENDPATH**/ ?>