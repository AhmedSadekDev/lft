{{-- Page Header --}}
<div class="invoice-page-header mb-4" style="direction: rtl;">
    <div class="invoice-page-header-inner">
        <div class="d-flex align-items-center">
            
            <div class="mr-3">
                <h1 class="invoice-page-title">{{ __('admin.add_new_invoice') }}</h1>
                <p class="invoice-page-subtitle">{{ __('admin.company_name') }}: {{ $booking->company->name ?? '-' }}</p>
            </div>
        </div>
        <a href="{{ route('bookings.show', $booking) }}" class="invoice-back-btn">
            <i class="fas fa-arrow-right ml-2"></i>
            رجوع لتفاصيل الطلب
        </a>
    </div>
</div>

{{-- General Info Card --}}
<div class="invoice-section-card mb-4">
    <div class="invoice-section-head invoice-section-head--blue">
        <span class="invoice-section-icon invoice-section-icon--blue"><i class="fas fa-info-circle"></i></span>
        <h2 class="invoice-section-title">{{ __('admin.general_details') }}</h2>
    </div>
    <div class="invoice-section-body">
        <div class="row">
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="invoice-info-item">
                    <span class="invoice-info-icon invoice-info-icon--blue"><i class="fas fa-hashtag"></i></span>
                    <div class="invoice-info-text">
                        <span class="invoice-info-label">{{ __('admin.booking_number') }}</span>
                        <span class="invoice-info-value">{{ $booking->booking_number ?? '-' }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="invoice-info-item">
                    <span class="invoice-info-icon invoice-info-icon--green"><i class="fas fa-file-invoice"></i></span>
                    <div class="invoice-info-text">
                        <span class="invoice-info-label">{{ __('admin.invoice_number') }}</span>
                        <span class="invoice-info-value">{{ (isset($invoice) ? $invoice->invoice_number : $invoice_number) ?? '-' }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="invoice-info-item">
                    <span class="invoice-info-icon invoice-info-icon--blue"><i class="fas fa-building"></i></span>
                    <div class="invoice-info-text">
                        <span class="invoice-info-label">{{ __('admin.company_name') }}</span>
                        <span class="invoice-info-value">{{ $booking->company->name ?? '-' }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="invoice-info-item">
                    <span class="invoice-info-icon invoice-info-icon--orange"><i class="fas fa-certificate"></i></span>
                    <div class="invoice-info-text">
                        <span class="invoice-info-label">{{ __('admin.certificate_number') }}</span>
                        <span class="invoice-info-value">{{ $booking->certificate_number ?? '-' }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="invoice-info-item">
                    <span class="invoice-info-icon invoice-info-icon--teal"><i class="fas fa-calendar-alt"></i></span>
                    <div class="invoice-info-text">
                        <span class="invoice-info-label">{{ __('admin.arrival_date') }}</span>
                        <span class="invoice-info-value">{{ $booking->arrival_date ?? '-' }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="invoice-info-item">
                    <span class="invoice-info-icon invoice-info-icon--purple"><i class="fas fa-ship"></i></span>
                    <div class="invoice-info-text">
                        <span class="invoice-info-label">{{ __('admin.shipping_agent') }}</span>
                        <span class="invoice-info-value">{{ $booking->shippingAgent?->title ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Invoice Section --}}
<div class="invoice-section-card mb-4">
    <div class="invoice-section-head invoice-section-head--green">
        <span class="invoice-section-icon invoice-section-icon--green"><i class="fas fa-receipt"></i></span>
        <h2 class="invoice-section-title">{{ __('admin.bill_type_invoice') }}</h2>
    </div>
    <div class="invoice-section-body">
        <div class="invoice-subsection mb-4">
            <h3 class="invoice-subsection-title">
                <span class="invoice-subsection-icon invoice-subsection-icon--blue"><i class="fas fa-truck"></i></span>
                {{ __('admin.transportation_details') }}
            </h3>
            <div class="table-responsive rounded border invoice-table-wrap">
                @include('admin.components.booking-containers.table')
            </div>
        </div>
        <hr class="invoice-divider">
        <div class="invoice-subsection">
            <h3 class="invoice-subsection-title">
                <span class="invoice-subsection-icon invoice-subsection-icon--teal"><i class="fas fa-list-check"></i></span>
                {{ __('admin.taxed_services') }}
            </h3>
            <div class="table-responsive rounded border invoice-table-wrap">
                @include('admin.components.booking-services.table', ['booking_services' => $booking->taxed_services])
            </div>
        </div>
    </div>
</div>

{{-- Attachments Section --}}
<div class="invoice-section-card mb-4">
    <div class="invoice-section-head invoice-section-head--orange">
        <span class="invoice-section-icon invoice-section-icon--orange"><i class="fas fa-paperclip"></i></span>
        <h2 class="invoice-section-title">{{ __('admin.attachments') }}</h2>
    </div>
    <div class="invoice-section-body">
        <h3 class="invoice-subsection-title">
            <span class="invoice-subsection-icon invoice-subsection-icon--orange"><i class="fas fa-tags"></i></span>
            {{ __('admin.untaxed_services') }}
        </h3>
        <div class="table-responsive rounded border invoice-table-wrap">
            @include('admin.components.booking-services.table', ['booking_services' => $booking->untaxed_services, 'expensesServices' => $booking->expenses])
        </div>
    </div>
</div>

<style>
.invoice-page-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #3d7ab5 100%);
    border-radius: 14px;
    padding: 1.5rem 2rem;
    box-shadow: 0 6px 24px rgba(30, 58, 95, 0.25);
}
.invoice-page-header-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.invoice-header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.4);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.invoice-header-icon i {
    font-size: 1.75rem;
    color: #fff;
}
.invoice-page-title {
    color: #fff;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
}
.invoice-page-subtitle {
    color: rgba(255,255,255,0.95);
    font-size: 0.95rem;
    margin: 0;
}
.invoice-back-btn {
    background: #fff;
    color: #1e3a5f;
    padding: 0.6rem 1.25rem;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: transform 0.2s, box-shadow 0.2s;
}
.invoice-back-btn:hover { color: #1e3a5f; text-decoration: none; transform: translateX(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

.invoice-section-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 14px rgba(0,0,0,0.06);
    border: 1px solid #e8ecf1;
    overflow: hidden;
}
.invoice-section-head {
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 2px solid transparent;
}
.invoice-section-head--blue { background: #e8f4fc; border-color: #0d6efd; }
.invoice-section-head--green { background: #e8f5e9; border-color: #198754; }
.invoice-section-head--orange { background: #fff3e6; border-color: #fd7e14; }
.invoice-section-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    color: #fff;
}
.invoice-section-icon--blue { background: #0d6efd; }
.invoice-section-icon--green { background: #198754; }
.invoice-section-icon--orange { background: #fd7e14; }
.invoice-section-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: #1a1d21;
}
.invoice-section-body { padding: 1.5rem; }

.invoice-info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e8ecf1;
    height: 100%;
}
.invoice-info-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: #fff;
    flex-shrink: 0;
}
.invoice-info-icon--blue { background: #0d6efd; }
.invoice-info-icon--green { background: #198754; }
.invoice-info-icon--orange { background: #fd7e14; }
.invoice-info-icon--teal { background: #0dcaf0; }
.invoice-info-icon--purple { background: #6f42c1; }
.invoice-info-text { min-width: 0; }
.invoice-info-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #5c6370;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 0.25rem;
}
.invoice-info-value {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1a1d21;
}

.invoice-subsection { margin-bottom: 0; }
.invoice-subsection-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1a1d21;
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.invoice-subsection-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #fff;
}
.invoice-subsection-icon--blue { background: #0d6efd; }
.invoice-subsection-icon--teal { background: #0dcaf0; }
.invoice-subsection-icon--orange { background: #fd7e14; }
.invoice-divider { border-color: #e8ecf1; margin: 1.5rem 0; }
.invoice-table-wrap { border-color: #e8ecf1 !important; }

@media (max-width: 768px) {
    .invoice-header-icon { width: 48px; height: 48px; }
    .invoice-header-icon i { font-size: 1.4rem; }
    .invoice-page-title { font-size: 1.25rem; }
}
</style>
