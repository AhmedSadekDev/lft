@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.banks')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->

                    <a href="{{ route('banktransactions.index', request()->id) }}" class="btn btn-primary font-weight-bolder">
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
                <form action="{{ route('banktransactions.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="bank_id" value="{{ $bank->id }}">

                    <div class="form-group">
                        <label for="nameInput">{{ __('main.operation_name') }}</label>
                        <input class="form-control" id="nameInput" type="text" name="name" value="{{ old('name') }}"
                            required>
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="date">{{ __('main.date') }}</label>
                        <input class="form-control" id="date" type="date" name="date" value="{{ old('date') }}"
                            required>
                        @error('date')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>


                    <div class="form-group">
                        <label for="valueInput">{{ __('main.amount') }}</label>
                        <input class="form-control" id="valueInput" type="number" name="amount"
                            value="{{ old('amount') }}" required>
                        @error('amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="typeSelect">{{ __('admin.type') }}</label>
                        <select id="typeSelect" class="form-control" name="type">
                            <option value="0" @if (old('type') == 0 || (isset($vault) && $vault->type == 0)) selected @endif>
                                {{ __('main.debit') }}</option>
                            <option value="1" @if (old('type') == 1 || (isset($vault) && $vault->type == 1)) selected @endif>
                                {{ __('main.credit') }}</option>
                            <option value="2" @if (old('type') == 2 || (isset($vault) && $vault->type == 2)) selected @endif>
                                {{ __('main.transfer') }}</option>
                        </select>
                        @error('type')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group companySelectDiv d-none">
                        <label for="companySelect">{{ __('main.company') }}</label>
                        <select id="companySelect" class="form-control" name="company_id">
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @if (old('company_id') == $company->id) selected @endif>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('trans_bacompany_idnk_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group bankSelectDiv d-none">
                        <label for="bankSelect">{{ __('main.bank_tranferd') }}</label>
                        <select id="bankSelect" class="form-control" name="trans_bank_id">
                            @foreach ($banks as $bank)
                                <option value="{{ $bank->id }}" @if (old('trans_bank_id') == $bank->id) selected @endif>
                                    {{ $bank->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('trans_bank_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>



                    <div class="form-group">
                        <label for="additionInput">{{ __('admin.image') }}</label>
                        <input class="form-control" id="additionInput" type="file" name="image">
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


@push('js')
    <script>
        $(document).on('change', '#typeSelect', function() {
            let value = $(this).val();

            if (value == 2) {
                $('.bankSelectDiv').removeClass('d-none');
            } else {
                $('.bankSelectDiv').addClass('d-none');
            }

            if (value == 1) {
                $('.companySelectDiv').removeClass('d-none');
            } else {
                $('.companySelectDiv').addClass('d-none');
            }
        });
    </script>
@endpush
