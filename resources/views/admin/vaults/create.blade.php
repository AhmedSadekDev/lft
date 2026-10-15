@extends('layouts.admin')
@section('content')
    <div class="container-fluid lft-simple-page">
        <div class="card card-custom gutter-b lft-simple-card lft-simple-card--narrow">
            <div class="card-header border-0">
                <div class="card-title mb-0">
                    <h3 class="card-label mb-0">
                        <i class="fas fa-safe text-primary ml-2"></i>
                        {{ __('main.vaults') }}
                    </h3>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('vaults.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-right ml-1"></i>
                        {{ __('main.back') }}
                    </a>
                </div>
            </div>
            @include('admin.vaults.form')
        </div>
    </div>
@endsection
