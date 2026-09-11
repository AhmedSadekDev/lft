<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف حساب - <?php echo e($company->name); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 10pt;
            padding: 8px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px 0;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #333;
        }
        .header .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        .header .period {
            font-size: 12px;
            color: #666;
        }
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8.5pt;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 5px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.35;
        }
        td.text-wrap {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            font-size: 8pt;
        }
        td.num, th.num {
            white-space: nowrap;
            font-size: 8.5pt;
        }
        td.date-cell {
            white-space: nowrap;
            font-size: 8.5pt;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            font-size: 8pt;
            padding: 8px 5px;
        }
        tbody tr {
            background-color: #fff;
        }
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-danger {
            color: #d32f2f;
            font-weight: bold;
        }
        .text-success {
            color: #388e3c;
            font-weight: bold;
        }
        .summary-row {
            background-color: #e3f2fd !important;
            font-weight: bold;
            color: #1976d2;
        }
        .final-balance-row {
            background-color: #fff3cd !important;
            font-weight: bold;
        }
        .final-balance-amount {
            color: #d32f2f;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo e(($mode ?? 'combined') === 'detailed' ? 'كشف حساب تفصيلي' : 'كشف حساب مجمّع'); ?></h1>
        <div class="company-name">الاسم: <?php echo e($company->name); ?></div>
        <div class="period">الحساب في الفترة من <?php echo e($fromDate); ?> الى <?php echo e($toDate); ?></div>
        <?php if($company->opening_balance && $company->opening_balance != 0): ?>
        <div style="font-size: 11px; color: #555; margin-top: 5px;">
            <strong>الرصيد الافتتاحي:</strong>
            <span style="color: <?php echo e($company->opening_balance >= 0 ? '#d32f2f' : '#388e3c'); ?>; font-weight: bold;">
                <?php echo e(number_format($company->opening_balance, 2)); ?>

            </span>
        </div>
        <?php endif; ?>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th class="date-cell" style="width: 9%;">التاريخ</th>
                <th class="num" style="width: 11%;">الرصيد</th>
                <th style="width: 10%;">رقم الفاتورة</th>
                <th style="width: 10%;">نوع العملية</th>
                <th class="num" style="width: 8%;">خصم على الفاتورة</th>
                <th class="num" style="width: 8%;">الضريبة</th>
                <th style="width: 10%;">بيان ملحق</th>
                <th class="num" style="width: 9%;">فاتورة النقل</th>
                <th class="num" style="width: 9%;">القيمة الاجمالية</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $transactions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $date = $transaction['date'] instanceof \Carbon\Carbon ? $transaction['date'] : \Carbon\Carbon::parse($transaction['date']);
                    $invNo = trim((string) ($transaction['invoice_number'] ?? ''));
                    $bookNo = trim((string) ($transaction['booking_number'] ?? ''));
                    $displayRef = $invNo !== '' ? $invNo : ($bookNo !== '' ? $bookNo : '-');
                    $rb = (float) ($transaction['running_balance'] ?? 0);
                ?>
                <tr>
                    <td class="date-cell"><?php echo e($date->format('Y-m-d')); ?></td>
                    <td class="num">
                        <?php if($rb >= 0): ?>
                            <span class="text-danger">مدين <?php echo e(number_format(abs($rb), 2)); ?></span>
                        <?php else: ?>
                            <span class="text-success">دائن <?php echo e(number_format(abs($rb), 2)); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-wrap"><?php echo e($displayRef); ?></td>
                    <td class="text-wrap"><?php echo e($transaction['type_label']); ?></td>
                    <td class="num"><?php echo e(($transaction['discount'] ?? 0) > 0 ? number_format($transaction['discount'], 2) : '-'); ?></td>
                    <td class="num"><?php echo e(($transaction['tax'] ?? 0) > 0 ? number_format($transaction['tax'], 2) : '-'); ?></td>
                    <td class="text-wrap"><?php echo e($transaction['attachment_statement'] ?: '-'); ?></td>
                    <td class="num"><?php echo e(($transaction['transportation'] ?? 0) > 0 ? number_format($transaction['transportation'], 2) : '-'); ?></td>
                    <td class="num"><?php echo e(($transaction['total'] ?? 0) > 0 ? number_format($transaction['total'], 2) : '-'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">لا توجد حركات في هذه الفترة</td>
                </tr>
            <?php endif; ?>

            <?php if(isset($transactions) && $transactions->count() > 0): ?>
                <tr class="summary-row">
                    <td colspan="4" style="text-align: right; padding-right: 10px; font-size: 9pt;">ملخص الفترة</td>
                    <td colspan="2" style="text-align: center; font-size: 8.5pt;">
                        فواتير: <?php echo e(number_format($totalInvoices ?? 0, 2)); ?> — سداد: <?php echo e(number_format($totalPayments ?? 0, 2)); ?>

                    </td>
                    <td colspan="3" style="text-align: center; font-size: 8.5pt;">
                        الرصيد المرحّل: <?php echo e(number_format($carriedForwardBalance ?? 0, 2)); ?>

                    </td>
                </tr>
                <tr class="final-balance-row">
                    <td colspan="7" style="text-align: right; padding-right: 10px; font-size: 9.5pt;">الرصيد النهائي المستحق (يوم <?php echo e($toDate); ?>)</td>
                    <td colspan="2" class="num final-balance-amount" style="text-align: center; font-size: 10pt;">
                        <?php echo e(number_format(abs($finalBalance ?? 0), 2)); ?>

                        <span style="display: inline-block; margin-right: 6px; font-weight: bold; <?php echo e(($finalBalance ?? 0) >= 0 ? 'color: #d32f2f;' : 'color: #388e3c;'); ?>"><?php echo e(($finalBalance ?? 0) >= 0 ? 'مدين' : 'دائن'); ?></span>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/accounts/statement-pdf.blade.php ENDPATH**/ ?>