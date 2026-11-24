@extends('layouts.admin')
@section('content')
<div class="container-fluid lft-simple-page">
    @include('layouts.includes.breadcrumb', ['page' => __('main.reefer_trips')])
    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header border-0">
            <h3 class="card-label mb-0">{{ __('main.reefer_trips') }}</h3>
            <div class="card-toolbar">
                @if(auth()->user()->hasPermissionTo('reefer_trips.manage'))
                    <a href="{{ route('reefer_trips.create') }}" class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-plus"></i> رحلة جديدة
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <form method="get" class="mb-4 row" style="gap:0;">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" placeholder="بحث" value="{{ $q }}">
                </div>
                <div class="col-md-3">
                    <input type="text" name="country" class="form-control" placeholder="الدولة" value="{{ $country }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary">تصفية</button>
                </div>
            </form>
            <table class="table table-responsive-xl no-datatable">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>إذن الشحن</th>
                        <th>التاريخ</th>
                        <th>الديل الأجنبي</th>
                        <th>السائق</th>
                        <th>العربية</th>
                        <th>الوجهة</th>
                        <th>عهدة EGP/USD</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trips as $trip)
                        <tr>
                            <td>{{ $trip->id }}</td>
                            <td>{{ $trip->shipment?->permit_number ?? '—' }}</td>
                            <td>{{ optional($trip->trip_date)->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $trip->foreign_trailer ?: '—' }}</td>
                            <td>{{ $trip->driver_name ?: ($trip->driver?->name ?? '—') }}</td>
                            <td>{{ $trip->car_number ?: ($trip->car?->car_number ?? '—') }}</td>
                            <td>{{ $trip->destination_country ?: '—' }}</td>
                            <td>{{ number_format((float)$trip->custody_egp, 2) }} / ${{ number_format((float)$trip->custody_usd, 2) }}</td>
                            <td>{{ $trip->current_status ?: '—' }}</td>
                            <td>
                                <a href="{{ route('reefer_trips.show', $trip) }}" class="btn btn-sm btn-light-primary">عرض</a>
                                @if(auth()->user()->hasPermissionTo('reefer_trips.manage'))
                                    <a href="{{ route('reefer_trips.edit', $trip) }}" class="btn btn-sm btn-light-warning">تعديل</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">لا توجد رحلات — ابدأ بإضافة رحلة أو طبّق SQL أولاً</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $trips->links() }}
        </div>
    </div>
</div>
@endsection
