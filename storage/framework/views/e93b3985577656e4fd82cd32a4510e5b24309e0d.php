<?php $__env->startSection("content"); ?>
<div class="container-fluid">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => __('main.drivers') ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-user-tie text-primary mr-2"></i>
                    <?php echo e(__('main.drivers')); ?>

                </h3>
            </div>
            <div class="card-toolbar">
                <div class="d-flex gap-2 flex-wrap">
                    <?php if(auth()->user()->hasPermissionTo('drivers.create')): ?>
                        <a href="<?php echo e(route('drivers.create')); ?>" class="btn btn-primary font-weight-bold shadow-sm">
                            <i class="fas fa-plus mr-1"></i><?php echo e(__('admin.add')); ?>

                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- شريط البحث -->
        <div class="card-body border-top">
            <form action="<?php echo e(route('drivers.index')); ?>" method="get" class="mb-4">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="font-weight-bold text-dark mb-2">البحث</label>
                        <div class="input-group input-group-solid">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                            </div>
                            <input type="text" name="search" class="form-control form-control-solid"
                                    placeholder="ابحث عن الاسم أو رقم الهاتف..."
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
                            <a href="<?php echo e(route('drivers.index')); ?>" class="btn btn-secondary font-weight-bold w-100">
                                <i class="fas fa-times mr-1"></i> إلغاء البحث
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </form>

            <!-- معلومات النتائج -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-muted">
                    <i class="fas fa-info-circle mr-1"></i>
                    عرض <?php echo e($drivers->firstItem() ?? 0); ?> - <?php echo e($drivers->lastItem() ?? 0); ?> من أصل <?php echo e($drivers->total()); ?> سائق
                </div>
            </div>

            <!-- الجدول -->
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-head-custom table-vertical-center no-datatable">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th scope="col" style="width: 80px;">#</th>
                            <th scope="col"><?php echo e(__('admin.name')); ?></th>
                            <th scope="col"><?php echo e(__('admin.phone')); ?></th>
                            <th scope="col"><?php echo e(__('admin.created_at')); ?></th>
                            <th scope="col" style="width: 120px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $drivers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $driver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="text-center align-middle">
                                    <span class="badge badge-secondary badge-pill"><?php echo e($driver->id); ?></span>
                                </td>
                                <td class="align-middle">
                                    <span class="font-weight-bold">
                                        <i class="fas fa-user mr-1 text-muted"></i><?php echo e($driver->name); ?>

                                    </span>
                                </td>
                                <td class="align-middle">
                                    <span class="text-muted">
                                        <i class="fas fa-phone mr-1"></i><?php echo e($driver->phone); ?>

                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    <small class="text-muted">
                                        <?php echo e($driver->created_at ? \Carbon\Carbon::parse($driver->created_at)->format('Y-m-d') : '-'); ?>

                                    </small>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="d-flex justify-content-center gap-2">
                                        <?php if(auth()->user()->hasPermissionTo('drivers.update')): ?>
                                            <a href="<?php echo e(route('drivers.edit',$driver->id)); ?>"
                                               class="btn btn-icon btn-light btn-hover-primary btn-sm"
                                               title="تعديل">
                                                <i class="fas fa-edit text-primary"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if(auth()->user()->hasPermissionTo('drivers.delete')): ?>
                                            <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                                                    onclick="Delete('<?php echo e($driver->id); ?>')"
                                                    title="حذف">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-user-tie fa-3x mb-3"></i>
                                        <p class="font-weight-bold">لا توجد سائقين</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if($drivers->hasPages()): ?>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        صفحة <?php echo e($drivers->currentPage()); ?> من <?php echo e($drivers->lastPage()); ?>

                    </div>
                    <div>
                        <?php echo e($drivers->appends(request()->query())->links('pagination::bootstrap-4')); ?>

                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!--end::Card-->
</div>

<style>
    .table-head-custom th {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }
    .btn-icon {
        width: 35px;
        height: 35px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    .btn-icon:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
</style>
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
                    var url = '<?php echo e(route("drivers.destroy", ":id")); ?>';
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
<?php $__env->stopPush(); ?>


<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/drivers/index.blade.php ENDPATH**/ ?>