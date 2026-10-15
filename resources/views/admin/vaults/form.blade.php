@if ($method == 'POST')
    {!! Form::open([
        'url' => $action,
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
        'class' => 'lft-simple-form',
    ]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($vault, [
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
            <div class="form-group mb-0">
                <label for="amount">{{ __('main.amount') }}</label>
                <input
                    id="amount"
                    value="{{ isset($vault) ? old('amount', $vault->amount) : old('amount') }}"
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
    @else
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-edit ml-1"></i>
            {{ __('admin.update') }}
        </button>
    @endif
    <a href="{{ route('vaults.index') }}" class="btn btn-secondary">
        <i class="fas fa-times ml-1"></i>
        {{ __('main.back') }}
    </a>
</div>

{!! Form::close() !!}
