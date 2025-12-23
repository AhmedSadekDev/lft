<div style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); padding: 18px 20px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2); -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; display: block !important; visibility: visible !important; page-break-inside: avoid;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 50px; height: 50px; background: rgba(255, 255, 255, 0.2); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #fff; font-weight: 700;">L</div>
            <h2 style="font-family: 'Cairo', sans-serif; color: #fff; margin: 0; font-size: 22px; font-weight: 700;">{{ $document_title ?? '' }}</h2>
        </div>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="color: #fff; font-weight: 600; font-size: 13px;">فاتورة رقم:</span>
                <span style="color: #fff; font-size: 14px; background: rgba(255, 255, 255, 0.25); padding: 5px 14px; border-radius: 5px; font-weight: 700;">{{ $invoice->invoice_number ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="color: #fff; font-weight: 600; font-size: 13px;">التاريخ:</span>
                <span style="color: #fff; font-size: 13px;">{{ $invoice->created_at ?? "" }}</span>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
    <div style="background: #f8f9fa; padding: 12px 15px; border-radius: 8px; border-right: 4px solid #007bff;">
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-weight: 700; color: #495057; font-size: 12px; min-width: 90px;">🏢 اسم الشركة:</span>
                <span style="color: #212529; font-size: 13px; font-weight: 600;">{{ $invoice->booking->company->name ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-weight: 700; color: #495057; font-size: 12px; min-width: 90px;">👤 عناية:</span>
                <span style="color: #212529; font-size: 13px;">{{ $invoice->booking->employee?->name ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-weight: 700; color: #495057; font-size: 12px; min-width: 90px;">🚢 الخط الملاحي:</span>
                <span style="color: #212529; font-size: 13px;">{{ $invoice->booking->shippingAgent->title ?? "" }}</span>
            </div>
        </div>
    </div>

    <div style="background: #fff; padding: 12px 15px; border-radius: 8px; border: 1px solid #dee2e6; border-right: 4px solid #28a745;">
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-weight: 700; color: #495057; font-size: 12px; min-width: 90px;">📋 رقم الحجز:</span>
                <span style="color: #007bff; font-size: 13px; font-weight: 700;">{{ $invoice->booking->booking_number ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-weight: 700; color: #495057; font-size: 12px; min-width: 90px;">📄 رقم الشهادة:</span>
                <span style="color: #212529; font-size: 13px;">{{ $invoice->booking->certificate_number ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-weight: 700; color: #495057; font-size: 12px; min-width: 90px;">🔢 الرقم الضريبي:</span>
                <span style="color: #212529; font-size: 13px;">{{ $invoice->booking->company->tax_no ?? "" }}</span>
            </div>
        </div>
    </div>
</div>
