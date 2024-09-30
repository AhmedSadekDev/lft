@extends("layouts.admin")
@section("content")
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                {{ __('main.financial_custody_superagents') }}
            </div>
        </div>
        @include('admin.financial_custody_superagents.form')
    </div>
@endsection
