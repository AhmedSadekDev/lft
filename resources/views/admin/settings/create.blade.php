@extends("layouts.admin")
@section("content")
    
    <div class="container-fluid lft-simple-page">
<!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.add_new_setting') }}
            </div>
        </div>
        @include('admin.settings.form')
    </div>
    </div>
@endsection
