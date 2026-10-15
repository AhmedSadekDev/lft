@extends('layouts.admin')
@section('content')
    <div class="container-fluid lft-simple-page">
        @include('layouts.includes.breadcrumb', ['page' => __('main.permissions')])
        <div class="card card-custom gutter-b lft-simple-card">
            <div class="card-header border-0">
                <div class="card-title">
                    <h3 class="card-label">إضافة صلاحية / دور</h3>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('permissions.index') }}" class="btn btn-secondary btn-sm">
                        {{ __('main.back') }}
                    </a>
                </div>
            </div>
            <form action="{{ route('permissions.store') }}" method="post" class="lft-simple-form">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label class="required-field">{{ __('admin.name') }}</label>
                        <input class="form-control" type="text" name="name" placeholder="{{ __('admin.name') }}"
                            value="{{ old('name') }}" required>
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <label class="mb-2 font-weight-bold">{{ __('main.permissions') }}:</label>
                    <div class="row">
                        @foreach ($permissions as $permission)
                            <div class="form-check col-md-3 col-sm-6 mb-3">
                                <input id="permission{{ $permission->id }}" class="form-check-input" type="checkbox"
                                    name="permissions[]" value="{{ $permission->id }}">
                                <label for="permission{{ $permission->id }}"
                                    class="form-check-label">{{ __('admin.' . $permission->name) }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer lft-simple-form-footer">
                    <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
