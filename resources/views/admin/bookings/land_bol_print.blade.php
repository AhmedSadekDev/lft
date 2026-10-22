<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ __('main.land_bill_of_lading') }} #{{ $policy->id }}</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; color: #111; margin: 24px; }
        h1 { font-size: 22px; margin: 0 0 8px; }
        .meta { color: #555; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        th, td { border: 1px solid #ccc; padding: 8px 10px; text-align: right; font-size: 13px; }
        th { background: #f5f5f5; width: 28%; }
        .usd-note { color: #666; font-size: 12px; }
        .actions { margin-bottom: 16px; }
        .actions button { padding: 8px 16px; cursor: pointer; }
        @media print {
            .actions { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    @php
        $container = $policy->booking_containers->first();
        $booking = $container?->booking;
    @endphp

    <div class="actions">
        <button type="button" onclick="window.print()">{{ __('admin.print') ?? 'طباعة' }}</button>
        <button type="button" onclick="window.close()">{{ __('main.back') }}</button>
    </div>

    <h1>{{ __('main.land_bill_of_lading') }}</h1>
    <div class="meta">
        {{ __('main.delivery_policies') }} #{{ $policy->id }}
        @if($isReefer)
            — {{ __('main.reefer_outbound') }}
        @endif
    </div>

    <table>
        <tr>
            <th>{{ __('admin.booking_number') ?? __('main.booking_number') }}</th>
            <td>{{ $booking?->booking_number ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('admin.company') ?? __('main.companies') }}</th>
            <td>{{ $booking?->company?->name ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('main.container') }}</th>
            <td>{{ $container?->container_no ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('main.drivers') }}</th>
            <td>{{ $policy->driver?->name ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('admin.car_number') }}</th>
            <td>{{ $policy->car?->car_number ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('admin.departure') }}</th>
            <td>{{ $container?->departure?->title ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('admin.loading') }} / {{ __('main.destination') }}</th>
            <td>{{ $container?->loading?->title ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('admin.aging') }}</th>
            <td>{{ $container?->aging?->title ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('main.date') }}</th>
            <td>{{ $policy->date ?? $policy->getRawOriginal('created_at') ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('admin.custody_egp') }}</th>
            <td>{{ number_format((float) ($policy->money_transfer?->value ?? 0), 2) }} {{ __('main.currency') }}</td>
        </tr>
        <tr>
            <th>{{ __('admin.custody_usd') }}</th>
            <td>
                @if($policy->custody_usd !== null)
                    {{ number_format((float) $policy->custody_usd, 2) }} USD
                    <div class="usd-note">{{ __('main.custody_usd_record_only_note') }}</div>
                @else
                    —
                @endif
            </td>
        </tr>
    </table>

    @if($isReefer && count($timeline))
        <h2 style="font-size:16px;">{{ __('main.reefer_trip_timeline') }}</h2>
        <table>
            <thead>
                <tr>
                    <th style="width:8%;">#</th>
                    <th>{{ __('main.stage') }}</th>
                    <th>{{ __('main.date') }} / {{ __('main.time') }}</th>
                    <th>{{ __('admin.notes') ?? 'ملاحظات' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($timeline as $row)
                    <tr>
                        <td>{{ $row['stage'] }}</td>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['occurred_at'] ?? '—' }}</td>
                        <td>{{ $row['notes'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
