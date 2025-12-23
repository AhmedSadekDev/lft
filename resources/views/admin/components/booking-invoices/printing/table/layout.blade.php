<table style="display: table; width: 100%; margin-top: 0.5rem; border-spacing: 0; border: 1px solid #dee2e6; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);">
    <thead style="background: linear-gradient(135deg, #343a40 0%, #212529 100%); color: #fff; font-family: 'Cairo', sans-serif; font-size: 0.85rem; vertical-align: middle;">
        <tr>
            <th style="padding: 10px 12px; text-align: center; font-weight: 700; border-right: 1px solid rgba(255, 255, 255, 0.1); width: 50px;">م</th>
            <th style="padding: 10px 12px; text-align: start; font-weight: 700; border-right: 1px solid rgba(255, 255, 255, 0.1);">تفاصيل الفاتورة</th>
            <th style="padding: 10px 12px; text-align: center; font-weight: 700; width: 110px;">التكلفة</th>
        </tr>
    </thead>
    <tbody style="font-family: 'Cairo', sans-serif; font-size: 0.8rem; text-align: center; vertical-align: middle;">
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
