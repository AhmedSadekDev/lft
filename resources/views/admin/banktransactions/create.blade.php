@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
        @include('layouts.includes.breadcrumb', ['page' => __('main.banks')])
        <!--begin::Card-->
        <div class="card card-custom gutter-b lft-simple-card">
            <div class="card-header border-0">
                <div class="card-toolbar">
                    <!--begin::Button-->

                    <a href="{{ route('banktransactions.index', request()->id) }}" class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>{{ __('main.back') }}
                    </a>

                    <!--end::Button-->
                </div>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>{{ __('main.there_are_errors') }}</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
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
                        <input class="form-control" id="valueInput" type="number" step="0.01" min="0.01" name="amount"
                            value="{{ old('amount') }}" required>
                        @error('amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="typeSelect">{{ __('admin.type') }}</label>
                        <select id="typeSelect" class="form-control" name="type" required>
                            <option value="0" @selected(old('type') == '0')>{{ __('main.debit_to_vault') }} (بنك ← خزنة)</option>
                            <option value="1" @selected(old('type') == '1')>{{ __('main.credit') }} (من عميل)</option>
                            <option value="2" @selected(old('type') == '2')>{{ __('main.transfer') }} (بنك ← بنك)</option>
                            <option value="3" @selected(old('type', '3') == '3')>{{ __('main.transfer_from_vault') }} (خزنة ← بنك)</option>
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
                        @error('company_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group bankSelectDiv d-none">
                        <label for="bankSelect">{{ __('main.bank_tranferd') }}</label>
                        <select id="bankSelect" class="form-control" name="trans_bank_id">
                            @foreach ($banks as $otherBank)
                                <option value="{{ $otherBank->id }}" @if (old('trans_bank_id') == $otherBank->id) selected @endif>
                                    {{ $otherBank->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('trans_bank_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group vaultInfoDiv d-none">
                        <div class="alert alert-info mb-0">
                            <strong>{{ __('main.vault') }}:</strong>
                            {{ number_format((float) ($vault->amount ?? 0), 2) }} {{ __('main.currency') }}
                            <br>
                            <small>{{ __('main.select_transfer_from_vault_hint') }}</small>
                        </div>
                        @unless($vault)
                            <div class="alert alert-danger mt-2 mb-0">لا توجد خزنة في النظام — أضف خزنة أولاً.</div>
                        @endunless
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
        function syncTypeFields() {
            let value = $('#typeSelect').val();

            $('.bankSelectDiv').toggleClass('d-none', value != 2);
            $('.companySelectDiv').toggleClass('d-none', value != 1);
            $('.vaultInfoDiv').toggleClass('d-none', value != 3);
        }

        $(document).on('change', '#typeSelect', syncTypeFields);
        $(document).ready(syncTypeFields);
    </script>
@endpush
