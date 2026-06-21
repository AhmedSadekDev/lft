<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => 'الشركات الخاصة'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-title">
                    <h3 class="card-label">الشركات الخاصة</h3>
                </div>
                <div class="card-toolbar">
                    <!--begin::Search-->
                    <form method="GET" action="<?php echo e(route('private-companies.index')); ?>" class="d-flex align-items-center mr-4">
                        <input type="text" name="search" class="form-control form-control-solid w-250px mr-3"
                               placeholder="بحث بالاسم، الرقم الضريبي، السجل التجاري..."
                               value="<?php echo e(request('search')); ?>">
                        <button type="submit" class="btn btn-light-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if(request('search')): ?>
                            <a href="<?php echo e(route('private-companies.index')); ?>" class="btn btn-light ml-2">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                    <!--end::Search-->
                    <!--begin::Button-->
                    <a href="<?php echo e(route('private-companies.create')); ?>" class="btn btn-primary font-weight-bolder mr-2">
                        <span class="svg-icon svg-icon-md">
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
                        </span>إضافة شركة خاصة
                    </a>
                    <!--end::Button-->
                </div>
            </div>
            <div class="card-body">
                <?php if($privateCompanies->count() > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-separate table-head-custom no-datatable" id="table" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px">#</th>
                                <th style="min-width: 150px">الاسم</th>
                                <th style="min-width: 120px">الرقم الضريبي</th>
                                <th style="min-width: 120px">السجل التجاري</th>
                                <th style="min-width: 100px">اللوجو</th>
                                <th class="text-center" style="min-width: 120px">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $privateCompanies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $privateCompany): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="text-center align-middle">
                                        <span class="text-muted font-weight-bold"><?php echo e($privateCompany->id); ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-dark font-weight-bold"><?php echo e($privateCompany->name); ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-muted"><?php echo e($privateCompany->tax_no ?? '-'); ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-muted"><?php echo e($privateCompany->commercial_register ?? '-'); ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <?php if($privateCompany->logo): ?>
                                            <img src="<?php echo e($privateCompany->logo); ?>" alt="<?php echo e($privateCompany->name); ?>"
                                                 style="max-width: 60px; max-height: 60px; border-radius: 4px;">
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo e(route('private-companies.edit', $privateCompany->id)); ?>"
                                               class="btn btn-sm btn-clean btn-icon" title="تعديل">
                                                <i class="la la-edit text-primary"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-clean btn-icon delete-btn"
                                                    data-id="<?php echo e($privateCompany->id); ?>"
                                                    data-url="<?php echo e(route('private-companies.destroy', $privateCompany->id)); ?>"
                                                    title="حذف">
                                                <i class="la la-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center flex-wrap mt-5">
                    <div class="d-flex flex-wrap py-2 mr-3">
                        <span class="text-muted">
                            عرض <?php echo e($privateCompanies->firstItem()); ?> إلى <?php echo e($privateCompanies->lastItem()); ?> من <?php echo e($privateCompanies->total()); ?> نتائج
                        </span>
                    </div>
                    <div>
                        <?php echo e($privateCompanies->links()); ?>

                    </div>
                </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> لا توجد شركات خاصة مسجلة حالياً.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!--end::Card-->
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script>
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var url = $(this).data('url');

        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "لن تتمكن من التراجع عن هذا!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، احذف!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '<?php echo e(csrf_token()); ?>'
                    },
                    success: function(response) {
                        Swal.fire(
                            'تم الحذف!',
                            'تم حذف الشركة الخاصة بنجاح.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'خطأ!',
                            'حدث خطأ أثناء حذف الشركة الخاصة.',
                            'error'
                        );
                    }
                });
            }
        });
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/private-companies/index.blade.php ENDPATH**/ ?>