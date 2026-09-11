<div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 20px; border-radius: 8px; margin-top: 20px; border: 2px solid #dee2e6; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
    <h3 style="font-family: 'Cairo', sans-serif; font-size: 18px; font-weight: 700; color: #212529; margin: 0 0 20px 0; text-align: center; padding-bottom: 10px; border-bottom: 2px solid #dc3545;">ملخص الملحقات</h3>

    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
        <div style="background: #fff; padding: 12px 15px; border-radius: 6px; border-right: 3px solid #ffc107;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #495057; font-size: 13px;">الخصم ({{$invoice->discount ?? 0}}%):</span>
                <span style="font-weight: 700; color: #dc3545; font-size: 14px;">{{ number_format($invoice->discount_amount ?? 0, 2) }} ج.م</span>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); padding: 18px 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #fff; font-size: 18px;">المطلوب سداده:</span>
                <span style="font-weight: 700; color: #fff; font-size: 22px; background: rgba(255, 255, 255, 0.2); padding: 8px 20px; border-radius: 6px;">{{ number_format($invoice->invoice_total_after_discount ?? 0, 2) }} ج.م</span>
            </div>
        </div>
    </div>
</div>
