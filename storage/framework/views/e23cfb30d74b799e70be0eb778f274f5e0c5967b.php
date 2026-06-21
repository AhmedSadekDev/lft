 <style>
        .bs-canvas-overlay,
        .bs-canvas {
            transition: all .4s ease-out;
            -webkit-transition: all .4s ease-out;
            -moz-transition: all .4s ease-out;
            -ms-transition: all .4s ease-out;
        }

        .bs-canvas {
            top: 0;
            z-index: 1110;
            overflow-x: hidden;
            overflow-y: auto;
            width: 330px;
        }

        .bs-canvas-left {
            left: 0;
            margin-left: -330px;
        }

        .bs-canvas-right {
            right: 0;
            margin-right: -330px;
        }

        /* Only for demo */
    </style>
<?php $__env->startSection("content"); ?>
<div class="container">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => __('main.financial_custody_superagents') ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5 d-flex justify-content-between align-items-center">
            <div class="card-toolbar d-flex gap-2">
                <a href="<?php echo e(route('superagent_transactions.create', $superagent->id)); ?>" class="btn btn-primary font-weight-bolder">
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
                    </span><?php echo e(__('admin.add')); ?>

                </a>
                <!-- زر الفلتر -->
                <button type="button" class="btn btn-primary fw-bold shadow-sm" data-toggle="modal" data-target="#filterModal">
                    <i class="fas fa-filter"></i> فلتر
                </button>
                <!-- زر تصدير Excel -->
                <div class="p-2">
                    <button class="btn btn-primary" type="button" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> تصدير إلى Excel</button>
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
                        <form action="<?php echo e(route('superagent_transactions.index', $superagent->id)); ?>" method="get">
                            <div class="form-group">
                                <label for="monthInput">من</label>
                                <input type="date" name="from" value="<?php echo e(old('from') ?? request('from')); ?>"
                                    id="monthInput" class="form-control" placeholder="من">
                            </div>
                            <div class="form-group">
                                <label for="yearInput">الي</label>
                                <input type="date" name="to" value="<?php echo e(old('to') ?? request('to')); ?>"
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
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="superagentTransactionsTable">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th scope="col">#</th>
                            <th scope="col">الصورة</th>
                            <th scope="col">الاسم</th>
                            <th scope="col">المبلغ</th>
                            <th scope="col">التاريخ</th>
                            <th scope="col">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $totalAmount = 0;
                        ?>
                        <?php $__currentLoopData = $superagentTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $totalAmount += $transaction->amount ?? 0;
                        ?>
                        <tr>
                            <th scope="row" class="text-center"><?php echo e($transaction->id); ?></th>
                            <td class="text-center" style="width: 80px;">
                                <?php if($transaction->image): ?>
                                    <?php
                                        $imagePath = asset($transaction->image);
                                    ?>
                                    <a href="<?php echo e($imagePath); ?>"
                                       data-lightbox="transaction-<?php echo e($transaction->id); ?>"
                                       data-title="صورة المعاملة">
                                        <img src="<?php echo e($imagePath); ?>"
                                             alt="صورة"
                                             class="img-thumbnail"
                                             style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;">
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($transaction->name ?? '-'); ?></td>
                            <td class="text-center">
                                <strong class="text-danger"><?php echo e(number_format($transaction->amount ?? 0, 2)); ?> جنيه</strong>
                            </td>
                            <td class="text-center"><?php echo e($transaction->created_at ?? '-'); ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a class="btn btn-sm btn-clean btn-icon btn-icon-md" title="حذف" onclick="Delete(<?php echo e($transaction->id); ?>)">
                                        <i class="la la-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="3" class="text-right">الإجمالي:</td>
                            <td class="text-center text-danger">
                                <?php echo e(number_format($totalAmount, 2)); ?> جنيه
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
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
                    var url = '<?php echo e(route("superagent_transactions.destroy", ":id")); ?>';
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
    <!-- Lightbox CSS & JS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportToExcel() {
            // الحصول على البيانات من الجدول
            let table = document.getElementById("superagentTransactionsTable");
            let wb = XLSX.utils.book_new();

            // تحويل الجدول إلى ورقة عمل
            let ws = XLSX.utils.table_to_sheet(table, {
                raw: false,
                dateNF: 'dd/mm/yyyy'
            });

            // تنسيق الأعمدة
            ws['!cols'] = [
                { wch: 10 },  // #
                { wch: 15 },  // الصورة
                { wch: 30 },  // الاسم
                { wch: 15 },  // المبلغ
                { wch: 15 },  // التاريخ
                { wch: 15 }   // الإجراءات
            ];

            // إضافة الورقة إلى المصنف
            XLSX.utils.book_append_sheet(wb, ws, "معاملات السوبراجنت");

            // تصدير الملف
            XLSX.writeFile(wb, "معاملات_السوبراجنت_" + new Date().toISOString().split('T')[0] + ".xlsx");
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/superagent_transactions/index.blade.php ENDPATH**/ ?>