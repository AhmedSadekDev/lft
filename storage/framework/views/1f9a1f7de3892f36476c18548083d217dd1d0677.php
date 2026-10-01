
<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => __('main.companies')], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-title">
                    <h3 class="card-label"><?php echo e(__('main.companies')); ?></h3>
                </div>
                <div class="card-toolbar">
                    <!--begin::Search-->
                    <form method="GET" action="<?php echo e(route('companies.index')); ?>" class="d-flex align-items-center mr-4">
                        <input type="text" name="search" class="form-control form-control-solid w-250px mr-3"
                               placeholder="بحث بالاسم، البريد، الهاتف..."
                               value="<?php echo e(request('search')); ?>">
                        <button type="submit" class="btn btn-light-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if(request('search')): ?>
                            <a href="<?php echo e(route('companies.index')); ?>" class="btn btn-light ml-2">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                    <!--end::Search-->
                    <!--begin::Button-->
                    <?php if(auth()->user()->hasPermissionTo('companies.create')): ?>
                        <a href="<?php echo e(route('companies.create')); ?>" class="btn btn-primary font-weight-bolder mr-2">
                            <span class="svg-icon svg-icon-md">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
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
                                <!--end::Svg Icon-->
                            </span><?php echo e(__('admin.add')); ?>

                        </a>
                    <?php endif; ?>
                    <!--end::Button-->
                    <div class="p-2">
                        <a href="<?php echo e(route('companies.export-excel', ['search' => request('search')])); ?>"
                           class="btn btn-primary">
                            <i class="fas fa-file-excel"></i> تصدير إلى Excel
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if($companies->count() > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-separate table-head-custom no-datatable" id="table" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px">#</th>
                                <th style="min-width: 180px">معلومات الشركة</th>
                                <th style="min-width: 150px">التواصل</th>
                                <th style="min-width: 120px">الضريبي</th>
                                <th class="text-center" style="min-width: 120px">الحالة الضريبية</th>
                                <th class="text-center" style="min-width: 100px">نوع الفاتورة</th>
                                <th class="text-center" style="min-width: 120px">آخر فاتورة</th>
                                <th class="text-center" style="min-width: 150px">الرصيد المتبقي</th>
                                <th class="text-center" style="min-width: 100px">الملحقات</th>
                                <th class="text-center" style="min-width: 120px">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    // نفس منطق كشف الحساب: رصيد افتتاحي + (إجمالي الفواتير) - (إجمالي السداد الفعلي)
                                    $openingBalance = (float) ($company->opening_balance ?? 0);
                                    $totalInvoices = 0;
                                    $totalPayments = 0;

                                    foreach ($company->bookings as $booking) {
                                        if (!$booking->invoice) {
                                            continue;
                                        }

                                        $invoice = $booking->invoice;

                                        // نفس معادلة calculateInvoiceTotal في AccountController
                                        $invoiceTotalBeforeTax = (float) ($invoice->invoice_total_before_tax ?? 0);
                                        $taxedServicesTotal = (float) ($invoice->taxed_services_total_before_vat ?? 0);
                                        $untaxedServicesTotal = (float) ($invoice->untaxed_services_total_before_vat ?? 0);
                                        $vatValue = (float) ($invoice->value_added_tax_amount ?? 0);
                                        $saleValue = (float) ($invoice->sales_tax_amount ?? 0);
                                        $discountValue = (float) ($invoice->discount_amount ?? 0);

                                        $totalInvoices += ceil(
                                            $invoiceTotalBeforeTax
                                            + $taxedServicesTotal
                                            + $untaxedServicesTotal
                                            + $vatValue
                                            - $saleValue
                                            - $discountValue
                                        );

                                        // نفس منطق calculatePaidAmount: استبعاد الشيكات غير المستحقة
                                        $totalPayments += $invoice->invoicePayments
                                            ->filter(function ($payment) {
                                                return $payment->payment_type !== 'check' || !is_null($payment->check_paid_at);
                                            })
                                            ->sum('value');
                                    }

                                    $remainingBalance = $openingBalance + $totalInvoices - $totalPayments;
                                ?>
                                <tr>
                                    <td class="text-center align-middle">
                                        <span class="text-muted font-weight-bold"><?php echo e($company->id); ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-dark font-weight-bold mb-1"><?php echo e($company->name); ?></span>
                                            <?php if($company->address): ?>
                                                <span class="text-muted font-size-sm">
                                                    <i class="fas fa-map-marker-alt text-muted mr-1"></i>
                                                    <?php echo e($company->address); ?>

                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <?php if($company->email): ?>
                                                <span class="text-dark-75 font-size-sm mb-1">
                                                    <i class="fas fa-envelope text-primary mr-1"></i>
                                                    <a href="mailto:<?php echo e($company->email); ?>" class="text-primary"><?php echo e($company->email); ?></a>
                                                </span>
                                            <?php endif; ?>
                                            <?php if($company->phone): ?>
                                                <span class="text-dark-75 font-size-sm">
                                                    <i class="fas fa-phone text-success mr-1"></i>
                                                    <a href="tel:<?php echo e($company->phone); ?>" class="text-dark-75"><?php echo e($company->phone); ?></a>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($company->tax_no): ?>
                                            <span class="badge badge-light-primary font-weight-bold">
                                                <?php echo e($company->tax_no); ?>

                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-<?php echo e($company->taxed == 0 ? 'danger' : 'success'); ?> font-weight-bold">
                                            <i class="fa fa-<?php echo e($company->taxed == 0 ? 'times' : 'check'); ?> mr-1"></i>
                                            <?php echo e($company->taxed_invoice); ?>

                                        </span>
                                        <?php if($company->invoices->count()): ?>
                                            <div class="mt-1">
                                                <a href="<?php echo e(route('bokkings.invoices', $company->id)); ?>" class="btn btn-sm btn-light-primary">
                                                    <i class="fas fa-file-invoice mr-1"></i>
                                                    <?php echo e(__('main.Tax_invoices')); ?>

                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-light-info font-weight-bold">
                                            <?php echo e($company->bill_type == 1 ? __('admin.bill_type_invoice') : __('admin.bill_type_statement')); ?>

                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?php
                                            $lastInvoice = $company->companyInvoices()->latest()->first();
                                        ?>
                                        <?php if($lastInvoice && $lastInvoice->created_at): ?>
                                            <span class="text-dark-75 font-weight-bold">
                                                <?php echo e($lastInvoice->created_at->format('Y-m-d')); ?>

                                            </span>
                                            <div class="text-muted font-size-sm">
                                                <?php echo e($lastInvoice->created_at->diffForHumans()); ?>

                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="badge badge-<?php echo e($remainingBalance > 0 ? 'warning' : ($remainingBalance < 0 ? 'info' : 'success')); ?> badge-lg font-weight-bold mb-2">
                                                <?php echo e(number_format(abs($remainingBalance), 2)); ?> ج
                                            </span>
                                            <?php if($remainingBalance > 0): ?>
                                                <span class="text-muted font-size-sm mb-2">مستحق</span>
                                            <?php elseif($remainingBalance < 0): ?>
                                                <span class="text-muted font-size-sm mb-2">رصيد زائد</span>
                                            <?php else: ?>
                                                <span class="text-muted font-size-sm mb-2">مسدد</span>
                                            <?php endif; ?>
                                            <?php if(auth()->user()->hasPermissionTo('accounts.index')): ?>
                                                <a href="<?php echo e(route('accounts.statement', $company->id)); ?>" class="btn btn-sm btn-light-primary">
                                                    <i class="fas fa-file-invoice mr-1"></i>
                                                    كشف حساب
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?php if(!is_null($company->attachments)): ?>
                                            <div class="d-flex justify-content-center">
                                                <?php if(is_array($company->attachments)): ?>
                                                    <?php $__currentLoopData = $company->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <a href="<?php echo e(url($attachment)); ?>" target="_blank" class="btn btn-icon btn-light btn-hover-primary btn-sm mr-1" title="عرض الملف">
                                                            <i class="fas fa-file-<?php echo e(pathinfo($attachment, PATHINFO_EXTENSION) == 'pdf' ? 'pdf text-danger' : 'image text-primary'); ?>"></i>
                                                        </a>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php else: ?>
                                                    <a href="<?php echo e(url($company->attachments)); ?>" target="_blank" class="btn btn-icon btn-light btn-hover-primary btn-sm" title="عرض الملف">
                                                        <i class="fas fa-file-pdf text-danger"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="dropdown dropdown-inline">
                                            <a href="javascript:;" class="btn btn-sm btn-light-primary btn-icon" data-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right">
                                                <?php if(auth()->user()->hasPermissionTo('companies.update')): ?>
                                                    <a class="dropdown-item" href="<?php echo e(route('companies.edit', $company->id)); ?>">
                                                        <i class="fas fa-edit text-primary mr-2"></i> تعديل
                                                    </a>
                                                <?php endif; ?>
                                                <?php if(auth()->user()->hasPermissionTo('transportations.create')): ?>
                                                    <a class="dropdown-item" href="<?php echo e(route('companyTransportations.index', ['company_id' => $company->id])); ?>">
                                                        <i class="fas fa-plus text-success mr-2"></i> إضافة عرض سعر
                                                    </a>
                                                <?php endif; ?>
                                                <?php if(auth()->user()->hasPermissionTo('services.create')): ?>
                                                    <a class="dropdown-item" href="<?php echo e(route('companyServices.index', ['company' => $company])); ?>">
                                                        <i class="fas fa-cog text-info mr-2"></i> الخدمات
                                                    </a>
                                                <?php endif; ?>
                                                <?php if(auth()->user()->hasPermissionTo('accounts.index')): ?>
                                                    <a class="dropdown-item" href="<?php echo e(route('accounts.statement', $company->id)); ?>">
                                                        <i class="fas fa-file-invoice text-warning mr-2"></i> كشف حساب
                                                    </a>
                                                <?php endif; ?>
                                                <div class="dropdown-divider"></div>
                                                <?php if(auth()->user()->hasPermissionTo('companies.delete')): ?>
                                                    <a class="dropdown-item text-danger" href="javascript:;" onclick="Delete('<?php echo e($company->id); ?>')">
                                                        <i class="fas fa-trash mr-2"></i> حذف
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-10">
                        <i class="fas fa-building fa-3x text-muted mb-4"></i>
                        <h4 class="text-muted">لا توجد شركات</h4>
                        <?php if(auth()->user()->hasPermissionTo('companies.create')): ?>
                            <a href="<?php echo e(route('companies.create')); ?>" class="btn btn-primary mt-3">
                                <i class="fas fa-plus mr-2"></i> إضافة شركة جديدة
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if($companies->hasPages()): ?>
                <!--begin::Pagination-->
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mr-3">
                            <span class="text-muted font-weight-bold">
                                عرض <span class="text-dark"><?php echo e($companies->firstItem() ?? 0); ?></span>
                                إلى <span class="text-dark"><?php echo e($companies->lastItem() ?? 0); ?></span>
                                من <span class="text-dark"><?php echo e($companies->total()); ?></span> نتيجة
                            </span>
                        </div>
                        <div>
                            <?php echo e($companies->links()); ?>

                        </div>
                    </div>
                </div>
                <!--end::Pagination-->
                <?php endif; ?>
            </div>
        </div>
        <!--end::Card-->
    </div>

    <?php if(auth()->user()->hasPermissionTo('companies.index')): ?>
        <!-- Creates the bootstrap modal where the Note Of Transaction For users will appear -->
        <div class="modal fade" id="attachmentModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6><?php echo e(__('admin.attachment')); ?></h6>
                    </div>
                    <div class="modal-body" id="attachment_preview">

                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('css'); ?>
    <style>
        .table-separate {
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .table-separate thead th {
            border: none;
            background-color: #f3f6f9;
            color: #464e5f;
            font-weight: 600;
            padding: 15px 10px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-separate tbody tr {
            background-color: #fff;
            border: 1px solid #ebedf3;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .table-separate tbody tr:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .table-separate tbody td {
            border: none;
            padding: 20px 10px;
            vertical-align: middle;
        }

        .table-separate tbody tr:first-child td:first-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .table-separate tbody tr:first-child td:last-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .badge-lg {
            padding: 8px 16px;
            font-size: 14px;
        }

        .dropdown-menu {
            min-width: 200px;
        }

        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f3f6f9;
            padding-right: 25px;
        }

        .dropdown-item i {
            width: 20px;
        }
    </style>
<?php $__env->stopPush(); ?>
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
                    var url = '<?php echo e(route('companies.destroy', ':id')); ?>';
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

        function openFile(attach) {
            $('#attachment_preview').html(`<embed src="${attach}"  frameborder="0" width="100%" height="400px">`)
            $('#attachmentModal').modal('show');
        }
    </script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/companies/index.blade.php ENDPATH**/ ?>