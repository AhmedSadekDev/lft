<tr>
    <td style="border-top: 2px solid #f5f5f5;">{{ $key }}</td>
    <td style="border-top: 2px solid #f5f5f5;">
        <div class="detalis_container" style="display: flex;justify-content: start;flex-wrap: wrap;text-align: center;padding: 0.25rem 0.5rem;">
            <div class="info" style="display: flex;width: 100%;justify-content: start;margin-bottom: 0;">
                <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">الخدمة : </p>
                <p class="text" style="margin-bottom: 0;margin-top: 0">{{ $booking_service->full_name }}</p>
            </div>
            <div class="info" style="display: flex;width: 100%;justify-content: start;margin-bottom: 0;">
                <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">ملاحظات : </p>
                <p class="text" style="margin-bottom: 0;margin-top: 0">{{ $booking_service->note }}</p>
            </div>
        </div>
    </td>
    <td style="border-top: 2px solid #f5f5f5;">{{ $booking_service->price }}</td>
</tr>
