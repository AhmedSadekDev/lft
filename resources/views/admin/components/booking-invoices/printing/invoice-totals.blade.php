<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 15px; border-radius: 8px; margin-top: 15px; border: 2px solid #dee2e6; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
    <h3 style="font-family: 'Cairo', sans-serif; font-size: 16px; font-weight: 700; color: #212529; margin: 0 0 12px 0; text-align: center; padding-bottom: 8px; border-bottom: 2px solid #007bff;">ملخص الفاتورة</h3>

    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 12px;">
        <div style="background: #fff; padding: 10px 12px; border-radius: 6px; border-right: 3px solid #17a2b8;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 12px;">إجمالي الفاتورة قبل الضريبة:</span>
                <span style="font-weight: 700; color: #212529; font-size: 13px;">{{ number_format($invoice->invoice_total_before_tax ?? 0, 2) }} ج.م</span>
            </div>
        </div>

        <div style="background: #fff; padding: 10px 12px; border-radius: 6px; border-right: 3px solid #ffc107;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 12px;">الخصم:</span>
                <span style="font-weight: 700; color: #dc3545; font-size: 13px;">{{ number_format($invoice->discount_amount ?? 0, 2) }} ج.م</span>
            </div>
        </div>

        <div style="background: #fff; padding: 10px 12px; border-radius: 6px; border-right: 3px solid #28a745;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 12px;">ضريبة القيمة المضافة ({{$invoice->value_added_tax ?? 0}}%):</span>
                <span style="font-weight: 700; color: #28a745; font-size: 13px;">{{ number_format($invoice->value_added_tax_amount ?? 0, 2) }} ج.م</span>
            </div>
        </div>

        <div style="background: #fff; padding: 10px 12px; border-radius: 6px; border-right: 3px solid #6f42c1;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 12px;">ضريبة عامة ({{$invoice->sales_tax ?? 0}}%):</span>
                <span style="font-weight: 700; color: #6f42c1; font-size: 13px;">{{ number_format($invoice->sales_tax_amount ?? 0, 2) }} ج.م</span>
            </div>
        </div>
    </div>

    <div style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); padding: 14px 18px; border-radius: 8px; margin-top: 8px; box-shadow: 0 4px 12px rgba(0, 123, 255, 0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 700; color: #fff; font-size: 16px;">إجمالي الفاتورة بعد الضريبة:</span>
            <span style="font-weight: 700; color: #fff; font-size: 20px; background: rgba(255, 255, 255, 0.2); padding: 6px 18px; border-radius: 6px;">{{ number_format($invoice->invoice_total_after_tax ?? 0, 2) }} ج.م</span>
        </div>
    </div>
</div>
