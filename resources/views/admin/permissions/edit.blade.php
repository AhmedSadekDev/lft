@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
<div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.permissions')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->

                    <a href="{{ route('permissions.index') }}" class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>{{ __('main.back') }}
                    </a>

                    <!--end::Button-->
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('permissions.index') }}" class="btn btn-secondary btn-sm">
                        {{ __('main.back') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('permissions.update', $role->id) }}" method="post">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <input class="form-control" type="text" name="name" placeholder="{{ __('admin.name') }}"
                            value="{{ old('name') ?? $role->name }}" required>
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <lable class="mb-2">{{ __('main.permissions') }}:</lable>
                    <div class="row">

                        @foreach ($permissions as $permission)
                            <div class="form-check col-3 mb-3">
                                <input id="permission{{ $permission->id }}" class="form-check-input" type="checkbox"
                                    name="permissions[]" value="{{ $permission->id }}"
                                    @if ($role->permissions->contains('id', $permission->id)) checked @endif>
                                <label for="permission{{ $permission->id }}"
                                    class="form-check-label">{{ __('admin.' . $permission->name) }}</label>
                            </div>
                        @endforeach
                    </div>


                    <div class="d-flex justify-content-end my-3">
                        <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
        <!--end::Card-->
    </div>
    </div>
@endsection
