@php
    $taxTotal = (float) ($taxGroup['total'] ?? $invoice->invoice_total_after_discount ?? 0);
    $receiptTotal = (float) ($receiptGroup['total'] ?? 0);
    $additionalTotal = (float) ($additionalGroup['total'] ?? 0);
    $grand = $taxTotal + $receiptTotal + $additionalTotal;
@endphp
<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 8px; border-radius: 6px; margin-top: 6px; border: 2px solid #dee2e6;">
    <h3 style="font-family: 'Cairo', sans-serif; font-size: 12px; font-weight: 700; color: #212529; margin: 0 0 6px 0; text-align: center; padding-bottom: 4px; border-bottom: 2px solid #dc3545;">ملخص الأقسام</h3>

    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; margin-bottom: 4px;">
        <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid #DC143C;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 9px;">فاتورة ضريبية (I):</span>
                <span style="font-weight: 700; color: #212529; font-size: 10px;">{{ number_format($taxTotal, 2) }} ج.م</span>
            </div>
        </div>
        <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid #0d6efd;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 9px;">إيصالات (R):</span>
                <span style="font-weight: 700; color: #0d6efd; font-size: 10px;">{{ number_format($receiptTotal, 2) }} ج.م</span>
            </div>
        </div>
        <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid #198754;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 9px;">خدمات إضافية (S):</span>
                <span style="font-weight: 700; color: #198754; font-size: 10px;">{{ number_format($additionalTotal, 2) }} ج.م</span>
            </div>
        </div>
    </div>

    <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); padding: 8px 12px; border-radius: 6px; margin-top: 4px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 700; color: #fff; font-size: 12px;">الإجمالي العام:</span>
            <span style="font-weight: 700; color: #fff; font-size: 16px;">{{ number_format($grand, 2) }} ج.م</span>
        </div>
    </div>
</div>
