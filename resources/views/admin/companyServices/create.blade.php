@extends("layouts.admin")
@section("content")
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.add_new_service') }}
            </div>
            <div class="mt-3">
                <a href="{{ route('companyServices.index', $company->id) }}" class="btn btn-secondary float-right">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        @include('admin.companyServices.form')
    </div>
@endsection
