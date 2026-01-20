@extends("layouts.admin")
@section("content")
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                {{ __('Send FCM Notification') }}
            </div>
            <div class="mt-3">
                <a href="{{ route('main') }}" class="btn btn-secondary float-right">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        {!! Form::open(['url' => $action, 'method' => $method]) !!}
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("token", "FCM Token", ["class" => "required-field"]) !!}
                        {!! Form::text('token', old('token'), ["class" => "form-control", "id" => "token", "placeholder" => "Enter FCM Device Token"]) !!}
                        @error('token')
                            <small class="alert text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-12 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("title", "Title", ["class" => "required-field"]) !!}
                        {!! Form::text('title', old('title'), ["class" => "form-control", "id" => "title", "placeholder" => "Enter Notification Title"]) !!}
                        @error('title')
                            <small class="alert text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-12 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("body", "Body", ["class" => "required-field"]) !!}
                        {!! Form::textarea('body', old('body'), ["class" => "form-control", "id" => "body", "rows" => "4", "placeholder" => "Enter Notification Body"]) !!}
                        @error('body')
                            <small class="alert text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer">
            {!! Form::submit("Send Notification", ["class" => "btn btn-primary btn-md text-uppercase font-weight-bold chat-send py-2 px-6"]) !!}
        </div>
        {!! Form::close() !!}
    </div>
@endsection
