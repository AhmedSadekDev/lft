@php
    $company = $invoice->booking->company ?? null;
    $privateCompany = $company->privateCompany ?? null;

    // استخدام بيانات الشركة الخاصة إن وجدت، وإلا استخدام بيانات الشركة العادية
    $displayName = $privateCompany ? $privateCompany->name : ($company->name ?? "");
    $displayTaxNo = $privateCompany ? $privateCompany->tax_no : ($company->tax_no ?? "");
    $displayCommercialRegister = $privateCompany ? $privateCompany->commercial_register : "";
    $logoUrl = $privateCompany ? $privateCompany->logo : null;

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

        /* Logo on the left */
        .logo-container {
            order: 1 !important;
            flex: 0 0 auto !important;
            min-width: 220px !important;
        }

        /* Company info on the right */
        .company-info-container {
            order: 2 !important;
            flex: 1 !important;
            text-align: right !important;
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
        position: relative;
        width: 100%;
        background: #fff;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #000;
        border-bottom: 2px solid #8B4513;
     ">

    <!-- Top Section -->
    <div style="
        display: flex;
        justify-content: space-between;
        align-items: flex-start; /* محاذاة صحيحة */
        gap: 30px;
        direction: ltr;
        position: relative;
        z-index: 2;
    ">

        <!-- LEFT : Logo -->
        <div style="
            flex: 0 0 auto;
            min-width: 220px;
            height: 95px;
            display: flex;
            align-items: flex-start;
        ">
            @if($logoUrl)
                <img src="{{ $logoUrl }}"
                     alt="Logo"
                     style="
                        max-width: 200px;
                        max-height: 95px;
                        object-fit: contain;
                        display: block;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                     ">
            @else
                <!-- Default Logo -->
                <svg width="200" height="95" viewBox="0 0 200 95">
                    <path d="M 5 55 Q 100 15 195 55"
                          stroke="#DC143C" stroke-width="2.5" fill="none"/>
                    <path d="M 185 50 L 195 55 L 190 55 L 190 60 L 195 60 L 195 55"
                          fill="#DC143C"/>
                    <text x="15" y="45" font-family="serif"
                          font-size="28" font-weight="bold" fill="#333">
                        LEADER
                    </text>
                    <text x="15" y="70" font-family="sans-serif"
                          font-size="13" fill="#666">
                        FOR TRANS
                    </text>
                </svg>
            @endif
        </div>

        <!-- RIGHT : Company Info -->
        <div style="
            flex: 1;
            text-align: right;
            direction: rtl;
            padding-right: 20px;
            padding-top: 2px;
        ">

            <div style="
                color: #DC143C;
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 6px;
                font-family: 'Cairo', sans-serif;
            ">
                {{ $displayName }}
            </div>

            <div style="
                color: #333;
                font-size: 16px;
                margin-bottom: 10px;
                font-family: 'Cairo', sans-serif;
            ">
                لنقل الحاويات
            </div>

            @if($displayTaxNo)
                <div style="
                    color: #333;
                    font-size: 13px;
                    margin-bottom: 4px;
                    font-family: 'Cairo', sans-serif;
                ">
                    ب.ض : {{ $displayTaxNo }}
                </div>
            @endif

            @if($displayCommercialRegister)
                <div style="
                    color: #333;
                    font-size: 13px;
                    font-family: 'Cairo', sans-serif;
                ">
                    س.ت : {{ $displayCommercialRegister }}
                </div>
            @endif

        </div>
    </div>

    <!-- Watermark (Print Only) -->
    <div class="watermark"
         style="
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.12;
            z-index: 0;
            pointer-events: none;
            width: 600px;
            height: 600px;
            display: none;
         ">
        @if($logoUrl)
            <img src="{{ $logoUrl }}"
                 alt="Watermark"
                 style="
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                    filter: grayscale(100%) brightness(1.4);
                 ">
        @else
            <svg width="500" height="300" viewBox="0 0 500 300">
                <path d="M 30 150 Q 250 30 470 150"
                      stroke="#DC143C" stroke-width="4" fill="none" opacity="0.2"/>
                <text x="50" y="140" font-family="serif"
                      font-size="80" font-weight="bold"
                      fill="#DC143C" opacity="0.2">
                    LEADER
                </text>
                <text x="50" y="190" font-family="sans-serif"
                      font-size="40"
                      fill="#DC143C" opacity="0.2">
                    FOR TRANS
                </text>
            </svg>
        @endif
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
            <h2 style="font-family: 'Cairo', sans-serif; color: #fff; margin: 0; font-size: 16px; font-weight: 700;">{{ str_replace('فواتير', 'فاتوره', $document_title ?? '') }}</h2>
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
<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 6px; position: relative; z-index: 2;">
    <div style="background: #f8f9fa; padding: 6px 10px; border-radius: 6px; border-right: 3px solid #DC143C;">
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">🏢 اسم الشركة:</span>
                <span style="color: #212529; font-size: 11px; font-weight: 600;">{{ $displayName }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">🔢 الرقم الضريبي:</span>
                <span style="color: #212529; font-size: 11px;">{{ $displayTaxNo }}</span>
            </div>
            @if($displayCommercialRegister)
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">📋 السجل التجاري:</span>
                <span style="color: #212529; font-size: 11px;">{{ $displayCommercialRegister }}</span>
            </div>
            @endif
        </div>
    </div>

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

    <div style="background: #fff; padding: 6px 10px; border-radius: 6px; border: 1px solid #dee2e6; border-right: 3px solid #ffc107;">
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">👤 عناية:</span>
                <span style="color: #212529; font-size: 11px;">{{ $invoice->booking->employee?->name ?? "" }}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <span style="font-weight: 700; color: #495057; font-size: 10px; min-width: 75px;">🚢 الخط الملاحي:</span>
                <span style="color: #212529; font-size: 11px;">{{ $invoice->booking->shippingAgent->title ?? "" }}</span>
            </div>
        </div>
    </div>
</div>
