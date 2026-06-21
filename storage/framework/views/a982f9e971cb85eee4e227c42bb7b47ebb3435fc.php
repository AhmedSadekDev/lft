<tr style="border-bottom: 1px solid #e9ecef;">
    <td style="padding: 4px 6px; text-align: center; border-right: 1px solid #e9ecef; font-weight: 700; color: #495057; background: #f8f9fa; vertical-align: top; font-size: 11px;"><?php echo e($key); ?></td>
    <td style="padding: 4px 6px; text-align: start; border-right: 1px solid #e9ecef; vertical-align: top;">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px;">
            <div style="display: flex; align-items: center; gap: 3px;">
                <span style="font-weight: 700; color: #6c757d; font-size: 9px; min-width: 70px;">🔧 ايصالات:</span>
                <span style="color: #212529; font-size: 10px; font-weight: 600;"><?php echo e($groupedService->group_key); ?></span>
            </div>
            <?php if(count($groupedService->notes) > 0): ?>
            <div style="display: flex; align-items: start; gap: 3px; background: #fff3cd; padding: 2px 4px; border-radius: 3px; border-right: 2px solid #ffc107;">
                <span style="font-weight: 700; color: #856404; font-size: 9px; min-width: 70px;">📝 ملاحظات:</span>
                <span style="color: #856404; font-size: 9px;"><?php echo e(implode(' - ', $groupedService->notes)); ?></span>
            </div>
            <?php else: ?>
            <div></div>
            <?php endif; ?>
        </div>
    </td>
    <td style="padding: 4px 6px; text-align: center; font-weight: 700; color: #28a745; font-size: 12px; background: #f8f9fa; vertical-align: top;"><?php echo e(number_format($groupedService->total_price ?? 0, 2)); ?> ج.م</td>
</tr>

<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/components/booking-invoices/printing/table/grouped-receipt-row.blade.php ENDPATH**/ ?>