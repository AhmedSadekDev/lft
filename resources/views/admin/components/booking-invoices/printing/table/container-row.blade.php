<tr>
    <td style="border-top: 2px solid #f5f5f5;">{{ $key }}</td>
    <td style="border-top: 2px solid #f5f5f5;">
        <div class="detalis_container" style="display: flex;justify-content: start;flex-wrap: wrap;text-align: center;padding: 0.25rem 0.5rem;">
            <div class="info" style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">رقم الحاوية : </p>
                <p class="text" style="margin-bottom: 0;margin-top: 0">{{ $booking_container->container_no ?? '' }}</p>
            </div>
            <div class="info" style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">مقاس ونوع : </p>
                <p class="text" style="margin-bottom: 0;margin-top: 0">{{ $booking_container->container->full_name ?? '' }}</p>
            </div>
            <div class="info" style="display: flex;width: 25%;justify-content: start;">
                <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">تاريخ : </p>
                <p class="text" style="margin-bottom: 0;margin-top: 0">{{ $booking_container->arrival_date ?? '' }}</p>
            </div>
            <div class="info" style="display: flex;width: 25%;justify-content: start;">
            </div>
            <div class="info" style="display: flex;width: 25%;justify-content: start;margin-bottom: 0;">
                <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">اسم المصنع : </p>
                <p class="text" style="margin-bottom: 0;margin-top: 0">{{ $booking_container->branch?->factory->name }}</p>
            </div>
            <div class="info" style="display: flex;width: 25%;justify-content: start;">
                <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">خروج : </p>
                <p class="text" style="margin-bottom: 0;margin-top: 0">{{ $booking_container->departure?->title }}</p>
            </div>
            <div class="info" style="display: flex;width: 25%;justify-content: start;">
                <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">وجهة : </p>
                <p class="text" style="margin-bottom: 0;margin-top: 0">{{ $booking_container->loading?->title }}</p>
            </div>
            <div class="info" style="display: flex;width: 25%;justify-content: start;">
                <p class="title" style="margin-bottom: 0;margin-top: 0;font-weight: 900;">تعتيق : </p>
                <p class="text" style="margin-bottom: 0;margin-top: 0">{{ $booking_container->aging?->title }}</p>
            </div>
        </div>
    </td>
    <td style="border-top: 2px solid #f5f5f5;">{{ $booking_container->price ?? 0 }}</td>
</tr>
