<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $typeLabel }} — {{ $noteNumber }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f8fafc; margin: 0; padding: 24px; color: #0f172a; }
        .sheet { max-width: 720px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px 32px; box-shadow: 0 10px 30px rgba(15,23,42,.08); }
        .brand { display: flex; align-items: center; gap: 14px; border-bottom: 2px solid #1E4DAE; padding-bottom: 16px; margin-bottom: 20px; }
        .brand img { height: 64px; background: #fff; border-radius: 10px; padding: 4px 8px; }
        .brand h1 { margin: 0; font-size: 20px; color: #1E4DAE; }
        .brand p { margin: 2px 0 0; color: #64748b; font-size: 13px; }
        .badge { display: inline-block; padding: 6px 14px; border-radius: 999px; font-weight: 700; font-size: 13px;
            background: {{ $type === 'addition' ? 'rgba(16,185,129,.12)' : 'rgba(220,53,69,.12)' }};
            color: {{ $type === 'addition' ? '#047857' : '#b91c1c' }}; }
        .meta { margin: 18px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px 18px; font-size: 14px; }
        .meta strong { color: #475569; display: block; font-size: 12px; margin-bottom: 2px; }
        .amount { margin: 22px 0; padding: 18px; border-radius: 12px; text-align: center;
            background: linear-gradient(135deg, #1E4DAE 0%, #64B1F2 100%); color: #fff; }
        .amount .label { font-size: 14px; opacity: .9; }
        .amount .value { font-size: 32px; font-weight: 800; margin-top: 4px; }
        .reason { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; }
        .reason strong { display: block; margin-bottom: 6px; color: #1E4DAE; }
        .actions { margin-top: 22px; text-align: center; }
        .actions button, .actions a { font-family: inherit; border: 0; border-radius: 10px; padding: 10px 18px; margin: 0 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .print-btn { background: #1E4DAE; color: #fff; }
        .back-btn { background: #e2e8f0; color: #0f172a; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none !important; }
            .sheet { box-shadow: none; border: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="brand">
            <img src="{{ asset('assets/media/logo.png') }}" alt="Leader Trans International">
            <div>
                <h1>Leader Trans International</h1>
                <p>{{ $typeLabel }} — على حساب الشركة</p>
            </div>
        </div>

        <span class="badge">{{ $typeLabel }}</span>

        <div class="meta">
            <div>
                <strong>رقم الإشعار</strong>
                {{ $noteNumber }}
            </div>
            <div>
                <strong>التاريخ</strong>
                {{ $noteDate }}
            </div>
            <div>
                <strong>الشركة</strong>
                {{ $company->name }}
            </div>
            <div>
                <strong>الأثر على الرصيد</strong>
                {{ $type === 'addition' ? 'زيادة المستحق على الشركة' : 'تخفيض المستحق على الشركة' }}
            </div>
        </div>

        <div class="amount">
            <div class="label">المبلغ</div>
            <div class="value">{{ number_format($amount, 2) }} {{ $currencySymbol }}</div>
        </div>

        <div class="reason">
            <strong>السبب</strong>
            {{ $reason ?: '—' }}
        </div>

        <div class="actions">
            <button type="button" class="print-btn" onclick="window.print()">طباعة</button>
            <a class="back-btn" href="{{ route('accounts.statement', $company->id) }}">رجوع لكشف الحساب</a>
        </div>
    </div>
</body>
</html>
