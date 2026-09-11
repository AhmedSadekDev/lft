@extends('layouts.admin')
@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">إضافة إيصال</div>
            <div class="mt-3">
                <a href="{{ route('receipts.index') }}" class="btn btn-secondary float-right">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        @include('admin.receipts.form')
    </div>
@endsection
