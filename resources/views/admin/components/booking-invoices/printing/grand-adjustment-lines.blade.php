@php
    $commercialDiscount = (float) ($commercialDiscount ?? 0);
    $commercialAddition = (float) ($commercialAddition ?? 0);
    $combinedTotal = (float) ($combinedTotal ?? 0);
    $symbol = $symbol ?? 'ج.م';
    $adjustmentReason = $adjustmentReason ?? null;
@endphp
@if($commercialDiscount > 0 || $commercialAddition > 0)
    @if($commercialDiscount > 0)
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
            <span style="font-weight: 700; font-size: 13px;">خصم تجاري (على إجمالي الفاتورة)</span>
            <span style="font-weight: 700; font-size: 15px;">− {{ number_format($commercialDiscount, 2) }} {{ $symbol }}</span>
        </div>
    @endif
    @if($commercialAddition > 0)
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
            <span style="font-weight: 700; font-size: 13px;">إضافة (على إجمالي الفاتورة)</span>
            <span style="font-weight: 700; font-size: 15px;">+ {{ number_format($commercialAddition, 2) }} {{ $symbol }}</span>
        </div>
    @endif
    @if(!empty($adjustmentReason))
        <div style="margin-top: 4px; font-size: 11px; color: #495057;">السبب: {{ $adjustmentReason }}</div>
    @endif
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px; border-top: 1px solid #000; padding-top: 6px;">
        <span style="font-weight: 700; font-size: 13px;">الإجمالي النهائي بعد التسوية</span>
        <span style="font-weight: 700; font-size: 15px;">{{ number_format($combinedTotal, 2) }} {{ $symbol }}</span>
    </div>
@endif
