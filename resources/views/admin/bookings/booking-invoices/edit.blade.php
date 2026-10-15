@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
@include('admin.components.booking-invoices.listings')
    @include('admin.components.booking-invoices.totals-form')
    </div>
@endsection
