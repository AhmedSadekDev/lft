
<?php
    $from = isset($_GET['from']) ? $_GET['from'] : '';
    $to = isset($_GET['to']) ? $_GET['to'] : '';
?>

<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => $company->name], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <a href="<?php echo e(route('companyInvoice.create', $company->id)); ?>" class="btn btn-primary font-weight-bolder">
                        <span class="svg-icon svg-icon-md">
                            <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px"
                                height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <rect x="0" y="0" width="24" height="24" />
                                    <circle fill="#000000" cx="9" cy="15" r="6" />
                                    <path
                                        d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z"
                                        fill="#000000" opacity="0.3" />
                                </g>
                            </svg>
                            <!--end::Svg Icon-->
                        </span><?php echo e(__('admin.add')); ?>

                    </a>

                </div>
                <!--begin::Button-->
            </div>
        </div>
        <div class="">
            <br>
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                فلتر
            </button>
            <?php if($from): ?>
                <a href="<?php echo e(route('companyInvoice.export', [$from, $to, $company->id])); ?>" class="btn btn-secondary">
                    تحميل الملف
                </a>
            <?php endif; ?>

            <!-- Modal -->
            <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">فلتر بالتاريخ</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                X
                            </button>
                        </div>
                        <form action="<?php echo e(route('companyInvoice.index', $company->id)); ?>" method="get">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dateFrom">من </label>
                                            <input id="dateFrom" class="form-control" type="date" name="from">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dateTo">الي</label>
                                            <input id="dateTo" class="form-control" type="date" name="to">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">غلق</button>
                                <button type="submit" class="btn btn-primary">فلتر</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">

            <table class="table" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">صورة التحويل</th>
                        <th scope="col">قيمة التحويل</th>
                        <th scope="col">من قام بالاضافة</th>
                        <th scope="col"><?php echo e(__('admin.status')); ?></th>

                        <th scope="col"><?php echo e(__('admin.created_at')); ?></th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $imagePath = Str::startsWith($invoice->image, 'admin')
                                ? asset($invoice->image)
                                : asset('Admin/images/companyInvoice/' . $invoice->image);
                        ?>

                        <tr>
                            <th scope="row"><?php echo e($invoice->id); ?></th>
                            <td>
                                <a href="<?php echo e($imagePath); ?>" download><img
                                        class="bg-soft-primary rounded img-fluid avatar-40 me-3"
                                        style="width:100px;height:100px"
                                        src="<?php echo e($imagePath); ?>"
                                        alt="Ads"></a>
                            </td>
                            <td>
                                <?php echo e($invoice->total); ?>

                            </td>
                            <td>
                                <?php echo e($invoice->user->name); ?>

                            </td>

                            <td>
                                <?php if($invoice->type): ?>
                                    <?php echo e(__('main.credit')); ?>

                                <?php else: ?>
                                    <?php echo e(__('main.debit')); ?>

                                <?php endif; ?>
                            </td>

                            <td><?php echo e($invoice->created_at); ?></td>
                            <td>
                                <div class="row">
                                    <div class="col-md-3 mr-3">
                                        <a href="<?php echo e(route('companyInvoice.edit', $invoice->id)); ?>"
                                            class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                            <i class="fas fa-edit text-primary"></i>
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-icon btn-light btn-hover-danger btn-sm mx-3 delete"
                                            onclick="Delete('<?php echo e($invoice->id); ?>')">
                                            <i class="fas fa-trash text-danger"></i>
                                        </button>
                                    </div>

                                </div>
                            </td>

                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
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
                    var url = '<?php echo e(route('companyInvoice.destroy', ':id')); ?>';
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
                            if (xhr.status == 200) {
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

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\companyInvoice\index.blade.php ENDPATH**/ ?>