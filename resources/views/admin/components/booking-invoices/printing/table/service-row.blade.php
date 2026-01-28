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
            // "ايصالات:" تكون label، والجزء الآخر يكون displayValue
            $label = 'ايصالات:';
            $displayValue = '';

            // الجزء قبل "ايصالات" (مثل "تخصيص" أو "هيئة الميناء")
            $beforePart = trim($parts[0]);
            // الجزء بعد "ايصالات" (إن وجد)
            $afterPart = isset($parts[2]) ? trim($parts[2]) : '';

            // نستخدم الجزء قبل "ايصالات" كـ displayValue
            if (!empty($beforePart)) {
                $displayValue = $beforePart;
            } elseif (!empty($afterPart)) {
                // إذا لم يكن هناك جزء قبل، نستخدم الجزء بعد
                $displayValue = $afterPart;
            } else {
                // إذا لم يكن هناك أي جزء، نعرض الاسم الكامل بدون "ايصالات"
                $displayValue = preg_replace('/(ايصالات|إيصالات)/i', '', $fullName);
                $displayValue = trim($displayValue);
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
    <td style="padding: 4px 6px; text-align: center; border-right: 1px solid #e9ecef; font-weight: 700; color: #495057; background: #f8f9fa; vertical-align: top; font-size: 11px;">{{ $key }}</td>
    <td style="padding: 4px 6px; text-align: start; border-right: 1px solid #e9ecef; vertical-align: top;">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px;">
            <div style="display: flex; align-items: center; gap: 3px;">
                <span style="font-weight: 700; color: #6c757d; font-size: 9px; min-width: 70px;">🔧 {{ $label }}</span>
                <span style="color: #212529; font-size: 10px; font-weight: 600;">{{ $displayValue }}</span>
            </div>
            @if($booking_service->note)
            <div style="display: flex; align-items: start; gap: 3px; background: #fff3cd; padding: 2px 4px; border-radius: 3px; border-right: 2px solid #ffc107;">
                <span style="font-weight: 700; color: #856404; font-size: 9px; min-width: 70px;">📝 ملاحظات:</span>
                <span style="color: #856404; font-size: 9px;">{{ $booking_service->note }}</span>
            </div>
            @else
            <div></div>
            @endif
        </div>
        @if($booking_service->image && $booking_service->getRawOriginal('image'))
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; margin-top: 2px;">
            <div style="display: flex; align-items: start; gap: 3px; background: #ffe6e6; padding: 2px 4px; border-radius: 3px; border-right: 2px solid #DC143C;">
                <span style="font-weight: 700; color: #8B0000; font-size: 9px; min-width: 70px;">🖼️ الإيصال:</span>
                <a href="{{ $booking_service->image }}" target="_blank" style="color: #DC143C; font-size: 9px; text-decoration: underline;">عرض الإيصال</a>
            </div>
            <div></div>
        </div>
        @endif
    </td>
    <td style="padding: 4px 6px; text-align: center; font-weight: 700; color: #28a745; font-size: 12px; background: #f8f9fa; vertical-align: top;">{{ number_format($booking_service->price ?? 0, 2) }} ج.م</td>
</tr>
