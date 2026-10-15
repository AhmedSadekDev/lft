@extends("layouts.admin")
@section("content")
    
    <div class="container-fluid lft-simple-page">
<!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.add_new_yard') }}
            </div>
            <div class="card-toolbar">
                <a href="{{ route('yards.index') }}" class="btn btn-secondary btn-sm">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        @include('admin.yards.form')
    </div>
    </div>
@endsection
