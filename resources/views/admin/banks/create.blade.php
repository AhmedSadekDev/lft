@extends("layouts.admin")
@section("content")
    <!--begin::Bankd-->
    <div class="bankd bankd-custom gutter-b">
        <div class="bankd-header">
            <div class="bankd-title mb-3">
                {{ __('main.banks') }}
            </div>
        </div>
        @include('admin.banks.form')
    </div>
@endsection
