@extends("layouts.admin")
@section("content")
    
    <div class="container-fluid lft-simple-page">
<!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.add_financial_custody_agent') }}
            </div>
        </div>
        @include('admin.financial_custody_agents.form')
    </div>
    </div>
@endsection
