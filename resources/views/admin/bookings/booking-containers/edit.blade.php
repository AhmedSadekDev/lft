@extends("layouts.admin")
@section("content")
    
    <div class="container-fluid lft-simple-page">
<!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.edit_container_information') }}
            </div>
        </div>
        @include('admin.components.booking-containers.form')
    </div>
    </div>
@endsection
