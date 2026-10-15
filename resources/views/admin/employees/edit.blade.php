@extends("layouts.admin")
@section("content")
    
    <div class="container-fluid lft-simple-page">
<!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.edit_employee_information') }}
            </div>
            <div class="card-toolbar">
                <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-sm">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        @include('admin.employees.form')
    </div>
    </div>
@endsection
