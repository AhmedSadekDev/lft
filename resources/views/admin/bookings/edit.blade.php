@extends('layouts.admin')
@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        .booking-edit-page { direction: rtl; background: transparent; padding-bottom: 2rem; }
        .booking-edit-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #3d7ab5 100%);
            border-radius: 14px;
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 6px 24px rgba(30, 58, 95, 0.25);
        }
        .booking-edit-header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .booking-edit-icon {
            width: 52px;
            height: 52px;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.4);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .booking-edit-icon i { font-size: 1.6rem; color: #fff; }
        .booking-edit-title { color: #fff; font-size: 1.4rem; font-weight: 700; margin: 0 0 0.25rem 0; }
        .booking-edit-subtitle { color: rgba(255,255,255,0.95); font-size: 0.9rem; margin: 0; }
        .booking-edit-back {
            background: var(--bg-card);
            color: var(--text-primary);
            padding: 0.6rem 1.25rem;
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .booking-edit-back:hover { color: var(--text-primary); text-decoration: none; }

        .booking-edit-card {
            background: var(--bg-card);
            border-radius: 14px;
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        .booking-edit-card .card-body { padding: 1.5rem 2rem; }

        .booking-form-label {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.4rem;
        }
        .booking-form-control {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 0.6rem 1rem;
        }
        .booking-form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        .booking-container-block {
            background: var(--bg-card-hover);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .booking-container-block .form-group label {
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .booking-container-block .form-group label::before {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 0.9rem;
            color: var(--link-color);
        }
        .booking-container-block .form-group:has(select[name*="branch_id"]) label::before { content: "\f1ad"; }
        .booking-container-block .form-group:has(select[name*="container_id"]) label::before { content: "\f466"; }
        .booking-container-block .form-group:has(input[name*="arrival_date"]) label::before { content: "\f073"; }
        .booking-container-block .form-group:has(input[name*="containers_count"]) label::before { content: "\f0ca"; }

    </style>

    <div class="container booking-edit-page">
        @include('layouts.includes.breadcrumb', ['page' => __('admin.edit_booking_information')])

        <div class="booking-edit-header">
            <div class="booking-edit-header-inner">
                <div class="d-flex align-items-center">
                    
                    <div class="mr-3">
                        <h1 class="booking-edit-title">{{ __('admin.edit_booking_information') }}</h1>
                        <p class="booking-edit-subtitle">{{ __('admin.booking_number') }}: {{ $booking->booking_number ?? '-' }}</p>
                    </div>
                </div>
                <a href="{{ route('bookings.show', $booking) }}" class="booking-edit-back">
                    <i class="fas fa-arrow-right"></i>
                    رجوع لتفاصيل الطلب
                </a>
            </div>
        </div>

        <div class="booking-edit-card card card-custom gutter-b">
            <div class="card-header bg-light border-0 py-3">
                <h2 class="card-title mb-0 font-weight-bold text-dark">
                    <i class="fas fa-clipboard-list text-primary ml-2"></i>
                    بيانات الطلب والحاويات
                </h2>
            </div>
            @include('admin.bookings.form')
        </div>
    </div>

@endsection
