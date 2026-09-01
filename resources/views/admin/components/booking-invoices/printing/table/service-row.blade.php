@php
    $fullName = $booking_service->full_name ?? '';
    $rowContext = $row_context ?? null;

    $label = 'الخدمة:';
    $displayValue = $fullName;
    $hideLabel = false;

    $normalizedName = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', mb_strtolower($fullName, 'UTF-8'));
    $normalizedName = str_replace(['ى', 'ی'], 'ي', $normalizedName);
    $hasReceipts = str_contains($normalizedName, 'ايصالات')
        || str_contains($normalizedName, 'ايصال')
        || str_contains($normalizedName, 'receipt');

    if ($hasReceipts) {
        $parts = preg_split('/(ايصالات|إيصالات)/iu', $fullName, 2, PREG_SPLIT_DELIM_CAPTURE);
        $beforePart = trim($parts[0] ?? '');
        $afterPart = trim($parts[2] ?? '');
        $desc = $beforePart !== '' ? $beforePart : ($afterPart !== '' ? $afterPart : 'عام');
        $hideLabel = true;
        $displayValue = 'إيصالات ' . $desc;
    } elseif ($rowContext === 'additional') {
        // خدمات بياتة: بدون كلمة "الخدمة"
        $hideLabel = true;
        $hasOtherExpenses = stripos($fullName, 'مصاريف أخرى') !== false
            || stripos($fullName, 'مصاريف اخري') !== false
            || stripos($fullName, 'مصاريف اخرى') !== false;
        $hasData = stripos($fullName, 'بيانه') !== false
            || stripos($fullName, 'بيانات') !== false
            || stripos($fullName, 'بياته') !== false;

        if ($hasOtherExpenses && $hasData) {
            $displayValue = 'خدمات بياتة';
        } elseif ($hasData) {
            $displayValue = 'خدمات بياتة';
        } else {
            $displayValue = preg_replace('/^الخدمة\s*:?\s*/iu', '', $fullName);
            $displayValue = trim($displayValue) !== '' ? trim($displayValue) : $fullName;
        }
    } else {
        $hasOtherExpenses = stripos($fullName, 'مصاريف أخرى') !== false
            || stripos($fullName, 'مصاريف اخري') !== false
            || stripos($fullName, 'مصاريف اخرى') !== false;
        $hasData = stripos($fullName, 'بيانه') !== false
            || stripos($fullName, 'بيانات') !== false;

        if ($hasOtherExpenses) {
            $label = 'مصاريف أخرى:';
            if ($hasData) {
                $displayValue = stripos($fullName, 'بيانات') !== false ? 'بيانات' : 'بيانه';
            } else {
                $displayValue = trim(preg_replace('/مصاريف\s+(أخرى|اخري|اخرى)/iu', '', $fullName));
                if ($displayValue === '') {
                    $displayValue = $fullName;
                }
            }
        } elseif ($hasData) {
            $label = 'بيانه:';
            $displayValue = trim(preg_replace('/بيانات?/iu', '', $fullName));
            if ($displayValue === '') {
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
                @if(!$hideLabel)
                    <span style="font-weight: 700; color: #6c757d; font-size: 9px; min-width: 70px;">{{ $label }}</span>
                @endif
                <span style="color: #212529; font-size: 11px; font-weight: 600;">{{ $displayValue }}</span>
            </div>
            @if($booking_service->note)
            <div style="display: flex; align-items: start; gap: 3px; background: #fff3cd; padding: 2px 4px; border-radius: 3px; border-right: 2px solid #ffc107;">
                <span style="font-weight: 700; color: #856404; font-size: 9px; min-width: 70px;">ملاحظات:</span>
                <span style="color: #856404; font-size: 9px;">{{ $booking_service->note }}</span>
            </div>
            @else
            <div></div>
            @endif
        </div>
        @if($booking_service->image && $booking_service->getRawOriginal('image'))
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; margin-top: 2px;">
            <div style="display: flex; align-items: start; gap: 3px; background: #ffe6e6; padding: 2px 4px; border-radius: 3px; border-right: 2px solid #DC143C;">
                <span style="font-weight: 700; color: #8B0000; font-size: 9px; min-width: 70px;">الإيصال:</span>
                <a href="{{ $booking_service->image }}" target="_blank" style="color: #DC143C; font-size: 9px; text-decoration: underline;">عرض الإيصال</a>
            </div>
            <div></div>
        </div>
        @endif
    </td>
    <td style="padding: 4px 6px; text-align: center; font-weight: 700; color: #28a745; font-size: 12px; background: #f8f9fa; vertical-align: top;">{{ number_format($booking_service->price ?? 0, 2) }} ج.م</td>
</tr>
