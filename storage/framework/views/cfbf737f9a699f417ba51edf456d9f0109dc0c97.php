

<?php $__env->startSection("content"); ?>
<style>
    .car-statement-payment-pdf-btn {
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
    .car-statement-payment-pdf-btn:hover {
        background-color: #e63d52 !important;
        color: #fff !important;
        text-decoration: none;
    }
    .car-statement-payment-pdf-btn:focus {
        box-shadow: 0 0 0 0.2rem rgba(248, 77, 95, 0.35);
    }
</style>
<div class="container-fluid">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => 'كشف حساب سيارة - ' . $car->car_number ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-car text-primary mr-2"></i>
                    كشف حساب سيارة - <?php echo e($car->car_number); ?>

                </h3>
            </div>
            <div class="card-toolbar">
                <a href="<?php echo e(route('accounts.car.statement.export.excel', ['carId' => $car->id, 'from' => $fromDate, 'to' => $toDate])); ?>"
                   class="btn btn-primary font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-file-excel"></i> تصدير Excel
                </a>
                <a href="<?php echo e(route('accounts.car.statement.export.pdf', ['carId' => $car->id, 'from' => $fromDate, 'to' => $toDate])); ?>"
                   class="btn btn-danger font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-file-pdf"></i> تصدير PDF
                </a>
                <button type="button" class="btn btn-secondary font-weight-bold shadow-sm" data-toggle="modal" data-target="#filterModal">
                    <i class="fas fa-filter"></i> فلتر
                </button>
            </div>
        </div>

        <!-- Modal الفلتر -->
        <div class="modal fade" id="filterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold">
                            <i class="fas fa-calendar-alt mr-2"></i>فلترة حسب التاريخ
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form action="<?php echo e(route('accounts.car.statement', $car->id)); ?>" method="get">
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="font-weight-bold">من تاريخ</label>
                                <input type="date" name="from" value="<?php echo e($fromDate); ?>" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">إلى تاريخ</label>
                                <input type="date" name="to" value="<?php echo e($toDate); ?>" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">تطبيق</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- معلومات السيارة -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات السيارة</h5>
                    <p><strong>رقم السيارة:</strong> <?php echo e($car->car_number); ?></p>
                    <p><strong>النوع:</strong> <?php echo e($car->type ?? '-'); ?></p>
                </div>
                <div class="col-md-6 text-right">
                    <h5 class="font-weight-bold">ملخص الحساب</h5>
                    <p><strong>الرصيد المرحّل:</strong>
                        <span class="text-<?php echo e($carriedForwardBalance >= 0 ? 'danger' : 'success'); ?>">
                            <?php echo e(number_format($carriedForwardBalance, 2)); ?>

                        </span>
                    </p>
                    <p><strong>إجمالي القيمة:</strong>
                        <span class="text-info"><?php echo e(number_format($totalValue, 2)); ?></span>
                    </p>
                    <p><strong>إجمالي العهدة:</strong>
                        <span class="text-info"><?php echo e(number_format($totalCustody, 2)); ?></span>
                    </p>
                    <p><strong>إجمالي الدفعات:</strong>
                        <span class="text-success"><?php echo e(number_format($totalPayments, 2)); ?></span>
                    </p>
                    <p><strong>الرصيد النهائي المستحق:</strong>
                        <span class="text-<?php echo e($finalBalance >= 0 ? 'danger' : 'success'); ?> font-weight-bold">
                            <?php echo e(number_format($finalBalance, 2)); ?>

                        </span>
                    </p>
                </div>
            </div>

            <!-- جدول كشف الحساب -->
            <h5 class="font-weight-bold mt-4 mb-3">الحساب في الفترة من <?php echo e($fromDate); ?> الى <?php echo e($toDate); ?></h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" style="font-size: 12px;">
                    <thead class="thead-dark">
                        <tr class="text-center">
                            <th rowspan="2" style="vertical-align: middle; width: 7%;">التاريخ</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">حساب سابق</th>
                            <th rowspan="2" style="vertical-align: middle; width: 7%;">الخدمة</th>
                            <th rowspan="2" style="vertical-align: middle; width: 10%;">الوصف</th>
                            <th rowspan="2" style="vertical-align: middle; width: 7%;">رقم الحاوية</th>
                            <th rowspan="2" style="vertical-align: middle; width: 7%;">خروج</th>
                            <th rowspan="2" style="vertical-align: middle; width: 7%;">الوجهة</th>
                            <th rowspan="2" style="vertical-align: middle; width: 7%;">تعتيق</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">القيمة</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">العهدة</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">الإجمالي</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">الإجمالي</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">مدين أو دائن</th>
                            <th rowspan="2" style="vertical-align: middle; width: 9%;">اجمالي النقلة + حساب سابق</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">تفاصيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $date = $transaction['date'] instanceof \Carbon\Carbon ? $transaction['date'] : \Carbon\Carbon::parse($transaction['date']);
                                $paymentDetails = $transaction['payment_details'] ?? [];
                                $hasPaymentDetails = ($transaction['type'] ?? '') === 'payment_group'
                                    && is_array($paymentDetails)
                                    && count($paymentDetails) > 0;
                            ?>
                            <tr>
                                <td class="text-center" style="font-size: 10px;"><?php echo e($date->format('Y-m-d')); ?><br><small><?php echo e($date->format('H:i')); ?></small></td>
                                <td class="text-center"><?php echo e($transaction['previous_balance'] > 0 ? number_format($transaction['previous_balance'], 2) : '-'); ?></td>
                                <td class="text-center"><?php echo e($transaction['service'] ?: '-'); ?></td>
                                <td class="text-center" style="font-size: 10px;"><?php echo e($transaction['description'] ?: '-'); ?></td>
                                <td class="text-center"><?php echo e($transaction['container_no'] ?: '-'); ?></td>
                                <td class="text-center" style="font-size: 10px;"><?php echo e($transaction['departure'] ?: '-'); ?></td>
                                <td class="text-center" style="font-size: 10px;"><?php echo e($transaction['destination'] ?: '-'); ?></td>
                                <td class="text-center" style="font-size: 10px;"><?php echo e($transaction['aging'] ?: '-'); ?></td>
                                <td class="text-center"><?php echo e($transaction['value'] > 0 ? number_format($transaction['value'], 2) : '-'); ?></td>
                                <td class="text-center"><?php echo e($transaction['custody'] > 0 ? number_format($transaction['custody'], 2) : '-'); ?></td>
                                <td class="text-center"><?php echo e($transaction['total1'] > 0 ? number_format($transaction['total1'], 2) : '-'); ?></td>
                                <td class="text-center"><?php echo e($transaction['total2'] > 0 ? number_format($transaction['total2'], 2) : '-'); ?></td>
                                <td class="text-center <?php echo e($transaction['debit_credit'] == 'مدين' ? 'text-danger font-weight-bold' : 'text-success font-weight-bold'); ?>">
                                    <?php echo e($transaction['debit_credit']); ?>

                                </td>
                                <td class="text-center <?php echo e($transaction['running_balance'] >= 0 ? 'text-danger' : 'text-success'); ?> font-weight-bold">
                                    <?php echo e(number_format($transaction['running_balance'], 2)); ?>

                                </td>
                                <td class="text-center align-middle">
                                    <?php if($hasPaymentDetails): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-info"
                                                data-toggle="modal"
                                                data-target="#carPaymentDetailsModal<?php echo e($index); ?>"
                                                title="عرض تفاصيل السداد">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="15" class="text-center py-5">لا توجد حركات في هذه الفترة</td>
                            </tr>
                        <?php endif; ?>

                        <!-- صف الإجمالي النهائي -->
                        <?php if($transactions->count() > 0): ?>
                            <?php
                                $totalPreviousBalance = $transactions->sum('previous_balance');
                                $totalValue = $transactions->sum('value');
                                $totalCustody = $transactions->sum('custody');
                                $total1 = $transactions->sum('total1');
                                $total2 = $transactions->sum('total2');
                                $finalRunningBalance = $transactions->last()['running_balance'] ?? $finalBalance;
                            ?>
                            <tr class="table-info font-weight-bold">
                                <td colspan="2" class="text-center">الحساب النهائي يوم <?php echo e($toDate); ?></td>
                                <td colspan="6" class="text-center"></td>
                                <td class="text-center"><?php echo e(number_format($totalValue, 2)); ?></td>
                                <td class="text-center"><?php echo e(number_format($totalCustody, 2)); ?></td>
                                <td class="text-center"><?php echo e(number_format($total1, 2)); ?></td>
                                <td class="text-center"><?php echo e(number_format($total2, 2)); ?></td>
                                <td class="text-center">—</td>
                                <td class="text-center <?php echo e($finalRunningBalance >= 0 ? 'text-danger' : 'text-success'); ?>">
                                    <?php echo e(number_format(abs($finalRunningBalance), 2)); ?>

                                </td>
                                <td class="text-center">—</td>
                            </tr>
                            <tr class="table-warning font-weight-bold">
                                <td colspan="13" class="text-center">الرصيد النهائي المستحق</td>
                                <td class="text-center <?php echo e($finalRunningBalance >= 0 ? 'text-danger' : 'text-success'); ?>">
                                    <?php echo e(number_format(abs($finalRunningBalance), 2)); ?>

                                </td>
                                <td class="text-center">—</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $paymentDetails = $transaction['payment_details'] ?? [];
        $hasPaymentDetails = ($transaction['type'] ?? '') === 'payment_group'
            && is_array($paymentDetails)
            && count($paymentDetails) > 0;
    ?>
    <?php if($hasPaymentDetails): ?>
        <?php
            $groupUuidForPdf = $transaction['payment_group_uuid'] ?? '';
            $paymentReceiptPdfUrl = $groupUuidForPdf !== ''
                ? route('accounts.car.statement.payment-receipt-pdf', ['carId' => $car->id, 'group' => $groupUuidForPdf])
                : null;
        ?>
        <div class="modal fade" id="carPaymentDetailsModal<?php echo e($index); ?>" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title font-weight-bold mb-0">
                            <i class="fas fa-info-circle ml-2"></i>
                            تفاصيل السداد — <?php echo e(number_format($transaction['total2'] ?? 0, 2)); ?> ج.م
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
                                        <th class="text-center">رقم النقلة</th>
                                        <th class="text-center">رقم الحاوية</th>
                                        <th class="text-center">المبلغ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $paymentDetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detailIndex => $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td class="text-center"><?php echo e($detailIndex + 1); ?></td>
                                            <td class="text-center">
                                                <span class="badge badge-secondary"><?php echo e($detail['delivery_policy_id'] ?? '-'); ?></span>
                                            </td>
                                            <td class="text-center"><?php echo e($detail['container_numbers'] ?? '-'); ?></td>
                                            <td class="text-center font-weight-bold text-success">
                                                <?php echo e(number_format($detail['value'] ?? 0, 2)); ?> ج.م
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-success font-weight-bold">
                                        <td colspan="3" class="text-right">الإجمالي:</td>
                                        <td class="text-center"><?php echo e(number_format($transaction['total2'] ?? 0, 2)); ?> ج.م</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer flex-column align-items-stretch pt-3">
                        <?php if($paymentReceiptPdfUrl): ?>
                            <a href="<?php echo e($paymentReceiptPdfUrl); ?>"
                               class="btn font-weight-bold shadow-sm text-white border-0 car-statement-payment-pdf-btn w-100 text-center mb-2"
                               title="تحميل بيان السداد PDF"
                               dir="rtl">
                                <i class="fas fa-file-pdf ml-2"></i>
                                طباعة بيان السداد PDF
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary font-weight-bold align-self-center" data-dismiss="modal">إغلاق</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/accounts/car-statement.blade.php ENDPATH**/ ?>