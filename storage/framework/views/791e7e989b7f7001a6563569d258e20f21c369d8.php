<?php
    $isBw = !empty($bw);
    $border = $isBw ? '#000' : ($border_color ?? '#DC143C');
    $titleBg = $isBw ? '#e0e0e0' : ($title_bg ?? 'linear-gradient(135deg, #DC143C 0%, #B22222 100%)');
    $titleColor = $isBw ? '#000' : '#fff';
    $tableTitle = $table_title ?? ($group['label'] ?? 'التفاصيل');
    $hideTitle = !empty($hide_title);
    $rowContext = $row_context ?? ($group['section'] ?? null);
?>
<div class="invoice-section-block" style="margin-bottom: 16px; padding: 10px; border: 2px solid <?php echo e($border); ?>; border-radius: 8px; page-break-inside: avoid;">
    <?php if(!$hideTitle): ?>
        <div style="background: <?php echo e($titleBg); ?>; color: <?php echo e($titleColor); ?>; padding: 8px 12px; border-radius: 6px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
            <strong style="font-family: 'Cairo', sans-serif; font-size: 14px;"><?php echo e($section_title ?? ($group['label'] ?? '')); ?></strong>
            <span style="font-family: 'Cairo', sans-serif; font-size: 12px; font-weight: 700;"><?php echo e($group['number'] ?? ''); ?></span>
        </div>
    <?php else: ?>
        <div style="margin-bottom: 8px; text-align: left; direction: ltr;">
            <span style="font-family: 'Cairo', sans-serif; font-size: 12px; font-weight: 700; color: #333;"><?php echo e($group['number'] ?? ''); ?></span>
        </div>
    <?php endif; ?>

    <?php echo $__env->make('admin.components.booking-invoices.printing.table.layout', [
        'items' => $group['items'] ?? collect(),
        'is_attachments' => ($group['section'] ?? '') !== 'tax',
        'empty_message' => ($group['items'] ?? collect())->isEmpty(),
        'table_title_override' => $tableTitle,
        'bw' => $isBw,
        'row_context' => $rowContext,
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php if(($group['items'] ?? collect())->isNotEmpty() || ($group['section'] ?? '') === 'tax'): ?>
        <?php echo $__env->make('admin.components.booking-invoices.printing.section-totals', [
            'group' => $group,
            'show_vat' => ($group['section'] ?? '') === 'tax',
            'border_color' => $border,
            'accent_color' => $isBw ? '#333' : ($border_color ?? '#DC143C'),
            'total_label' => ($group['section'] ?? '') === 'tax' ? 'إجمالي الفاتورة' : 'الإجمالي الفرعي',
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>
</div>
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/components/booking-invoices/printing/section-block.blade.php ENDPATH**/ ?>