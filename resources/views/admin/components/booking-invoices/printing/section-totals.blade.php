@php
    $showVat = !empty($show_vat);
    $borderColor = $border_color ?? '#DC143C';
    $accent = $accent_color ?? '#DC143C';
@endphp
<div style="background: #f8f9fa; padding: 8px; border-radius: 6px; margin-top: 6px; border: 2px solid #dee2e6;">
    <div style="display: grid; grid-template-columns: {{ $showVat ? 'repeat(2, 1fr)' : '1fr' }}; gap: 4px; margin-bottom: 4px;">
        <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid {{ $borderColor }};">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 9px;">الإجمالي الفرعي:</span>
                <span style="font-weight: 700; color: #212529; font-size: 10px;">{{ number_format($group['subtotal'] ?? 0, 2) }} ج.م</span>
            </div>
        </div>

        @if($showVat)
            @if(($group['discount'] ?? 0) > 0)
                <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid #ffc107;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; color: #495057; font-size: 9px;">الخصم:</span>
                        <span style="font-weight: 700; color: #dc3545; font-size: 10px;">{{ number_format($group['discount'] ?? 0, 2) }} ج.م</span>
                    </div>
                </div>
            @endif
            <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid #28a745;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 700; color: #495057; font-size: 9px;">ضريبة القيمة المضافة ({{ $invoice->value_added_tax ?? 0 }}%):</span>
                    <span style="font-weight: 700; color: #28a745; font-size: 10px;">{{ number_format($group['vat'] ?? 0, 2) }} ج.م</span>
                </div>
            </div>
        @endif
    </div>

    <div style="background: {{ $accent }}; padding: 8px 12px; border-radius: 6px; margin-top: 4px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 700; color: #fff; font-size: 12px;">{{ $total_label ?? 'الإجمالي' }}:</span>
            <span style="font-weight: 700; color: #fff; font-size: 14px;">{{ number_format($group['total'] ?? 0, 2) }} ج.م</span>
        </div>
    </div>
</div>
