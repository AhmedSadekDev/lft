<?php $__env->startSection('css'); ?>
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

    .badge-lg {
        padding: 8px 16px;
        font-size: 14px;
    }

    .separator {
        height: 2px;
        background: linear-gradient(90deg, transparent, #ebedf3 50%, transparent);
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => __('main.Tax_invoices')], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap  align-items-center py-5">
                <div class="card-toolbar">
                    <div class="">
                        <!--begin::Button-->
                        <a href="<?php echo e(route('bookings.create', request()->id)); ?>" class="btn btn-primary font-weight-bolder">
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
                    </div>

                    <!--end::Button-->
                </div>
                <div class="">
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                        <?php echo e(__('admin.filter')); ?>

                    </button>

                    <a href="<?php echo e(route('invoice_payments.excel', request()->id)); ?>"
                        class="btn btn-primary"><?php echo e(__('admin.export')); ?></a>

                    <a href="<?php echo e(route('invoice_payments.pdf', request()->id)); ?>"
                        class="btn btn-primary"><?php echo e(__('main.download_all_invoices')); ?></a>


                    <!-- Modal -->
                    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel"><?php echo e(__('admin.filter')); ?></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        X
                                    </button>
                                </div>
                                <form action="<?php echo e(route('bokkings.invoices', request()->id)); ?>" method="get">
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="idSelect"><?php echo e(__('main.companies')); ?></label>
                                                    <select class="form-control" name="id" id="idSelect">
                                                        <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <option value="<?php echo e($company->id); ?>"><?php echo e($company->name); ?>

                                                            </option>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dateFrom"><?php echo e(__('admin.from')); ?></label>
                                                    <input id="dateFrom" class="form-control" type="date"
                                                        name="date_from">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dateTo"><?php echo e(__('admin.to')); ?></label>
                                                    <input id="dateTo" class="form-control" type="date"
                                                        name="date_to">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary"><?php echo e(__('admin.filter')); ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Invoices Section -->
                <?php if($bookings->count() > 0): ?>
                <div class="mb-10">
                    <h4 class="mb-4 font-weight-bold text-dark">الفواتير</h4>
                    <div class="table-responsive">
                        <table class="table table-separate table-head-custom no-datatable" id="invoices-table" style="width: 100%">
                            <thead>
                                <tr>
                                    <th style="min-width: 120px">التاريخ</th>
                                    <th style="min-width: 120px">رقم الفاتورة</th>
                                    <th class="text-center" style="min-width: 100px">نوع العملية</th>
                                    <th class="text-center" style="min-width: 100px">الضريبة</th>
                                    <th class="text-center" style="min-width: 100px">الخصم</th>
                                    <th class="text-center" style="min-width: 120px">المبالغ</th>
                                    <th class="text-center" style="min-width: 120px">الإجمالي</th>
                                    <th class="text-center" style="min-width: 120px">المدفوع</th>
                                    <th class="text-center" style="min-width: 120px">المتبقي</th>
                                    <th class="text-center" style="min-width: 100px">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $invoice = $booking->invoice ?? null;

                                        if ($invoice) {
                                            $invoiceTotalBeforeTax = $invoice->invoice_total_before_tax;
                                            $vatValue = $invoice->value_added_tax_amount;
                                            $saleValue = $invoice->sales_tax_amount;
                                            $discountValue = $invoice->discount_amount;

                                            $taxedServicesTotal = $invoice->taxed_services_total_before_vat ?? 0;
                                            $untaxedServicesTotal = $invoice->untaxed_services_total_before_vat ?? 0;
                                            $transportationTotal = $invoice->transportation_total_before_vat ?? 0;

                                            $finalValue = $invoiceTotalBeforeTax + $taxedServicesTotal + $untaxedServicesTotal + $vatValue - $saleValue - $discountValue;
                                            $paidAmount = $booking->invoice->invoicePayments->sum('value') ?? 0;
                                            $remainingAmount = $finalValue - $paidAmount;
                                        } else {
                                            $vatValue = $saleValue = $discountValue = $taxedServicesTotal = $untaxedServicesTotal = $transportationTotal = $finalValue = $paidAmount = $remainingAmount = 0;
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="text-dark-75 font-weight-bold"><?php echo e($booking->created_at->format('Y-m-d')); ?></span>
                                            <div class="text-muted font-size-sm"><?php echo e($booking->created_at->format('H:i')); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-primary font-weight-bold"><?php echo e($booking->invoice->invoice_number ?? '-'); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if($booking->type_of_action == 0): ?>
                                                <span class="badge badge-info"><?php echo e(__('actions.Outbound')); ?></span>
                                            <?php elseif($booking->type_of_action == 1): ?>
                                                <span class="badge badge-success"><?php echo e(__('actions.Inbound')); ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-warning"><?php echo e(__('actions.Clearance')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-dark-75 font-weight-bold"><?php echo e(number_format($vatValue, 2)); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-danger font-weight-bold"><?php echo e(number_format($discountValue, 2)); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column">
                                                <small class="text-muted">ضريبي: <span class="text-dark"><?php echo e(number_format($taxedServicesTotal, 2)); ?></span></small>
                                                <small class="text-muted">غير ضريبي: <span class="text-dark"><?php echo e(number_format($untaxedServicesTotal, 2)); ?></span></small>
                                                <small class="text-muted">نقل: <span class="text-dark"><?php echo e(number_format($transportationTotal, 2)); ?></span></small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-lg badge-primary font-weight-bold"><?php echo e(number_format($finalValue, 2)); ?> ر.س</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo e(route('invoice_payments.index', $booking->invoice->id)); ?>" class="text-primary font-weight-bold">
                                                <?php echo e(number_format($paidAmount, 2)); ?> ر.س
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <?php if($remainingAmount > 0): ?>
                                                <span class="badge badge-lg badge-warning font-weight-bold"><?php echo e(number_format($remainingAmount, 2)); ?> ر.س</span>
                                                <div class="text-danger font-size-sm">مستحق</div>
                                            <?php elseif($remainingAmount < 0): ?>
                                                <span class="badge badge-lg badge-info font-weight-bold"><?php echo e(number_format(abs($remainingAmount), 2)); ?> ر.س</span>
                                                <div class="text-info font-size-sm">زائد</div>
                                            <?php else: ?>
                                                <span class="badge badge-lg badge-success font-weight-bold">0.00 ر.س</span>
                                                <div class="text-success font-size-sm">مسدد</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown dropdown-inline">
                                                <a href="javascript:;" class="btn btn-sm btn-light-primary btn-icon" data-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right">
                                                    <a class="dropdown-item" href="<?php echo e(route('booking-invoices.show', $booking->invoice->id)); ?>">
                                                        <i class="fas fa-eye text-primary mr-2"></i> عرض التفاصيل
                                                    </a>
                                                    <?php if($finalValue > ($booking->invoice->invoicePayments->sum('value') ?? 0)): ?>
                                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#createModal" data-invoice_id="<?php echo e($invoice->id); ?>" onclick="$('#invoiceIdInput').val('<?php echo e($invoice->id); ?>')">
                                                            <i class="fas fa-money-bill-wave text-success mr-2"></i> سداد
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
                </div>
                <?php endif; ?>

                <!-- Payments Section -->
                <?php if($allPayments->count() > 0): ?>
                <div class="separator separator-dashed my-10"></div>
                <div>
                    <h4 class="mb-4 font-weight-bold text-dark">المدفوعات</h4>
                    <div class="table-responsive">
                        <table class="table table-separate table-head-custom no-datatable" id="payments-table" style="width: 100%">
                            <thead>
                                <tr>
                                    <th style="min-width: 80px">#</th>
                                    <th style="min-width: 120px">رقم الفاتورة</th>
                                    <th class="text-center" style="min-width: 120px">المبلغ</th>
                                    <th class="text-center" style="min-width: 100px">صورة التحويل</th>
                                    <th style="min-width: 120px">تاريخ السداد</th>
                                    <th class="text-center" style="min-width: 100px">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $allPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td>
                                            <span class="text-muted font-weight-bold"><?php echo e($payment->id); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-info font-weight-bold"><?php echo e($payment->invoice->invoice_number ?? '-'); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-lg badge-success font-weight-bold"><?php echo e(number_format($payment->value, 2)); ?> ر.س</span>
                                        </td>
                                        <td class="text-center">
                                            <?php if($payment->image): ?>
                                                <a href="<?php echo e(asset($payment->image)); ?>" download class="btn btn-icon btn-light btn-hover-primary btn-sm">
                                                    <img src="<?php echo e(asset($payment->image)); ?>" alt="تحويل" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-dark-75 font-weight-bold"><?php echo e($payment->created_at->format('Y-m-d')); ?></span>
                                            <div class="text-muted font-size-sm"><?php echo e($payment->created_at->format('H:i')); ?></div>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown dropdown-inline">
                                                <a href="javascript:;" class="btn btn-sm btn-light-primary btn-icon" data-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right">
                                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#updateModal" data-url="<?php echo e(route('invoice_payments.update', $payment->id)); ?>" data-id="<?php echo e($payment->id); ?>" data-value="<?php echo e($payment->value); ?>" data-bank_id="<?php echo e($payment->bank_id); ?>">
                                                        <i class="fas fa-edit text-primary mr-2"></i> تعديل
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="javascript:;" onclick="Delete('<?php echo e($payment->id); ?>')">
                                                        <i class="fas fa-trash mr-2"></i> حذف
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($bookings->count() == 0 && $allPayments->count() == 0): ?>
                    <div class="text-center py-10">
                        <i class="fas fa-file-invoice fa-3x text-muted mb-4"></i>
                        <h4 class="text-muted">لا توجد فواتير أو مدفوعات</h4>
                    </div>
                <?php endif; ?>
                </div>
            </div>


        </div>
        <!-- Modal -->
        <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createModalLabel"><?php echo e(__('Pay')); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="<?php echo e(route('invoice_payments.store')); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="invoice_id" id="invoiceIdInput">

                        <div class="modal-body">
                            <div class="form-group">
                                <label for="bank_id"><?php echo e(__('admin.bank')); ?></label>
                                <select name="bank_id" id="bank_id" class="form-control">
                                    <?php $__currentLoopData = $banks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bank): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($bank->id); ?>"><?php echo e($bank->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['bank_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-danger"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="form-group">
                                <label for="value"><?php echo e(__('admin.value')); ?></label>
                                <input type="text" name="value" id="value" class="form-control">
                                <?php $__errorArgs = ['value'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-danger"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="form-group">
                                <label for="image"><?php echo e(__('admin.image')); ?></label>
                                <input type="file" name="image" id="image" class="form-control">
                                <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-danger"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-dismiss="modal"><?php echo e(__('admin.close')); ?></button>
                            <button type="submit" class="btn btn-primary"><?php echo e(__('admin.save')); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Update Modal -->
        <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateModalLabel">تعديل المدفوع</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="updatePaymentForm" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('POST'); ?>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="update_bank_id"><?php echo e(__('admin.bank')); ?></label>
                                <select name="bank_id" id="update_bank_id" class="form-control">
                                    <?php $__currentLoopData = $banks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bank): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($bank->id); ?>"><?php echo e($bank->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['bank_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-danger"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="form-group">
                                <label for="update_value"><?php echo e(__('admin.value')); ?></label>
                                <input type="text" name="value" id="update_value" class="form-control" required>
                                <?php $__errorArgs = ['value'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-danger"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="form-group">
                                <label for="update_image"><?php echo e(__('admin.image')); ?></label>
                                <input type="file" name="image" id="update_image" class="form-control">
                                <small class="text-muted">اتركه فارغاً إذا لم ترد تغيير الصورة</small>
                                <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-danger"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo e(__('admin.close')); ?></button>
                            <button type="submit" class="btn btn-primary"><?php echo e(__('admin.save')); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!--end::Card-->
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
<script>
    $(document).on('click', '.create-btn', function() {
      $('#invoiceIdInput').val($(this).data('invoice_id'));
    });

    // Handle update modal
    $(document).on('click', '[data-target="#updateModal"]', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        var id = $(this).data('id');
        var value = $(this).data('value');
        var bankId = $(this).data('bank_id') || null;

        $('#updatePaymentForm').attr('action', url);
        $('#update_value').val(value);
        if (bankId) {
            $('#update_bank_id').val(bankId);
        }
    });

    // Handle delete
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
                var url = '<?php echo e(route('invoice_payments.destroy', ':id')); ?>';
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
                    type: 'POST',
                    success: function(response, textStatus, xhr) {
                        if (xhr.status == 200) {
                            Swal.fire({
                                title: "<?php echo e(__('alerts.done')); ?>",
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: "<?php echo e(__('alerts.error')); ?>",
                            text: xhr.responseJSON?.msg || 'حدث خطأ',
                            icon: 'error',
                        });
                    }
                });
            }
        });
    }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/invoices/index.blade.php ENDPATH**/ ?>