<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 8px; border-radius: 6px; margin-top: 6px; border: 2px solid #dee2e6; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
    <h3 style="font-family: 'Cairo', sans-serif; font-size: 12px; font-weight: 700; color: #212529; margin: 0 0 6px 0; text-align: center; padding-bottom: 4px; border-bottom: 2px solid #dc3545;">ملخص الفاتورة</h3>

    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; margin-bottom: 4px;">
        <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid #17a2b8;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 9px;">النقل:</span>
                <span style="font-weight: 700; color: #212529; font-size: 10px;">{{ number_format($invoice->transportation_total_before_vat ?? 0, 2) }} ج.م</span>
            </div>
        </div>

        <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid #ffc107;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 9px;">الخدمات الخاضعة للضريبة:</span>
                <span style="font-weight: 700; color: #212529; font-size: 10px;">{{ number_format($invoice->taxed_services_total_before_vat ?? 0, 2) }} ج.م</span>
            </div>
        </div>

        <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid #28a745;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 9px;">الخدمات غير الخاضعة للضريبة:</span>
                <span style="font-weight: 700; color: #28a745; font-size: 10px;">{{ number_format($invoice->untaxed_services_total_before_vat ?? 0, 2) }} ج.م</span>
            </div>
        </div>

        <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid #6f42c1;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 9px;">إجمالي الفاتورة قبل الضريبة:</span>
                <span style="font-weight: 700; color: #6f42c1; font-size: 10px;">{{ number_format($invoice->invoice_total_before_tax ?? 0, 2) }} ج.م</span>
            </div>
        </div>

        <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid #dc3545;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 9px;">الخصم ({{$invoice->discount ?? 0}}%):</span>
                <span style="font-weight: 700; color: #dc3545; font-size: 10px;">{{ number_format($invoice->discount_amount ?? 0, 2) }} ج.م</span>
            </div>
        </div>

        <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid #17a2b8;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 9px;">ضريبة القيمة المضافة ({{$invoice->value_added_tax ?? 0}}%):</span>
                <span style="font-weight: 700; color: #17a2b8; font-size: 10px;">{{ number_format($invoice->value_added_tax_amount ?? 0, 2) }} ج.م</span>
            </div>
        </div>

        <div style="background: #fff; padding: 4px 6px; border-radius: 4px; border-right: 2px solid #6f42c1;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 9px;">ضريبة عامة ({{$invoice->sales_tax ?? 0}}%):</span>
                <span style="font-weight: 700; color: #6f42c1; font-size: 10px;">{{ number_format($invoice->sales_tax_amount ?? 0, 2) }} ج.م</span>
            </div>
        </div>
    </div>

    <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); padding: 8px 12px; border-radius: 6px; margin-top: 4px; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 700; color: #fff; font-size: 12px;">المطلوب سداده:</span>
            <span style="font-weight: 700; color: #fff; font-size: 16px; background: rgba(255, 255, 255, 0.2); padding: 4px 12px; border-radius: 4px;">{{ number_format($invoice->invoice_total_after_tax ?? 0, 2) }} ج.م</span>
        </div>
    </div>
</div>

