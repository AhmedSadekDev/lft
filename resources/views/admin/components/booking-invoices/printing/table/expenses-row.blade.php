<table style="display: table;width: 100%;margin-top: 0.5rem;border-spacing: 0;border: 1px solid #000;">
    <thead
        style="background-color: #000;color:#fff;font-family: 'Cairo', sans-serif;font-size: .8rem;vertical-align: middle;">
        <tr>
            <th style="padding: 0.5rem;text-align: start">م</th>
            <th style="padding: 0.5rem;text-align: start">تفاصيل الفاتورة</th>
            <th style="padding: 0.5rem;text-align: start">التكلفة</th>
        </tr>
    </thead>
    <tbody style="font-family: 'Cairo', sans-serif;font-size: .8rem;text-align: center;vertical-align: middle;">
        @foreach ($booking->expenses as $expenses)
            <tr>
                <td style="border-top: 2px solid #f5f5f5;">
                    {{ $loop->iteration + $booking->containers->count() + $booking->bookingServices->count() }}</td>
                <td style="border-top: 2px solid #f5f5f5;">
                    <div class="detalis_container"
                        style="display: flex;justify-content: start;flex-wrap: wrap;text-align: center;padding: 0.25rem 0.5rem;">
                        <div class="info" style="display: flex;width: 100%;justify-content: start;margin-bottom: 0;">
                            <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">الخدمة : </p>
                            <p class="text" style="margin-bottom: 0;margin-top: 0">
                                {{ $expenses->service->name . ' ' . $expenses->service->serviceCategory->title }}</p>
                        </div>
                        <div class="info" style="display: flex;width: 100%;justify-content: start;margin-bottom: 0;">
                            <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">ملاحظات : </p>
                            <p class="text" style="margin-bottom: 0;margin-top: 0">{{ $expenses->service->notes }}</p>
                        </div>
                    </div>
                </td>
                <td style="border-top: 2px solid #f5f5f5;">{{ $expenses->value }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
