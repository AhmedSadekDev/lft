@php
    $company = $invoice->booking->company ?? null;
    $privateCompany = $company->privateCompany ?? null;

    // استخدام بيانات الشركة الخاصة للاسم واللوجو فقط
    $displayName = $privateCompany ? $privateCompany->name : ($company->name ?? "");

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

<!-- Invoice Footer -->
<div class="invoice-footer" style="display: none; margin-top: 20px; padding-top: 15px; border-top: 2px solid #8B4513;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%; gap: 20px;">
        <!-- Left: English Contact Info -->
        <div style="flex: 1; text-align: left;">
            <div style="font-size: 12px; color: #333; line-height: 1.8; font-family: Arial, sans-serif;">
                <div><strong>MOBILE:</strong> {{ $contactPhone1 }}</div>
                <div><strong>MOBILE:</strong> {{ $contactPhone2 }}</div>
                <div><strong>Tel-Fax:</strong> {{ $contactTelFax }}</div>
                <div><strong>E-mail:</strong> {{ $contactEmail }}</div>
            </div>
        </div>

        <!-- Right: Arabic Company Info -->
        <div style="flex: 1; text-align: right;">
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
</div>
