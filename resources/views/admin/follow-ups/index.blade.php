@extends('layouts.admin')
@section('content')
<div class="container-fluid lft-simple-page">
    @include('layouts.includes.breadcrumb', ['page' => __('main.follow_ups')])

    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header border-0 flex-wrap">
            <h3 class="card-label mb-0">{{ __('main.follow_ups') }}</h3>
            <div class="card-toolbar" style="gap:8px;">
                @foreach(['all'=>'الكل','loading'=>'تحميل','aging'=>'تعتيق','international'=>'دولي','reefer'=>'برادات'] as $key => $label)
                    <a href="{{ route('follow_ups.index', array_filter(['filter'=>$key,'q'=>$q])) }}"
                       class="btn btn-sm {{ $filter === $key ? 'btn-primary' : 'btn-light' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="card-body">
            <form method="get" class="mb-4 d-flex" style="gap:8px;">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="text" name="q" class="form-control" style="max-width:320px;" placeholder="بحث برقم الحاوية / البوليصة / السائق" value="{{ $q }}">
                <button class="btn btn-primary">بحث</button>
            </form>

            <h5 class="font-weight-bolder mb-3">حاويات / طلبات</h5>
            <table class="table table-responsive-xl no-datatable">
                <thead class="thead-light">
                    <tr>
                        <th>الحاوية</th>
                        <th>الطلب</th>
                        <th>الشركة</th>
                        <th>السائق</th>
                        <th>العربية</th>
                        <th>آخر حالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($containers as $container)
                        @php
                            $policy = $container->delivery_policies->first();
                            $last = $container->followUps->first();
                        @endphp
                        <tr>
                            <td>{{ $container->container_no ?: '—' }}</td>
                            <td>
                                <a href="{{ route('bookings.show', $container->booking_id) }}">
                                    {{ $container->booking?->booking_number ?? $container->booking_id }}
                                </a>
                            </td>
                            <td>{{ $container->booking?->company?->name ?? '—' }}</td>
                            <td>{{ $policy?->driver?->name ?? '—' }}</td>
                            <td>{{ $policy?->car?->car_number ?? '—' }}</td>
                            <td>{{ $last?->status_text ?? '—' }}</td>
                            <td>
                                <a class="btn btn-sm btn-light-primary"
                                   href="{{ route('follow_ups.show', ['booking_container_id' => $container->id, 'delivery_policy_id' => $policy?->id]) }}">
                                    متابعة
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">لا توجد حاويات</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $containers->links() }}

            @if($reeferTrips->isNotEmpty())
                <h5 class="font-weight-bolder mt-5 mb-3">رحلات البرادات</h5>
                <table class="table table-responsive-xl no-datatable">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>الديل الأجنبي</th>
                            <th>السائق</th>
                            <th>العربية</th>
                            <th>الوجهة</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reeferTrips as $trip)
                            <tr>
                                <td>{{ $trip->id }}</td>
                                <td>{{ $trip->foreign_trailer ?: '—' }}</td>
                                <td>{{ $trip->driver_name ?: ($trip->driver?->name ?? '—') }}</td>
                                <td>{{ $trip->car_number ?: ($trip->car?->car_number ?? '—') }}</td>
                                <td>{{ $trip->destination_country ?: '—' }}</td>
                                <td>{{ $trip->current_status ?: '—' }}</td>
                                <td>
                                    <a class="btn btn-sm btn-light-warning" href="{{ route('reefer_trips.show', $trip) }}">التفاصيل</a>
                                    <a class="btn btn-sm btn-light-primary" href="{{ route('follow_ups.show', ['reefer_trip_id' => $trip->id]) }}">متابعة</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
