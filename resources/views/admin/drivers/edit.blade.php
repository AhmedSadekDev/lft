@extends("layouts.admin")
@section("content")
    
    <div class="container-fluid lft-simple-page">
<!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.edit_driver') }}
            </div>
            <div class="card-toolbar">
                <a href="{{ route('drivers.index') }}" class="btn btn-secondary btn-sm">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        @include('admin.drivers.form')
    </div>
    </div>
@endsection
