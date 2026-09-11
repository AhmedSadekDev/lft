@extends('layouts.admin')
@section('content')
    <div class="container-fluid invoice-create-page">
        @include('layouts.includes.breadcrumb', ['page' => __('admin.add_new_invoice')])
        @include('admin.components.booking-invoices.listings')
        @include('admin.components.booking-invoices.totals-form')
    </div>
@endsection

@push('styles')
<style>
    .invoice-create-page {
        direction: rtl;
        background: #f5f7fa;
        padding-bottom: 2rem;
    }
    .invoice-create-page .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .invoice-create-page .card-header {
        border-bottom: 1px solid #e4e6ef;
        background: #fff;
        border-radius: 12px 12px 0 0;
        padding: 1rem 1.5rem;
    }
    .invoice-create-page .card-body {
        padding: 1.5rem;
    }
</style>
@endpush
