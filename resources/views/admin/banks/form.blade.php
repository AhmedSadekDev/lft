@if ($method == 'POST')
    {!! Form::open([
        'url' => $action,
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
        'class' => 'lft-simple-form',
    ]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($bank, [
        'url' => [$action],
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
        'class' => 'lft-simple-form',
    ]) !!}
@endif

<div class="card-body">
    <div class="row">
        <div class="col-lg-6 col-md-8">
            <div class="form-group">
                <label for="name">{{ __('admin.name') }}</label>
                <input
                    id="name"
                    value="{{ isset($bank) ? old('name', $bank->name) : old('name') }}"
                    class="form-control"
                    type="text"
                    name="name"
                    required
                    autocomplete="off"
                    placeholder="{{ __('admin.name') }}"
                >
                @error('name')
                    <div class="text-danger mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-lg-6 col-md-8">
            <div class="form-group mb-0">
                <label for="amount">{{ __('main.balance') }}</label>
                <input
                    id="amount"
                    value="{{ isset($bank) ? old('amount', $bank->amount) : old('amount') }}"
                    class="form-control"
                    type="number"
                    name="amount"
                    step="0.01"
                    min="0"
                    required
                    placeholder="0.00"
                >
                @error('amount')
                    <div class="text-danger mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="card-footer lft-simple-form-footer">
    @if ($method == 'POST')
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save ml-1"></i>
            {{ __('admin.save') }}
        </button>
        <a href="{{ route('banks.index') }}" class="btn btn-secondary">
            <i class="fas fa-times ml-1"></i>
            {{ __('main.back') }}
        </a>
    @elseif ($method == 'PUT')
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-edit ml-1"></i>
            {{ __('admin.update') }}
        </button>
        <a href="{{ route('banks.index') }}" class="btn btn-secondary">
            <i class="fas fa-times ml-1"></i>
            {{ __('main.back') }}
        </a>
    @endif
</div>

{!! Form::close() !!}
