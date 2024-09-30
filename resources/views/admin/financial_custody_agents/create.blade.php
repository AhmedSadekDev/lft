@extends("layouts.admin")
@section("content")
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.add_financial_custody_agent') }}
            </div>
        </div>
        @include('admin.financial_custody_agents.form')
    </div>
@endsection
