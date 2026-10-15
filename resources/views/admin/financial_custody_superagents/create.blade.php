@extends("layouts.admin")
@section("content")
    
    <div class="container-fluid lft-simple-page">
<!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card">
        <div class="card-header">
            <div class="card-title">
                {{ __('main.financial_custody_superagents') }}
            </div>
        </div>
        @include('admin.financial_custody_superagents.form')
    </div>
    </div>
@endsection
