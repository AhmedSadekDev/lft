<div style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); padding: 8px 12px; border-radius: 6px; margin-bottom: 6px; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2); -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; display: block !important; visibility: visible !important; page-break-inside: avoid;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 32px; height: 32px; background: rgba(255, 255, 255, 0.2); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #fff; font-weight: 700;">L</div>
            <h2 style="font-family: 'Cairo', sans-serif; color: #fff; margin: 0; font-size: 16px; font-weight: 700;">{{ str_replace('فواتير', 'فاتوره', $document_title ?? '') }}</h2>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="color: #fff; font-weight: 600; font-size: 11px;">فاتورة رقم:</span>
                <span style="color: #fff; font-size: 12px; background: rgba(255, 255, 255, 0.25); padding: 3px 10px; border-radius: 4px; font-weight: 700;">{{ $invoice->invoice_number ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="color: #fff; font-weight: 600; font-size: 11px;">التاريخ:</span>
                <span style="color: #fff; font-size: 11px;">{{ $invoice->created_at ?? "" }}</span>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 6px;">
    <div style="background: #f8f9fa; padding: 6px 10px; border-radius: 6px; border-right: 3px solid #007bff;">
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">🏢 اسم الشركة:</span>
                <span style="color: #212529; font-size: 11px; font-weight: 600;">{{ $invoice->booking->company->name ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">🔢 الرقم الضريبي:</span>
                <span style="color: #212529; font-size: 11px;">{{ $invoice->booking->company->tax_no ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">👤 عناية:</span>
                <span style="color: #212529; font-size: 11px;">{{ $invoice->booking->employee?->name ?? "" }}</span>
            </div>
        </div>
    </div>

    <div style="background: #fff; padding: 6px 10px; border-radius: 6px; border: 1px solid #dee2e6; border-right: 3px solid #28a745;">
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">📋 رقم الحجز:</span>
                <span style="color: #007bff; font-size: 11px; font-weight: 700;">{{ $invoice->booking->booking_number ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">📄 رقم الشهادة:</span>
                <span style="color: #212529; font-size: 11px;">{{ $invoice->booking->certificate_number ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">🚢 الخط الملاحي:</span>
                <span style="color: #212529; font-size: 11px;">{{ $invoice->booking->shippingAgent->title ?? "" }}</span>
            </div>
        </div>
    </div>
</div>
