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
        @foreach ($items as $key => $item)
            @if ($item instanceof \App\Models\BookingContainer)
                @include('admin.components.booking-invoices.printing.table.container-row', [
                    'booking_container' => $item,
                    'key' => $key + 1,
                ])
            @endif
            @if ($item instanceof \App\Models\BookingService)
                @include('admin.components.booking-invoices.printing.table.service-row', [
                    'booking_service' => $item,
                    'key' => $key + 1,
                ])
            @endif
        @endforeach
    </tbody>
</table>
