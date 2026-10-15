@extends("layouts.admin")
@section("content")
    
    <div class="container-fluid lft-simple-page">
<!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header">
            <div class="card-title">
                إضافة شركة خاصة جديدة
            </div>
            <div class="card-toolbar">
                <a href="{{ route('private-companies.index') }}" class="btn btn-secondary btn-sm">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        @include('admin.private-companies.form')
    </div>
    </div>
@endsection
