@extends('layouts.admin')

@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('admin.vaults')])

        <div class="card card-custom">
            <div class="card-header">
                <h3 class="card-title">{{ __('admin.edit') }}</h3>
            </div>
            <form method="POST" action="{{ route('vaultransactions.update', $item->id) }}">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label>{{ __('admin.name') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $item->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label>{{ __('main.amount') }}</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $item->amount) }}" required>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
                    <a href="{{ route('vaultransactions.index') }}" class="btn btn-secondary">{{ __('admin.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection


