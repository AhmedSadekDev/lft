@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.car_shipments')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->

                    <a href="{{ route('superagent_transactions.index', request()->id) }}" class="btn btn-primary font-weight-bolder">
                        <span class="svg-icon svg-icon-md">
                            <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px"
                                height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <rect x="0" y="0" width="24" height="24" />
                                    <circle fill="#000000" cx="9" cy="15" r="6" />
                                    <path
                                        d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z"
                                        fill="#000000" opacity="0.3" />
                                </g>
                            </svg>
                            <!--end::Svg Icon-->
                        </span>{{ __('main.back') }}
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
@endsection
