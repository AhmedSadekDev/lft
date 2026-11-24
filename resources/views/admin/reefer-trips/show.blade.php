@extends('layouts.admin')
@section('content')
<div class="container-fluid lft-simple-page">
    @include('layouts.includes.breadcrumb', ['page' => 'رحلة براد #'.$trip->id])
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header border-0">
            <h3 class="card-label mb-0">رحلة براد #{{ $trip->id }}</h3>
            <div class="card-toolbar" style="gap:8px;">
                <a href="{{ route('follow_ups.show', ['reefer_trip_id'=>$trip->id]) }}" class="btn btn-light-primary">المتابعة</a>
                @if(auth()->user()->hasPermissionTo('reefer_trips.manage'))
                    <a href="{{ route('reefer_trips.edit', $trip) }}" class="btn btn-light-warning">تعديل</a>
                @endif
                <a href="{{ route('reefer_trips.index') }}" class="btn btn-light">{{ __('main.back') }}</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3"><strong>إذن الشحن:</strong> {{ $trip->shipment?->permit_number ?? '—' }}</div>
                <div class="col-md-3"><strong>الديل الأجنبي:</strong> {{ $trip->foreign_trailer ?: '—' }}</div>
                <div class="col-md-3"><strong>السائق:</strong> {{ $trip->driver_name ?: ($trip->driver?->name ?? '—') }}</div>
                <div class="col-md-3"><strong>الجواز:</strong> {{ $trip->driver_passport ?: '—' }}</div>
                <div class="col-md-3"><strong>التليفون:</strong> {{ $trip->driver_phone ?: '—' }}</div>
                <div class="col-md-3"><strong>العربية:</strong> {{ $trip->car_number ?: '—' }}</div>
                <div class="col-md-3"><strong>المقطورة:</strong> {{ $trip->trailer_number ?: '—' }}</div>
                <div class="col-md-3"><strong>البوليصة:</strong> {{ $trip->bol_number ?: '—' }}</div>
                <div class="col-md-3"><strong>موقع التحميل:</strong> {{ $trip->loading_site ?: '—' }}</div>
                <div class="col-md-3"><strong>الوجهة:</strong> {{ $trip->destination_country ?: '—' }}</div>
                <div class="col-md-3"><strong>عهدة EGP:</strong> {{ number_format((float)$trip->custody_egp, 2) }}</div>
                <div class="col-md-3"><strong>عهدة USD:</strong> ${{ number_format((float)$trip->custody_usd, 2) }}</div>
                <div class="col-md-6"><strong>الحالة الحالية:</strong> {{ $trip->current_status ?: '—' }}</div>
            </div>

            @if(auth()->user()->hasPermissionTo('reefer_trips.manage'))
            <h5 class="font-weight-bolder mb-3">تحديث المسار</h5>
            <form method="post" action="{{ route('reefer_trips.timeline', $trip) }}" class="mb-5">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">الحالة النصية</label>
                        <input type="text" name="current_status" class="form-control" value="{{ $trip->current_status }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">ميناء المغادرة</label>
                        <input type="text" name="departure_port" class="form-control" value="{{ $trip->departure_port }}" placeholder="سفاجا / نويبع">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">ملاحظات</label>
                        <input type="text" name="notes" class="form-control">
                    </div>
                    @foreach($timelineFields as $field => $label)
                        <div class="form-group col-md-3">
                            <label class="font-weight-bold">{{ $label }}</label>
                            <input type="datetime-local" name="{{ $field }}" class="form-control"
                                   value="{{ $trip->{$field} ? $trip->{$field}->format('Y-m-d\TH:i') : '' }}">
                        </div>
                    @endforeach
                    <div class="form-group col-md-12">
                        <label class="checkbox">
                            <input type="checkbox" name="log_follow_up" value="1" checked> تسجيل في سجل المتابعة
                        </label>
                    </div>
                </div>
                <button class="btn btn-primary font-weight-bolder">حفظ المسار</button>
            </form>
            @endif

            <h5 class="font-weight-bolder mb-3">سجل المتابعة</h5>
            <table class="table no-datatable">
                <thead class="thead-light">
                    <tr><th>الحالة</th><th>وقت الحدث</th><th>التسجيل</th><th>بواسطة</th><th>ملاحظات</th></tr>
                </thead>
                <tbody>
                    @forelse($trip->followUps as $row)
                        <tr>
                            <td>{{ $row->status_text }}</td>
                            <td>{{ optional($row->occurred_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ optional($row->recorded_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $row->creator?->name ?? '—' }}</td>
                            <td>{{ $row->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">لا تحديثات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
