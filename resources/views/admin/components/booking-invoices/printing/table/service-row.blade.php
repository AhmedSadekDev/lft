@php
    $fullName = $booking_service->full_name ?? '';
    $serviceName = $booking_service->service->name ?? '';
    $categoryTitle = $booking_service->service->serviceCategory->title ?? '';

    // تحديد نوع التسمية بناءً على محتوى الاسم
    $label = 'الخدمة:';
    $displayValue = $fullName;

    // التحقق من وجود "ايصالات" أو "إيصالات"
    $hasReceipts = stripos($fullName, 'ايصالات') !== false || stripos($fullName, 'إيصالات') !== false;

    if ($hasReceipts) {
        // تقسيم الاسم عند كلمة "ايصالات" أو "إيصالات"
        $parts = preg_split('/(ايصالات|إيصالات)/i', $fullName, 2, PREG_SPLIT_DELIM_CAPTURE);
        if (count($parts) >= 2) {
            $label = trim($parts[0]) . ':';
            $displayValue = 'ايصالات';
            // إذا كان هناك جزء بعد "ايصالات"، نضيفه
            if (isset($parts[2]) && !empty(trim($parts[2]))) {
                $displayValue .= ' ' . trim($parts[2]);
            }
        }
    } else {
        // التحقق من وجود "مصاريف أخرى" أو "مصاريف اخري"
        $hasOtherExpenses = stripos($fullName, 'مصاريف أخرى') !== false || stripos($fullName, 'مصاريف اخري') !== false || stripos($fullName, 'مصاريف اخرى') !== false;
        // التحقق من وجود "بيانه" أو "بيانات"
        $hasData = stripos($fullName, 'بيانه') !== false || stripos($fullName, 'بيانات') !== false;

        if ($hasOtherExpenses) {
            $label = 'مصاريف أخرى:';
            if ($hasData) {
                // استخراج "بيانه" أو "بيانات" من الاسم
                if (stripos($fullName, 'بيانات') !== false) {
                    $displayValue = 'بيانات';
                } elseif (stripos($fullName, 'بيانه') !== false) {
                    $displayValue = 'بيانه';
                } else {
                    // إذا لم نجد "بيانات" أو "بيانه"، نعرض باقي الاسم بعد إزالة "مصاريف أخرى"
                    $displayValue = preg_replace('/مصاريف\s+(أخرى|اخري|اخرى)/i', '', $fullName);
                    $displayValue = trim($displayValue);
                }
            } else {
                // إذا كان يحتوي فقط على "مصاريف أخرى" بدون "بيانات"
                $displayValue = preg_replace('/مصاريف\s+(أخرى|اخري|اخرى)/i', '', $fullName);
                $displayValue = trim($displayValue);
                if (empty($displayValue)) {
                    $displayValue = $fullName;
                }
            }
        } elseif ($hasData) {
            $label = 'بيانه:';
            $displayValue = preg_replace('/بيانات?/i', '', $fullName);
            $displayValue = trim($displayValue);
            if (empty($displayValue)) {
                $displayValue = $fullName;
            }
        }
    }
@endphp
<tr style="border-bottom: 1px solid #e9ecef;">
    <td style="padding: 12px; text-align: center; border-right: 1px solid #e9ecef; font-weight: 700; color: #495057; background: #f8f9fa; vertical-align: top;">{{ $key }}</td>
    <td style="padding: 12px; text-align: start; border-right: 1px solid #e9ecef; vertical-align: top;">
        <div style="display: flex; flex-direction: column; gap: 6px;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-weight: 700; color: #6c757d; font-size: 11px; min-width: 70px;">🔧 {{ $label }}</span>
                <span style="color: #212529; font-size: 12px; font-weight: 600;">{{ $displayValue }}</span>
            </div>
            @if($booking_service->note)
            <div style="display: flex; align-items: start; gap: 6px; background: #fff3cd; padding: 6px 8px; border-radius: 4px; border-right: 3px solid #ffc107;">
                <span style="font-weight: 700; color: #856404; font-size: 11px; min-width: 70px;">📝 ملاحظات:</span>
                <span style="color: #856404; font-size: 11px;">{{ $booking_service->note }}</span>
            </div>
            @endif
        </div>
    </td>
    <td style="padding: 12px; text-align: center; font-weight: 700; color: #28a745; font-size: 14px; background: #f8f9fa; vertical-align: top;">{{ number_format($booking_service->price ?? 0, 2) }} ج.م</td>
</tr>
