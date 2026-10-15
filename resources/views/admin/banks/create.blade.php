@extends('layouts.admin')
@section('content')
    <div class="container-fluid lft-simple-page">
        @include('layouts.includes.breadcrumb', ['page' => __('admin.add') . ' — ' . __('main.banks')])

        <div class="card card-custom gutter-b lft-simple-card">
            <div class="card-header border-0">
                <div class="card-title mb-0">
                    <h3 class="card-label mb-0">
                        <i class="fas fa-university text-primary ml-2"></i>
                        {{ __('admin.add') }} {{ __('main.bank') }}
                    </h3>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('banks.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-right ml-1"></i>
                        {{ __('main.back') }}
                    </a>
                </div>
            </div>
            @include('admin.banks.form')
        </div>
    </div>
@endsection
