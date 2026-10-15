@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
<!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.add_new_container') }}

            </div>
            <div class="card-toolbar">
                <a href="{{ route('containers.index') }}" class="btn btn-secondary btn-sm">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        @include('admin.containers.form')
    </div>
    </div>
@endsection
