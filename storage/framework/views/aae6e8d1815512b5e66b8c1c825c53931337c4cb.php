

<?php $__env->startSection("content"); ?>
<div class="container-fluid">

    <?php echo $__env->make("layouts.includes.breadcrumb", [
        'page' => 'كشف حساب - ' . $company->name
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="card shadow-sm border-0">

        <!-- Header -->
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <h4 class="mb-0">
                <i class="fas fa-file-invoice mr-2"></i>
                كشف حساب - <?php echo e($company->name); ?>

            </h4>

            <div>
                <a href="<?php echo e(route('accounts.statement.export.excel', ['companyId'=>$company->id,'from'=>$fromDate,'to'=>$toDate])); ?>"
                   class="btn btn-light btn-sm mr-2">
                    <i class="fas fa-file-excel text-success"></i> Excel
                </a>

                <a href="<?php echo e(route('accounts.statement.export.pdf', ['companyId'=>$company->id,'from'=>$fromDate,'to'=>$toDate])); ?>"
                   class="btn btn-light btn-sm mr-2">
                    <i class="fas fa-file-pdf text-danger"></i> PDF
                </a>

                <a href="<?php echo e(route('accounts.payment',$company->id)); ?>"
                   class="btn btn-light btn-sm">
                    <i class="fas fa-money-bill-wave text-success"></i> سداد
                </a>
            </div>
        </div>

        <div class="card-body">

            <!-- معلومات الشركة + الملخص -->
            <div class="row mb-4">

                <div class="col-md-6">
                    <div class="card border shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-building"></i> معلومات الشركة
                            </h5>
                            <p><strong>الاسم:</strong> <?php echo e($company->name); ?></p>
                            <p><strong>البريد:</strong> <?php echo e($company->email ?? '-'); ?></p>
                            <p><strong>الهاتف:</strong> <?php echo e($company->phone ?? '-'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card bg-light border shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-chart-line"></i> ملخص الحساب
                            </h5>

                            <p>الرصيد الافتتاحي:
                                <span class="float-left font-weight-bold">
                                    <?php echo e(number_format($company->opening_balance ?? 0,2)); ?> ج.م
                                </span>
                            </p>

                            <p>الرصيد المرحل:
                                <span class="float-left font-weight-bold">
                                    <?php echo e(number_format($carriedForwardBalance,2)); ?> ج.م
                                </span>
                            </p>

                            <p>إجمالي الفواتير:
                                <span class="float-left text-danger font-weight-bold">
                                    <?php echo e(number_format($totalInvoices,2)); ?> ج.م
                                </span>
                            </p>

                            <p>إجمالي السداد:
                                <span class="float-left text-success font-weight-bold">
                                    <?php echo e(number_format($totalPayments,2)); ?> ج.م
                                </span>
                            </p>

                            <hr>

                            <h5>
                                الرصيد النهائي:
                                <span class="<?php echo e($finalBalance >= 0 ? 'text-danger' : 'text-success'); ?>">
                                    <?php echo e(number_format(abs($finalBalance),2)); ?> ج.م
                                </span>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- فلتر -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#filterModal">
                    <i class="fas fa-filter"></i> فلتر
                </button>

                <strong>
                    من <?php echo e($fromDate); ?> إلى <?php echo e($toDate); ?>

                </strong>
            </div>

            <!-- جدول -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped text-center">
                    <thead style="background:#1f2a44; color:#fff;">
                        <tr>
                            <th>التاريخ</th>
                            <th>رقم الطلب</th>
                            <th>رقم الفاتورة</th>
                            <th>نوع العملية</th>
                            <th>الإجمالي</th>
                            <th>تم دفع</th>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>ملاحظات</th>
                            <th>تفاصيل</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $date = $transaction['date'] instanceof \Carbon\Carbon ? $transaction['date'] : \Carbon\Carbon::parse($transaction['date']);
                                $paymentDetails = $transaction['payment_details'] ?? [];
                                $hasPaymentDetails = isset($transaction['payment_details']) && is_array($paymentDetails) && count($paymentDetails) > 0;
                            ?>

                            <tr>
                                <td>
                                    <?php echo e($date->format('Y-m-d')); ?>

                                    <br>
                                    <small class="text-muted">
                                        <?php echo e($date->format('H:i')); ?>

                                    </small>
                                </td>

                                <td><?php echo e($transaction['booking_number'] ?? '-'); ?></td>

                                <td><?php echo e($transaction['invoice_number'] ?? '-'); ?></td>

                                <td>
                                    <?php if($transaction['type'] == 'invoice'): ?>
                                        <span class="badge badge-danger">فاتورة</span>
                                    <?php elseif($transaction['type'] == 'payment'): ?>
                                        <span class="badge badge-success">سداد</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">
                                            <?php echo e($transaction['type_label'] ?? '-'); ?>

                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-danger font-weight-bold">
                                    <?php echo e(number_format($transaction['total'] ?? 0,2)); ?>

                                </td>

                                <td class="text-success font-weight-bold">
                                    <?php echo e(number_format($transaction['paid'] ?? 0,2)); ?>

                                </td>

                                <td>
                                    <?php echo e(number_format($transaction['current_debit'] ?? 0,2)); ?>

                                </td>

                                <td>
                                    <?php echo e(number_format($transaction['current_credit'] ?? 0,2)); ?>

                                </td>

                                <td>
                                    <?php echo e($transaction['notes'] ?? '-'); ?>

                                </td>

                                <td>
                                    <?php if($hasPaymentDetails): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-info"
                                                data-toggle="modal"
                                                data-target="#transactionDetailsModal<?php echo e($index); ?>"
                                                title="عرض تفاصيل السداد">
                                            <i class="fas fa-eye"></i> تفاصيل
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if(($transaction['type'] ?? '') === 'payment' && $hasPaymentDetails): ?>
                                        <?php
                                            $paymentIds = collect($paymentDetails)
                                                ->pluck('id')
                                                ->filter()
                                                ->implode(',');
                                        ?>
                                        <?php if(!empty($paymentIds)): ?>
                                            <form method="POST"
                                                  action="<?php echo e(route('accounts.payment.group.destroy', ['companyId' => $company->id, 'from' => $fromDate, 'to' => $toDate])); ?>"
                                                  onsubmit="return confirm('هل أنت متأكد من حذف عملية السداد بالكامل؟ سيتم إعادة المبالغ كمستحق على الشركة.');"
                                                  class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <input type="hidden" name="payment_ids" value="<?php echo e($paymentIds); ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="حذف عملية السداد بالكامل">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="11" class="py-4 text-muted">
                                    لا توجد بيانات
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- Modals تفاصيل العمليات -->
<?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $paymentDetails = $transaction['payment_details'] ?? [];
        $hasPaymentDetails = isset($transaction['payment_details']) && is_array($paymentDetails) && count($paymentDetails) > 0;
    ?>
    <?php if($hasPaymentDetails): ?>
        <?php
            $paymentIdsForReceipt = collect($paymentDetails)->pluck('id')->filter()->implode(',');
            $companyReceiptPrintUrl = $paymentIdsForReceipt !== ''
                ? route('accounts.statement.payment-receipt', ['companyId' => $company->id, 'payment_ids' => $paymentIdsForReceipt])
                : null;
            $companyReceiptPdfUrl = $paymentIdsForReceipt !== ''
                ? route('accounts.statement.payment-receipt-pdf', ['companyId' => $company->id, 'payment_ids' => $paymentIdsForReceipt])
                : null;
        ?>
        <!-- Modal تفاصيل العملية -->
        <div class="modal fade" id="transactionDetailsModal<?php echo e($index); ?>" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl" role="document" style="max-width: 1100px;">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title font-weight-bold mb-0">
                            <i class="fas fa-info-circle ml-2"></i>
                            تفاصيل السداد — <?php echo e(number_format($transaction['paid'] ?? 0, 2)); ?> ج.م
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="إغلاق">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th class="text-center">رقم الفاتورة</th>
                                        <th class="text-center">رقم الطلب</th>
                                        <th class="text-center">المبلغ</th>
                                        <th class="text-center">نوع السداد</th>
                                        <th class="text-center">البنك</th>
                                        <th class="text-center">ملاحظات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($hasPaymentDetails && is_array($paymentDetails) && count($paymentDetails) > 0): ?>
                                        <?php $__currentLoopData = $paymentDetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detailIndex => $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td class="text-center"><?php echo e($detailIndex + 1); ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-info"><?php echo e($detail['invoice_number'] ?? '-'); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-secondary"><?php echo e($detail['booking_number'] ?? '-'); ?></span>
                                                </td>
                                                <td class="text-center font-weight-bold text-success" style="font-size: 1.1em;">
                                                    <?php echo e(number_format($detail['value'] ?? 0, 2)); ?> ج.م
                                                </td>
                                                <td class="text-center">
                                                    <?php if(($detail['payment_type'] ?? '') == 'check'): ?>
                                                        <span class="badge badge-warning badge-pill">
                                                            <i class="fas fa-money-check mr-1"></i>شيك
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-primary badge-pill">
                                                            <i class="fas fa-university mr-1"></i>تحويل بنكي
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center"><?php echo e($detail['bank_name'] ?? '-'); ?></td>
                                                <td class="text-center"><?php echo e($detail['notes'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                                                لا توجد تفاصيل متاحة
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-info font-weight-bold">
                                        <td colspan="3" class="text-right">الإجمالي:</td>
                                        <td class="text-center text-success"><?php echo e(number_format($transaction['paid'] ?? 0, 2)); ?> ج.م</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <?php if($companyReceiptPrintUrl): ?>
                            <h6 class="font-weight-bold mt-4 mb-2">
                                <i class="fas fa-file-invoice ml-1"></i> بيان السداد للطباعة
                            </h6>
                            <p class="text-muted small mb-2">يُحمَّل عند فتح النافذة؛ نفس تنسيق بيان السداد القابل للطباعة أو التحميل PDF.</p>
                            <iframe id="companyPaymentReceiptIframe<?php echo e($index); ?>"
                                    class="company-payment-receipt-iframe w-100 border rounded"
                                    title="بيان سداد"
                                    data-src="<?php echo e($companyReceiptPrintUrl); ?>"
                                    style="height: 420px; min-height: 280px; background: #fff;"></iframe>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer flex-column align-items-stretch pt-3">
                        <?php if($companyReceiptPdfUrl): ?>
                            <a href="<?php echo e($companyReceiptPdfUrl); ?>"
                               class="btn font-weight-bold shadow-sm text-white border-0 company-statement-payment-pdf-btn w-100 text-center mb-2"
                               title="تحميل بيان السداد PDF"
                               dir="rtl">
                                <i class="fas fa-file-pdf ml-2"></i>
                                طباعة بيان السداد PDF
                            </a>
                            <button type="button"
                                    class="btn btn-primary font-weight-bold js-print-company-statement-receipt mb-2"
                                    data-iframe-index="<?php echo e($index); ?>">
                                <i class="fas fa-print ml-1"></i> طباعة البيان
                            </button>
                            <a href="<?php echo e($companyReceiptPrintUrl); ?>"
                               class="btn btn-outline-primary font-weight-bold mb-2"
                               target="_blank"
                               rel="noopener">
                                <i class="fas fa-external-link-alt ml-1"></i> فتح في نافذة جديدة
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary font-weight-bold align-self-center" data-dismiss="modal">إغلاق</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<!-- Modal الفلتر -->
<div class="modal fade" id="filterModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="<?php echo e(route('accounts.statement',$company->id)); ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">فلترة حسب التاريخ</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <label>من</label>
                    <input type="date" name="from" value="<?php echo e($fromDate); ?>" class="form-control mb-3">

                    <label>إلى</label>
                    <input type="date" name="to" value="<?php echo e($toDate); ?>" class="form-control">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">تطبيق</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        padding: 1rem 1.5rem;
    }
    .card-header h4 {
        color: white !important;
        font-weight: bold;
    }
    .card-header .btn-light {
        background-color: rgba(255, 255, 255, 0.95) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        color: #333 !important;
    }
    .card-header .btn-light:hover {
        background-color: #fff !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    /* تعطيل DataTable */
    .dataTables_wrapper {
        display: none !important;
    }
    .dataTables_filter,
    .dataTables_length,
    .dataTables_info,
    .dataTables_paginate {
        display: none !important;
    }
    .btn-info {
        background-color: #17a2b8;
        border-color: #17a2b8;
    }
    .btn-info:hover {
        background-color: #138496;
        border-color: #117a8b;
    }
    .company-statement-payment-pdf-btn {
        background-color: #f84d5f !important;
        color: #fff !important;
        border-radius: 10px !important;
        padding: 10px 22px !important;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
    }
    .company-statement-payment-pdf-btn:hover {
        background-color: #e63d52 !important;
        color: #fff !important;
        text-decoration: none;
    }
</style>

<?php $__env->startPush('js'); ?>
<script>
    function printCompanyStatementReceipt(index) {
        var iframe = document.getElementById('companyPaymentReceiptIframe' + index);
        if (!iframe || !iframe.contentWindow) {
            return;
        }
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } catch (e) {
            var src = iframe.getAttribute('src');
            if (src) {
                window.open(src, '_blank');
            }
        }
    }

    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('.table').each(function() {
                if ($.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable().destroy();
                }
            });
        }
    });

    $(document).on('shown.bs.modal', '[id^="transactionDetailsModal"]', function (e) {
        var $modal = $(e.target);
        var $iframe = $modal.find('iframe.company-payment-receipt-iframe');
        if (!$iframe.length || $iframe.data('loaded')) {
            return;
        }
        var src = $iframe.data('src');
        if (src) {
            $iframe.attr('src', src).data('loaded', true);
        }
    });

    $(document).on('click', '.js-print-company-statement-receipt', function () {
        var idx = $(this).data('iframe-index');
        printCompanyStatementReceipt(idx);
    });
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/accounts/statement.blade.php ENDPATH**/ ?>