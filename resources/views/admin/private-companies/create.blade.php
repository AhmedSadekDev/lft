@extends("layouts.admin")
@section("content")
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                إضافة شركة خاصة جديدة
            </div>
            <div class="mt-3">
                <a href="{{ route('private-companies.index') }}" class="btn btn-secondary float-right">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        @include('admin.private-companies.form')
    </div>
@endsection
