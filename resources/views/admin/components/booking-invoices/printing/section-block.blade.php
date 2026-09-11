@php
    $isBw = !empty($bw);
    $border = $isBw ? '#000' : ($border_color ?? '#DC143C');
    $titleBg = $isBw ? '#e0e0e0' : ($title_bg ?? 'linear-gradient(135deg, #DC143C 0%, #B22222 100%)');
    $titleColor = $isBw ? '#000' : '#fff';
    $tableTitle = $table_title ?? ($group['label'] ?? 'التفاصيل');
    $hideTitle = !empty($hide_title);
    $rowContext = $row_context ?? ($group['section'] ?? null);
@endphp
<div class="invoice-section-block" style="margin-bottom: 16px; padding: 10px; border: 2px solid {{ $border }}; border-radius: 8px; page-break-inside: avoid;">
    @if(!$hideTitle)
        <div style="background: {{ $titleBg }}; color: {{ $titleColor }}; padding: 8px 12px; border-radius: 6px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
            <strong style="font-family: 'Cairo', sans-serif; font-size: 14px;">{{ $section_title ?? ($group['label'] ?? '') }}</strong>
            <span style="font-family: 'Cairo', sans-serif; font-size: 12px; font-weight: 700;">{{ $group['number'] ?? '' }}</span>
        </div>
    @else
        <div style="margin-bottom: 8px; text-align: left; direction: ltr;">
            <span style="font-family: 'Cairo', sans-serif; font-size: 12px; font-weight: 700; color: #333;">{{ $group['number'] ?? '' }}</span>
        </div>
    @endif

    @include('admin.components.booking-invoices.printing.table.layout', [
        'items' => $group['items'] ?? collect(),
        'is_attachments' => ($group['section'] ?? '') !== 'tax',
        'empty_message' => ($group['items'] ?? collect())->isEmpty(),
        'table_title_override' => $tableTitle,
        'bw' => $isBw,
        'row_context' => $rowContext,
    ])

    @if(($group['items'] ?? collect())->isNotEmpty() || ($group['section'] ?? '') === 'tax')
        @include('admin.components.booking-invoices.printing.section-totals', [
            'group' => $group,
            'show_vat' => ($group['section'] ?? '') === 'tax',
            'border_color' => $border,
            'accent_color' => $isBw ? '#333' : ($border_color ?? '#DC143C'),
            'total_label' => ($group['section'] ?? '') === 'tax' ? 'إجمالي الفاتورة' : 'الإجمالي الفرعي',
        ])
    @endif
</div>
