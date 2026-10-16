@extends('layouts.admin')
@section('content')
    <div class="container-fluid lft-simple-page">
        @include('layouts.includes.breadcrumb', ['page' => 'تعديل دور'])

        <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide lft-perm-card">
            <div class="card-header border-0">
                <div class="card-title mb-0">
                    <h3 class="card-label mb-0">
                        <i class="fas fa-user-cog text-primary ml-2" aria-hidden="true"></i>
                        تعديل الدور: {{ $role->name }}
                    </h3>
                    <span class="lft-perm-subtitle">عدّل الاسم أو فعّل/عطّل الصلاحيات ثم احفظ</span>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('permissions.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-right ml-1" aria-hidden="true"></i>
                        {{ __('main.back') }}
                    </a>
                </div>
            </div>
            @include('admin.permissions.form', [
                'action' => route('permissions.update', $role->id),
                'permissions' => $permissions,
                'role' => $role,
            ])
        </div>
    </div>
@endsection
