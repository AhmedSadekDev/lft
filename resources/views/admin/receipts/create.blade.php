@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
<div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header">
            <div class="card-title">إضافة إيصال</div>
            <div class="card-toolbar">
                <a href="{{ route('receipts.index') }}" class="btn btn-secondary btn-sm">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        @include('admin.receipts.form')
    </div>
    </div>
@endsection
