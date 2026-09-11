<tr style="border-bottom: 1px solid #e9ecef;">
    <td style="padding: 4px 6px; text-align: center; border-right: 1px solid #e9ecef; font-weight: 700; color: #495057; background: #f8f9fa; vertical-align: top; font-size: 11px;"><?php echo e($key); ?></td>
    <td style="padding: 4px 6px; text-align: start; border-right: 1px solid #e9ecef; vertical-align: top;">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px;">
            <div style="display: flex; align-items: center; gap: 3px;">
                <span style="font-weight: 700; color: #6c757d; font-size: 9px; min-width: 60px;">📦 رقم الحاوية:</span>
                <span style="color: #212529; font-size: 10px; font-weight: 600;"><?php echo e($booking_container->container_no ?? ''); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 3px;">
                <span style="font-weight: 700; color: #6c757d; font-size: 9px; min-width: 60px;">📏 مقاس ونوع:</span>
                <span style="color: #212529; font-size: 10px;"><?php echo e($booking_container->container->full_name ?? ''); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 3px;">
                <span style="font-weight: 700; color: #6c757d; font-size: 9px; min-width: 60px;">📅 تاريخ:</span>
                <span style="color: #212529; font-size: 10px;"><?php echo e($booking_container->arrival_date ?? ''); ?></span>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; margin-top: 2px;">
            <div style="display: flex; align-items: center; gap: 3px;">
                <span style="font-weight: 700; color: #6c757d; font-size: 9px; min-width: 60px;">🚚 خروج:</span>
                <span style="color: #212529; font-size: 10px;"><?php echo e($booking_container->departure?->title ?? 'N/A'); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 3px;">
                <span style="font-weight: 700; color: #6c757d; font-size: 9px; min-width: 60px;">📍 وجهة:</span>
                <span style="color: #212529; font-size: 10px;"><?php echo e($booking_container->loading?->title ?? 'N/A'); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 3px;">
                <span style="font-weight: 700; color: #6c757d; font-size: 9px; min-width: 60px;">⏱️ تعتيق:</span>
                <span style="color: #212529; font-size: 10px;"><?php echo e($booking_container->aging?->title ?? 'N/A'); ?></span>
            </div>
        </div>
    </td>
    <td style="padding: 4px 6px; text-align: center; font-weight: 700; color: #28a745; font-size: 12px; background: #f8f9fa; vertical-align: top;"><?php echo e(number_format($booking_container->price ?? 0, 2)); ?> ج.م</td>
</tr>
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/components/booking-invoices/printing/table/container-row.blade.php ENDPATH**/ ?>