@php
    $company = $invoice->booking->company ?? null;
    $privateCompany = $company->privateCompany ?? null;

    // استخدام بيانات الشركة الخاصة للاسم واللوجو والسجل التجاري والرقم الضريبي
    $displayName = $privateCompany ? $privateCompany->name : ($company->name ?? "");
    $logoUrl = $privateCompany ? $privateCompany->logo : null;

    // السجل التجاري والرقم الضريبي من الشركة الخاصة
    $privateCompanyTaxNo = $privateCompany->tax_no ?? "";
    $privateCompanyCommercialRegister = $privateCompany->commercial_register ?? "";

    // الرقم الضريبي للعميل (الشركة)
    $clientTaxNo = $company->tax_no ?? "";

    // اسم العميل/الشركة التي نرسل لها الفاتورة
    $clientName = $company->name ?? "";

    // معلومات الاتصال من الشركة الخاصة
    if ($privateCompany) {
        $contactPhone1 = $privateCompany->phone1 ?? "01001365666";
        $contactPhone2 = $privateCompany->phone2 ?? "01013118008";
        $contactTelFax = $privateCompany->tel_fax ?? "057 - 2292423";
        $contactEmail = $privateCompany->email ?? "leader@leaderfortrans.com";
        $contactAddress = $privateCompany->address ?? "ميناء دمياط المجمع الاستثمارى وحدة ٢٠٢";
    } else {
        // استخدام القيم الافتراضية إذا لم تكن هناك شركة خاصة
        $settings = \App\Models\Setting::first();
        $contactPhone1 = $settings->phone ?? "01001365666";
        $contactPhone2 = "01013118008";
        $contactTelFax = "057 - 2292423";
        $contactEmail = $settings->email ?? "leader@leaderfortrans.com";
        $contactAddress = "ميناء دمياط المجمع الاستثمارى وحدة ٢٠٢";
    }
@endphp

<style>
    @media print {
        .letterhead-header {
            position: relative !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: 100% !important;
            background: #fff !important;
            padding: 20px !important;
            margin-bottom: 20px !important;
            margin-top: 0 !important;
            border: 1px solid #000 !important;
            border-bottom: 2px solid #8B4513 !important;
            z-index: 1000 !important;
            page-break-inside: avoid !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        .letterhead-footer {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            position: fixed !important;
            bottom: 10px !important;
            left: 10px !important;
            right: 10px !important;
            width: calc(100% - 20px) !important;
            background: #fff !important;
            border: 2px solid #8B4513 !important;
            border-radius: 8px !important;
            padding: 15px 20px !important;
            z-index: 1000 !important;
            page-break-inside: avoid !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
        }

        body {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            padding: 0 !important;
        }

        .watermark {
            display: block !important;
            visibility: visible !important;
            opacity: 0.15 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* Ensure flexbox layout works correctly in print */
        .logo-company-container {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
        }

        /* Ensure logo and company info are vertically aligned in print */
        .letterhead-header > div[style*="display: flex"] {
            align-items: center !important;
        }

        /* Ensure logo container is centered */
        .letterhead-header > div > div:first-child {
            display: flex !important;
            align-items: center !important;
        }

        /* Ensure company info is centered */
        .letterhead-header > div > div:last-child {
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
        }

        /* Logo on the left */
        .logo-container {
            order: 1 !important;
            flex: 0 0 auto !important;
        }

        /* Bigger logo in print */
        .logo-container img,
        .logo-container svg {
            max-width: 250px !important;
            max-height: 120px !important;
            width: 250px !important;
            height: auto !important;
        }

        /* Company info on the right */
        .company-info-container {
            order: 2 !important;
            flex: 1 !important;
            text-align: right !important;
            direction: rtl !important;
        }
    }

    @media screen {
        .letterhead-header {
            position: relative;
            margin-bottom: 20px;
        }

        .letterhead-footer {
            display: none;
        }

        .watermark {
            display: none !important;
        }
    }

</style>

<!-- Letterhead Header -->
<div class="letterhead-header"
     style="
        width: 100%;
        background: #fff;
        padding: 15px 20px;
        margin-bottom: 15px;
        border: 1px solid #000;
        border-bottom: 2px solid #8B4513;
     ">

    <!-- TOP ROW: Logo on Left, Company Info on Right -->
    <div class="logo-company-container" style="
        display: flex;
        align-items: center;
        justify-content: space-between;
        direction: ltr;
        gap: 20px;
    ">

        <!-- LEFT : Logo -->
        <div class="logo-container" style="
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            min-width: 250px;
        ">
            @if($logoUrl)
                <img src="{{ $logoUrl }}"
                     alt="Logo"
                     class="logo-image"
                     style="
                        max-width: 250px;
                        max-height: 120px;
                        width: 250px;
                        height: auto;
                        object-fit: contain;
                        display: block;
                     ">
            @else
                <svg width="250" height="120" viewBox="0 0 200 90" class="logo-svg">
                    <circle cx="100" cy="45" r="40" fill="#DC143C" opacity="0.1"/>
                    <text x="100" y="50" font-size="20" font-weight="bold" fill="#DC143C" text-anchor="middle" font-family="Arial">LEADER FOR TRANS</text>
                </svg>
            @endif
        </div>

        <!-- RIGHT : Private Company Name and Info -->
        <div class="company-info-container" style="
            flex: 1;
            text-align: right;
            direction: rtl;
        ">
            <div style="
                color: #DC143C;
                font-size: 24px;
                font-weight: bold;
                font-family: 'Cairo', sans-serif;
                line-height: 1.3;
                margin-bottom: 4px;
            ">
                {{ $displayName }}
            </div>

            <div style="
                color: #333;
                font-size: 14px;
                font-family: 'Cairo', sans-serif;
                margin-bottom: 4px;
            ">
                لنقل الحاويات
            </div>

            @if($privateCompanyCommercialRegister)
                <div style="font-size: 12px; color: #666; margin-bottom: 2px;">السجل التجاري: {{ $privateCompanyCommercialRegister }}</div>
            @endif

            @if($privateCompanyTaxNo)
                <div style="font-size: 12px; color: #666;">ب.ض: {{ $privateCompanyTaxNo }}</div>
            @endif
        </div>

    </div>
</div>

<!-- Print Rules -->
<style>
@media print {
    .watermark {
        display: block !important;
    }
}
</style>



<!-- Letterhead Footer -->
<div class="letterhead-footer" style="position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border: 2px solid #8B4513; border-radius: 8px; padding: 15px 20px; z-index: 1000; display: none; margin: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <!-- Left: English Contact Info -->
    <div style="flex: 1; border-right: 1px solid #8B4513; padding-right: 20px;">
        <div style="font-size: 12px; color: #333; line-height: 1.8; font-family: Arial, sans-serif;">
            <div><strong>MOBILE:</strong> {{ $contactPhone1 }}</div>
            <div><strong>MOBILE:</strong> {{ $contactPhone2 }}</div>
            <div><strong>Tel-Fax:</strong> {{ $contactTelFax }}</div>
            <div><strong>E-mail:</strong> {{ $contactEmail }}</div>
        </div>
    </div>

    <!-- Right: Arabic Company Info -->
    <div style="flex: 1; padding-left: 20px; text-align: right;">
        <div style="font-size: 13px; color: #333; line-height: 1.8; font-family: 'Cairo', sans-serif;">
            <div style="margin-bottom: 5px;">
                @php
                    $nameParts = explode(' ', $displayName);
                    $firstPart = $nameParts[0] ?? $displayName;
                    $restParts = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
                @endphp
                <span style="color: #DC143C; font-weight: bold;">{{ $firstPart }}</span>
                @if($restParts)
                    <span style="color: #333;">{{ $restParts }}</span>
                @endif
            </div>
            <div style="margin-bottom: 5px;">لنقل الحاويات</div>
            <div>{{ $contactAddress }}</div>
        </div>
    </div>
</div>

<!-- Invoice Title and Number -->
<div style="background: linear-gradient(135deg, #DC143C 0%, #B22222 100%); padding: 8px 12px; border-radius: 6px; margin-bottom: 6px; box-shadow: 0 4px 15px rgba(220, 20, 60, 0.2); -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; display: block !important; visibility: visible !important; page-break-inside: avoid; position: relative; z-index: 2;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <h2 style="font-family: 'Cairo', sans-serif; color: #fff; margin: 0; font-size: 16px; font-weight: 700;">{{ str_replace('فواتير', 'فاتوره', $document_title ?? '') }} - {{ $clientName }}</h2>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="color: #fff; font-weight: 600; font-size: 11px;">فاتورة رقم:</span>
                <span style="color: #fff; font-size: 12px; font-weight: 700;">{{ $invoice->invoice_number ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="color: #fff; font-weight: 600; font-size: 11px;">التاريخ:</span>
                <span style="color: #fff; font-size: 11px;">{{ $invoice->created_at ?? "" }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Company and Booking Details -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 6px; position: relative; z-index: 2;">
    <!-- Left: Recipient Information -->
    <div style="background: #fff; padding: 6px 10px; border-radius: 6px; border: 1px solid #dee2e6; border-right: 3px solid #DC143C;">
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">👤 عناية:</span>
                <span style="color: #212529; font-size: 11px;">{{ $invoice->booking->employee?->name ?? $invoice->booking->company->name ?? "" }}</span>
            </div>
            @if($clientTaxNo)
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">📋 الرقم الضريبي:</span>
                <span style="color: #212529; font-size: 11px;">{{ $clientTaxNo }}</span>
            </div>
            @endif
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">🚢 الخط الملاحي:</span>
                <span style="color: #212529; font-size: 11px;">{{ $invoice->booking->shippingAgent->title ?? "" }}</span>
            </div>
        </div>
    </div>

    <!-- Right: Manufacturer Information -->
    <div style="background: #fff; padding: 6px 10px; border-radius: 6px; border: 1px solid #dee2e6; border-right: 3px solid #28a745;">
        <div style="display: flex; flex-direction: column; gap: 4px;">
            @php
                $firstContainer = $invoice->booking->bookingContainers->first();
                $factoryName = $firstContainer->branch?->factory->name ?? "";
            @endphp
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">🏭 اسم المصنع:</span>
                <span style="color: #212529; font-size: 11px;">{{ $factoryName }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">📋 رقم الحجز:</span>
                <span style="color: #212529; font-size: 11px; font-weight: 700;">{{ $invoice->booking->booking_number ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">📄 رقم الشهادة:</span>
                <span style="color: #212529; font-size: 11px;">{{ $invoice->booking->certificate_number ?? "" }}</span>
            </div>
        </div>
    </div>
</div>
