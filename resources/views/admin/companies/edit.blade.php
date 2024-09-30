@extends("layouts.admin")
@section("content")
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.edit_company_information') }}
            </div>
            <div class="mt-3">
                <a href="{{ route('companies.index') }}" class="btn btn-secondary float-right">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        @include('admin.companies.form')
    </div>
@endsection
