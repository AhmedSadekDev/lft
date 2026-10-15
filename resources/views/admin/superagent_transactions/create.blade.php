@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
<div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.car_shipments')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->

                    <a href="{{ route('superagent_transactions.index', request()->id) }}" class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>{{ __('main.back') }}
                    </a>

                    <!--end::Button-->
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('superagent_transactions.store', request()->id) }}" method="post" enctype='multipart/form-data'>
                    @csrf
                   
                   {{--
                    <div class="form-group">
                      <label for="superagentSelect">{{ __('admin.superagent') }}</label>
                        <select class="form-control" id="superagentSelect" name="superagent_id" required>
                            @foreach($superagents as $superagent)
                            <option value="{{ $superagent->id }}" @if(old('superagent_id') == $superagent->id || request()->id == $superagent->id) selected @endif>{{ $superagent->name }}</option>
                            @endforeach
                        </select>
                        @error('superagent_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    --}}
                    
                    
                    
                    <div class="form-group">
                      <label for="nameInput">{{ __('admin.name') }}</label>
                        <input class="form-control" id="nameInput" type="text" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                   
                    <div class="form-group">
                      <label for="valueInput">{{ __('admin.value') }}</label>
                        <input class="form-control" id="valueInput" type="number" name="amount" value="{{ old('amount') }}" required>
                        @error('amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                   
                    <div class="form-group">
                      <label for="imageInput">{{ __('admin.image') }}</label>
                        <input class="form-control" id="imageInput" type="file" name="image" required>
                        @error('image')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
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
