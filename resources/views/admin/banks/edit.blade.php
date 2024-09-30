@extends("layouts.admin")
@section("content")
    <!--begin::Bankd-->
    <div class="bankd bankd-custom gutter-b">
        <div class="bankd-header">
            <div class="bankd-title">
                {{ __('main.banks') }}
            </div>
        </div>
        @include('admin.banks.form')
    </div>
@endsection
