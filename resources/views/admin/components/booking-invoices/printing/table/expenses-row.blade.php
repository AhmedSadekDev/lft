<table style="display: table; width: 100%; margin-top: 0.5rem; border-spacing: 0; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
    <thead style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: #fff; font-family: 'Cairo', sans-serif; font-size: 0.9rem; vertical-align: middle;">
        <tr>
            <th style="padding: 12px 15px; text-align: center; font-weight: 700; border-right: 1px solid rgba(255, 255, 255, 0.1); width: 60px;">م</th>
            <th style="padding: 12px 15px; text-align: start; font-weight: 700; border-right: 1px solid rgba(255, 255, 255, 0.1);">تفاصيل المصاريف</th>
            <th style="padding: 12px 15px; text-align: center; font-weight: 700; width: 120px;">التكلفة</th>
        </tr>
    </thead>
    <tbody style="font-family: 'Cairo', sans-serif; font-size: 0.85rem; text-align: center; vertical-align: middle;">
        @foreach ($booking->expenses as $expenses)
            @php
                $fullName = ($expenses->service->name ?? '') . ' ' . ($expenses->service->serviceCategory?->title ?? '');
                $fullName = trim($fullName);

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
                <td style="padding: 15px; text-align: center; border-right: 1px solid #e9ecef; font-weight: 700; color: #495057; background: #f8f9fa;">
                    {{ $loop->iteration + $booking->containers->count() + $booking->bookingServices->count() }}
                </td>
                <td style="padding: 15px; text-align: start; border-right: 1px solid #e9ecef;">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-weight: 700; color: #6c757d; font-size: 12px; min-width: 70px;">💰 {{ $label }}</span>
                            <span style="color: #212529; font-size: 13px; font-weight: 600;">
                                {{ $displayValue }}
                            </span>
                        </div>
                        @if($expenses->service->notes)
                        <div style="display: flex; align-items: start; gap: 8px; background: #fff3cd; padding: 8px; border-radius: 4px; border-right: 3px solid #ffc107;">
                            <span style="font-weight: 700; color: #856404; font-size: 12px; min-width: 70px;">📝 ملاحظات:</span>
                            <span style="color: #856404; font-size: 12px;">{{ $expenses->service->notes }}</span>
                        </div>
                        @endif
                    </div>
                </td>
                <td style="padding: 15px; text-align: center; font-weight: 700; color: #dc3545; font-size: 15px; background: #f8f9fa;">
                    {{ number_format($expenses->value ?? 0, 2) }} ج.م
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
