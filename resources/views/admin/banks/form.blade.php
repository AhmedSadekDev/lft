@if ($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype' => 'multipart/form-data', 'files' => true]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($bank, [
        'url' => [$action],
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
    ]) !!}
@endif
<div class="card-body">
    <div class="row justify-content-center me-3">

        <div class="col-12">
            <div class="form-group">
                <label for="name">{{ __('admin.name') }}</label>
                <input id="name" value="{{ isset($bank) ? (old('name') ?? $bank->name) :old('name') }}" class="form-control" type="text" name="name">
                @error('name')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>


        <div class="col-12">
            <div class="form-group">
                <label for="amount">{{ __('main.balance') }}</label>
                <input id="amount" value="{{ isset($bank) ? (old('amount') ?? $bank->amount) :old('amount') }}" class="form-control" type="number" name="amount">
                @error('amount')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="card-footer">
    <div class="row ">

        @if ($method == 'POST')
            {!! Form::submit(__('admin.save'), [
                'class' => 'btn btn-primary btn-md text-uppercase font-weight-bold chat-send py-2 px-6',
            ]) !!}
        @elseif ($method == 'PUT')
            {!! Form::submit(__('admin.update'), ['class' => 'btn btn-primary']) !!}
        @endif
    </div>
</div>

</form>
{!! Form::close() !!}
<!-- /.bankd-body -->
