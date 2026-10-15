@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
<div class="card card-custom gutter-b lft-simple-card">
        <div class="card-header">
            <div class="card-title">تعديل مورد</div>
            <div class="card-toolbar">
                <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        @include('admin.suppliers.form')
    </div>
    </div>
@endsection
