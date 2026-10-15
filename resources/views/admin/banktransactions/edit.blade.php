@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
        @include('layouts.includes.breadcrumb', ['page' => __('main.banks')])
        <!--begin::Card-->
        <div class="card card-custom gutter-b lft-simple-card">
            <div class="card-header border-0">
                <div class="card-toolbar">
                    <!--begin::Button-->

                    <a href="{{ route('banktransactions.index', $item->bank_id) }}" class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>{{ __('main.back') }}
                    </a>

                    <!--end::Button-->
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('banktransactions.update', $item->id) }}" method="post"
                    enctype="multipart/form-data">
                    @csrf
                    @method('put')
                    <div class="form-group">
                        <label for="nameInput">{{ __('admin.name') }}</label>
                        <input class="form-control" id="nameInput" type="text" name="name"
                            value="{{ old('name') ?? $item->name }}" required>
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>


                    <div class="form-group">
                        <label for="date">{{ __('main.date') }}</label>
                        <input class="form-control" id="date" type="date" name="date"
                            value="{{ old('date') ?? $item->date }}" required>
                        @error('date')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="valueInput">{{ __('main.amount') }}</label>
                        <input class="form-control" id="valueInput" type="number" name="amount"
                            value="{{ old('amount') ?? $item->amount }}" required>
                        @error('amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>{{ __('admin.type') }}</label>
                        <input class="form-control" type="text" value="{{ $item->typeText() }}" disabled>
                        <small class="form-text text-muted">
                            نوع الحركة وطرفها الآخر لا يتغيران بعد التسجيل؛ تعديل المبلغ يُطبَّق على الطرفين معاً
                            (البنك والخزنة أو البنك المحوَّل إليه). لتغيير النوع احذف الحركة وسجّلها من جديد.
                        </small>
                    </div>

                    @if ($item->transBank)
                        <div class="form-group">
                            <label>{{ __('main.bank_tranferd') }}</label>
                            <input class="form-control" type="text" value="{{ $item->transBank->name }}" disabled>
                        </div>
                    @endif

                    @if ($item->company)
                        <div class="form-group">
                            <label>{{ __('main.company') }}</label>
                            <input class="form-control" type="text" value="{{ $item->company->name }}" disabled>
                        </div>
                    @endif



                    <div class="form-group">
                        <label for="additionInput">{{ __('admin.image') }}</label>
                        <input class="form-control" id="additionInput" type="file" name="image">
                        @if ($item->image)
                            <img style="max-width: 100px;" src="{{ asset($item->image) }}" alt="">
                        @endif
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
