<?php
    $tableTitle = $is_attachments ?? false ? 'الايصالات' : 'تفاصيل الفاتورة';
?>
<table style="display: table; width: 100%; margin-top: 0.2rem; border-spacing: 0; border: 1px solid #dee2e6; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);">
    <thead style="background: linear-gradient(135deg, #DC143C 0%, #B22222 100%); color: #fff; font-family: 'Cairo', sans-serif; font-size: 0.7rem; vertical-align: middle;">
        <tr>
            <th style="padding: 5px 8px; text-align: center; font-weight: 700; border-right: 1px solid rgba(255, 255, 255, 0.1); width: 50px;">م</th>
            <th style="padding: 5px 8px; text-align: start; font-weight: 700; border-right: 1px solid rgba(255, 255, 255, 0.1);"><?php echo e($tableTitle); ?></th>
            <th style="padding: 5px 8px; text-align: center; font-weight: 700; width: 110px;">التكلفة</th>
        </tr>
    </thead>
    <tbody style="font-family: 'Cairo', sans-serif; font-size: 0.7rem; text-align: center; vertical-align: middle;">
        <?php if(!empty($empty_message) && (isset($items) && count($items) === 0)): ?>
            <tr>
                <td style="padding: 10px 8px; border-bottom: 1px solid #dee2e6;">1</td>
                <td style="padding: 10px 8px; text-align: center; border-bottom: 1px solid #dee2e6; color: #6c757d;">لا يوجد إيصالات</td>
                <td style="padding: 10px 8px; border-bottom: 1px solid #dee2e6;">-</td>
            </tr>
        <?php else: ?>
        <?php $__currentLoopData = $items ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($item instanceof \App\Models\BookingContainer): ?>
                <?php echo $__env->make('admin.components.booking-invoices.printing.table.container-row', [
                    'booking_container' => $item,
                    'key' => $key + 1,
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
            <?php if($item instanceof \App\Models\BookingService): ?>
                <?php echo $__env->make('admin.components.booking-invoices.printing.table.service-row', [
                    'booking_service' => $item,
                    'key' => $key + 1,
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
            <?php if(is_object($item) && isset($item->type) && $item->type === 'grouped_receipt'): ?>
                <?php echo $__env->make('admin.components.booking-invoices.printing.table.grouped-receipt-row', [
                    'groupedService' => $item,
                    'key' => $key + 1,
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
            <?php if(is_object($item) && isset($item->type) && $item->type === 'agent_expense_attachment' && isset($item->expense)): ?>
                <?php echo $__env->make('admin.components.booking-invoices.printing.table.agent-expense-receipt-row', [
                    'expense' => $item->expense,
                    'key' => $key + 1,
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </tbody>
</table>
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/components/booking-invoices/printing/table/layout.blade.php ENDPATH**/ ?>