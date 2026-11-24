@extends('layouts.admin')
@section('content')
@php $isEdit = $trip->exists; @endphp
<div class="container-fluid lft-simple-page">
    @include('layouts.includes.breadcrumb', ['page' => $isEdit ? 'تعديل رحلة براد' : 'رحلة براد جديدة'])
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header border-0">
            <h3 class="card-label mb-0">{{ $isEdit ? 'تعديل رحلة #'.$trip->id : 'رحلة براد جديدة' }}</h3>
            <div class="card-toolbar">
                <a href="{{ route('reefer_trips.index') }}" class="btn btn-light">{{ __('main.back') }}</a>
            </div>
        </div>
        <div class="card-body">
            <form method="post" action="{{ $isEdit ? route('reefer_trips.update', $trip) : route('reefer_trips.store') }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <div class="row">
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">إذن شحن موجود</label>
                        <select name="reefer_shipment_id" class="form-control">
                            <option value="">—</option>
                            @foreach($shipments as $shipment)
                                <option value="{{ $shipment->id }}" @selected((int)old('reefer_shipment_id', $trip->reefer_shipment_id) === (int)$shipment->id)>
                                    {{ $shipment->permit_number }} ({{ $shipment->destination_country }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">أو إذن شحن جديد</label>
                        <input type="text" name="new_permit_number" class="form-control" value="{{ old('new_permit_number') }}" placeholder="رقم إذن الشحن">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">عدد البرادات المخطط</label>
                        <input type="number" min="1" name="planned_count" class="form-control" value="{{ old('planned_count', 1) }}">
                    </div>

                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">التاريخ</label>
                        <input type="date" name="trip_date" class="form-control" value="{{ old('trip_date', optional($trip->trip_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">موقع التحميل</label>
                        <input type="text" name="loading_site" class="form-control" value="{{ old('loading_site', $trip->loading_site) }}" placeholder="دمياط / أكتوبر / العاشر">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">الوجهة/الدولة</label>
                        <input type="text" name="destination_country" class="form-control" value="{{ old('destination_country', $trip->destination_country) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">المصنع</label>
                        <input type="text" name="factory_name" class="form-control" value="{{ old('factory_name', $trip->factory_name) }}">
                    </div>

                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">اسم السائق</label>
                        <input type="text" name="driver_name" class="form-control" value="{{ old('driver_name', $trip->driver_name) }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">رقم الجواز</label>
                        <input type="text" name="driver_passport" class="form-control" value="{{ old('driver_passport', $trip->driver_passport) }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">رقم التليفون</label>
                        <input type="text" name="driver_phone" class="form-control" value="{{ old('driver_phone', $trip->driver_phone) }}">
                    </div>

                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">رقم السيارة (رأس)</label>
                        <input type="text" name="car_number" class="form-control" value="{{ old('car_number', $trip->car_number) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">رقم المقطورة</label>
                        <input type="text" name="trailer_number" class="form-control" value="{{ old('trailer_number', $trip->trailer_number) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">الديل الأجنبي</label>
                        <input type="text" name="foreign_trailer" class="form-control" value="{{ old('foreign_trailer', $trip->foreign_trailer) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">البوليصة</label>
                        <input type="text" name="bol_number" class="form-control" value="{{ old('bol_number', $trip->bol_number) }}">
                    </div>

                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">داتا لوجر 1</label>
                        <input type="text" name="data_logger_1" class="form-control" value="{{ old('data_logger_1', $trip->data_logger_1) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">داتا لوجر 2</label>
                        <input type="text" name="data_logger_2" class="form-control" value="{{ old('data_logger_2', $trip->data_logger_2) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">الشهادة</label>
                        <input type="text" name="certificate_no" class="form-control" value="{{ old('certificate_no', $trip->certificate_no) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">المندوب (خصم العهدة)</label>
                        <select name="agent_id" class="form-control">
                            <option value="">—</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" @selected((int)old('agent_id', $trip->agent_id)===(int)$agent->id)>
                                    {{ $agent->name }} (EGP {{ number_format((float)$agent->wallet,2) }} / USD {{ number_format((float)($agent->wallet_usd ?? 0),2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">عهدة جنيه</label>
                        <input type="number" step="0.01" min="0" name="custody_egp" class="form-control" value="{{ old('custody_egp', $trip->custody_egp ?? 0) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold">عهدة دولار</label>
                        <input type="number" step="0.01" min="0" name="custody_usd" class="form-control" value="{{ old('custody_usd', $trip->custody_usd ?? 0) }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">ملاحظات</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes', $trip->notes) }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary font-weight-bolder">{{ __('admin.save') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
