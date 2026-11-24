<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.land_bill_of_lading') }} #{{ $policy->id }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @php
        $container = $policy->booking_containers->first();
        $booking = $container?->booking;
        $companyName = $booking?->company?->name ?? '—';
        $logoSrc = null;
        $logoPath = public_path('assets/media/logo.png');
        if (is_file($logoPath)) {
            $mime = @mime_content_type($logoPath) ?: 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPath));
        }
        $custodyEgp = number_format((float) ($policy->money_transfer?->value ?? 0), 2);
        $policyDate = $policy->date ?? $policy->getRawOriginal('created_at') ?? '—';
        $printedAt = now()->format('Y-m-d H:i');
    @endphp
    <style>
        :root {
            --ink: #152033;
            --muted: #5b6577;
            --line: #d7dde8;
            --soft: #f4f7fb;
            --accent: #0f766e;
            --accent-soft: #e6f5f3;
            --warn: #b45309;
            --paper: #ffffff;
        }

        * { box-sizing: border-box; }

        @page {
            size: A4;
            margin: 8mm;
        }

        body {
            margin: 0;
            background: #e8edf5;
            color: var(--ink);
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
            padding: 12px 16px;
            background: rgba(21, 32, 51, 0.92);
            backdrop-filter: blur(6px);
        }

        .toolbar button {
            border: 0;
            border-radius: 10px;
            padding: 10px 18px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .toolbar .btn-print {
            background: #14b8a6;
            color: #fff;
        }

        .toolbar .btn-back {
            background: #fff;
            color: #152033;
        }

        .sheet-wrap {
            padding: 24px 16px 40px;
        }

        .sheet {
            width: 210mm;
            max-width: 100%;
            min-height: 297mm;
            margin: 0 auto;
            background: var(--paper);
            border: 1px solid #c9d3e3;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(21, 32, 51, 0.12);
            padding: 18mm 16mm;
            position: relative;
            overflow: hidden;
        }

        .sheet::before {
            content: "";
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #0f766e, #1d4ed8);
        }

        .header {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: center;
            padding-bottom: 14px;
            border-bottom: 2px solid var(--ink);
            margin-bottom: 16px;
        }

        .brand {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .brand img {
            width: 64px;
            height: 64px;
            object-fit: contain;
        }

        .brand h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.35;
        }

        .brand p {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
        }

        .doc-meta {
            text-align: left;
            min-width: 170px;
        }

        .badge {
            display: inline-block;
            background: var(--accent-soft);
            color: var(--accent);
            border: 1px solid #9ad5cf;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .doc-meta .num {
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: 0.5px;
        }

        .doc-meta .sub {
            margin-top: 6px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }

        .section-title {
            margin: 18px 0 10px;
            font-size: 14px;
            font-weight: 800;
            color: var(--accent);
            letter-spacing: 0.3px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--soft);
            padding: 12px 14px;
            min-height: 68px;
        }

        .card .label {
            display: block;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .card .value {
            display: block;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.45;
            word-break: break-word;
        }

        .route {
            display: grid;
            grid-template-columns: 1fr auto 1fr auto 1fr;
            gap: 8px;
            align-items: stretch;
            margin-top: 4px;
        }

        .route-step {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
            background: #fff;
            text-align: center;
        }

        .route-step .label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .route-step .value {
            font-size: 14px;
            font-weight: 800;
            line-height: 1.4;
        }

        .route-arrow {
            align-self: center;
            color: var(--accent);
            font-size: 18px;
            font-weight: 800;
        }

        .money {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .money .card {
            background: #fff;
            border-color: #b7e0db;
        }

        .money .card.primary {
            background: linear-gradient(135deg, #0f766e, #0d9488);
            color: #fff;
            border: 0;
        }

        .money .card.primary .label {
            color: rgba(255, 255, 255, 0.85);
        }

        .note {
            margin-top: 8px;
            color: var(--warn);
            font-size: 12px;
            font-weight: 700;
        }

        .timeline {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid var(--line);
        }

        .timeline th,
        .timeline td {
            border-bottom: 1px solid var(--line);
            padding: 10px 12px;
            text-align: right;
            font-size: 13px;
        }

        .timeline th {
            background: var(--soft);
            font-weight: 800;
            color: var(--muted);
        }

        .timeline tr:last-child td {
            border-bottom: 0;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 14px;
            margin-top: 28px;
        }

        .sign-box {
            border: 1px dashed #aeb8c9;
            border-radius: 12px;
            min-height: 110px;
            padding: 12px;
            text-align: center;
        }

        .sign-box .label {
            font-size: 13px;
            font-weight: 800;
            color: var(--muted);
        }

        .sign-box .line {
            margin-top: 58px;
            border-top: 1px solid #9aa6b8;
            padding-top: 8px;
            font-size: 12px;
            color: var(--muted);
            font-weight: 700;
        }

        .footer {
            margin-top: 22px;
            padding-top: 10px;
            border-top: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .sheet-wrap { padding: 12px 8px 28px; }
            .sheet { padding: 16px; border-radius: 10px; }
            .header, .grid-2, .money, .signatures, .route {
                grid-template-columns: 1fr;
            }
            .route-arrow { display: none; }
            .doc-meta { text-align: right; }
        }

        @media print {
            html, body {
                background: #fff !important;
                height: auto !important;
            }
            .toolbar { display: none !important; }
            .sheet-wrap { padding: 0 !important; margin: 0 !important; }
            .sheet {
                width: auto !important;
                max-width: none !important;
                min-height: 0 !important;
                height: auto !important;
                margin: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                padding: 2mm 0 0 !important;
                page-break-after: avoid !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .sheet::before { height: 3px; }

            .header {
                gap: 8px !important;
                padding-bottom: 6px !important;
                margin-bottom: 6px !important;
            }
            .brand { gap: 8px !important; }
            .brand img {
                width: 40px !important;
                height: 40px !important;
            }
            .brand h1 { font-size: 15px !important; line-height: 1.25 !important; }
            .brand p { font-size: 10px !important; margin: 0 !important; }
            .doc-meta { min-width: 0 !important; }
            .badge {
                padding: 1px 8px !important;
                font-size: 9px !important;
                margin-bottom: 3px !important;
            }
            .doc-meta .num { font-size: 18px !important; }
            .doc-meta .sub { font-size: 10px !important; margin-top: 2px !important; }

            .section-title {
                margin: 7px 0 4px !important;
                font-size: 11px !important;
            }

            .grid-2,
            .money {
                gap: 5px !important;
            }

            .card {
                border-radius: 6px !important;
                padding: 6px 8px !important;
                min-height: 0 !important;
            }
            .card .label {
                font-size: 9px !important;
                margin-bottom: 1px !important;
            }
            .card .value {
                font-size: 11px !important;
                line-height: 1.3 !important;
            }

            .route { gap: 4px !important; margin-top: 0 !important; }
            .route-step {
                border-radius: 6px !important;
                padding: 6px !important;
            }
            .route-step .label {
                font-size: 9px !important;
                margin-bottom: 2px !important;
            }
            .route-step .value {
                font-size: 11px !important;
                line-height: 1.25 !important;
            }
            .route-arrow { font-size: 12px !important; }

            .note {
                margin-top: 3px !important;
                font-size: 9px !important;
            }

            .timeline {
                margin-top: 2px !important;
                border-radius: 6px !important;
            }
            .timeline th,
            .timeline td {
                padding: 3px 6px !important;
                font-size: 9px !important;
                line-height: 1.25 !important;
            }

            .signatures {
                gap: 8px !important;
                margin-top: 10px !important;
            }
            .sign-box {
                border-radius: 6px !important;
                min-height: 52px !important;
                padding: 6px !important;
            }
            .sign-box .label { font-size: 10px !important; }
            .sign-box .line {
                margin-top: 22px !important;
                padding-top: 4px !important;
                font-size: 9px !important;
            }

            .footer {
                margin-top: 8px !important;
                padding-top: 4px !important;
                font-size: 9px !important;
            }

            a { text-decoration: none; color: inherit; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="btn-print" onclick="window.print()">{{ __('admin.print') ?? 'طباعة' }}</button>
        <button type="button" class="btn-back" onclick="window.close()">{{ __('main.back') }}</button>
    </div>

    <div class="sheet-wrap">
        <article class="sheet">
            <header class="header">
                <div class="brand">
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" alt="Leader for Trans">
                    @endif
                    <div>
                        <h1>{{ __('main.land_bill_of_lading') }}</h1>
                        <p>Leader for Trans</p>
                    </div>
                </div>
                <div class="doc-meta">
                    @if($isReefer)
                        <div class="badge">{{ __('main.reefer_outbound') }}</div>
                    @endif
                    <div class="num">#{{ $policy->id }}</div>
                    <div class="sub">{{ __('main.date') }}: {{ $policyDate }}</div>
                </div>
            </header>

            <div class="section-title">بيانات الطلب</div>
            <div class="grid-2">
                <div class="card">
                    <span class="label">{{ __('admin.booking_number') ?? __('main.booking_number') }}</span>
                    <span class="value">{{ $booking?->booking_number ?? '—' }}</span>
                </div>
                <div class="card">
                    <span class="label">{{ __('admin.company') ?? __('main.companies') }}</span>
                    <span class="value">{{ $companyName }}</span>
                </div>
                <div class="card">
                    <span class="label">{{ __('main.container') }}</span>
                    <span class="value">{{ $container?->container_no ?? '—' }}</span>
                </div>
                <div class="card">
                    <span class="label">{{ __('admin.address') }}</span>
                    <span class="value">{{ $policy->address ?: '—' }}</span>
                </div>
            </div>

            <div class="section-title">بيانات النقل</div>
            <div class="grid-2">
                <div class="card">
                    <span class="label">{{ __('main.drivers') }}</span>
                    <span class="value">{{ $policy->driver?->name ?? '—' }}</span>
                </div>
                <div class="card">
                    <span class="label">{{ __('admin.car_number') }}</span>
                    <span class="value">{{ $policy->car?->car_number ?? '—' }}</span>
                </div>
            </div>

            <div class="section-title">المسار</div>
            <div class="route">
                <div class="route-step">
                    <div class="label">{{ __('admin.departure') }}</div>
                    <div class="value">{{ $container?->departure?->title ?? '—' }}</div>
                </div>
                <div class="route-arrow">←</div>
                <div class="route-step">
                    <div class="label">{{ __('admin.loading') }} / {{ __('main.destination') }}</div>
                    <div class="value">{{ $container?->loading?->title ?? '—' }}</div>
                </div>
                <div class="route-arrow">←</div>
                <div class="route-step">
                    <div class="label">{{ __('admin.aging') }}</div>
                    <div class="value">{{ $container?->aging?->title ?? '—' }}</div>
                </div>
            </div>

            <div class="section-title">العهدة</div>
            <div class="money">
                <div class="card primary">
                    <span class="label">{{ __('admin.custody_egp') }}</span>
                    <span class="value">{{ $custodyEgp }} {{ __('main.currency') }}</span>
                </div>
                <div class="card">
                    <span class="label">{{ __('admin.custody_usd') }}</span>
                    <span class="value">
                        @if($policy->custody_usd !== null)
                            {{ number_format((float) $policy->custody_usd, 2) }} USD
                        @else
                            —
                        @endif
                    </span>
                    @if($policy->custody_usd !== null)
                        <div class="note">{{ __('main.custody_usd_record_only_note') }}</div>
                    @endif
                </div>
            </div>

            @if($isReefer && count($timeline))
                <div class="section-title">{{ __('main.reefer_trip_timeline') }}</div>
                <table class="timeline">
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

            <div class="signatures">
                <div class="sign-box">
                    <div class="label">توقيع السائق</div>
                    <div class="line">الاسم / التوقيع</div>
                </div>
                <div class="sign-box">
                    <div class="label">توقيع المندوب</div>
                    <div class="line">الاسم / التوقيع</div>
                </div>
                <div class="sign-box">
                    <div class="label">ختم الشركة</div>
                    <div class="line">Leader for Trans</div>
                </div>
            </div>

            <footer class="footer">
                <span>{{ __('main.delivery_policies') }} #{{ $policy->id }}</span>
                <span>طُبعت: {{ $printedAt }}</span>
            </footer>
        </article>
    </div>
</body>
</html>
