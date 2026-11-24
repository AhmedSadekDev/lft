@extends('layouts.admin')
@section('content')
<div class="container-fluid lft-simple-page">
    @include('layouts.includes.breadcrumb', ['page' => __('main.follow_ups')])

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header border-0">
            <h3 class="card-label mb-0">سجل المتابعة</h3>
            <div class="card-toolbar">
                <a href="{{ route('follow_ups.index') }}" class="btn btn-light">{{ __('main.back') }}</a>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-4">
                @if($container)
                    <div><strong>الحاوية:</strong> {{ $container->container_no ?: '—' }}</div>
                    <div><strong>الطلب:</strong>
                        <a href="{{ route('bookings.show', $container->booking_id) }}">{{ $container->booking?->booking_number }}</a>
                    </div>
                @endif
                @if($policy)
                    <div><strong>البوليصة:</strong> #{{ $policy->id }} — {{ $policy->driver?->name }} / {{ $policy->car?->car_number }}</div>
                @endif
                @if($reeferTrip)
                    <div><strong>رحلة براد:</strong> #{{ $reeferTrip->id }} — {{ $reeferTrip->foreign_trailer }}</div>
                @endif
            </div>

            @if(auth()->user()->hasPermissionTo('follow_ups.manage'))
            <form method="post" action="{{ route('follow_ups.store') }}" class="row mb-5 p-4" style="background:#f4f7fb;border-radius:12px;">
                @csrf
                <input type="hidden" name="booking_container_id" value="{{ $container?->id }}">
                <input type="hidden" name="delivery_policy_id" value="{{ $policy?->id }}">
                <input type="hidden" name="reefer_trip_id" value="{{ $reeferTrip?->id }}">
                <div class="form-group col-md-4">
                    <label class="font-weight-bold required-field">الحالة</label>
                    <input type="text" name="status_text" class="form-control" required placeholder="مثال: في المصنع / في الجمرك / في الطريق">
                </div>
                <div class="form-group col-md-3">
                    <label class="font-weight-bold">وقت الحدث (اختياري)</label>
                    <input type="datetime-local" name="occurred_at" class="form-control">
                    <small class="text-muted">لو فاضي = الوقت الحالي</small>
                </div>
                <div class="form-group col-md-3">
                    <label class="font-weight-bold">ملاحظات</label>
                    <input type="text" name="notes" class="form-control">
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary btn-block font-weight-bolder">تسجيل</button>
                </div>
            </form>
            @endif

            <table class="table table-responsive-xl no-datatable">
                <thead class="thead-light">
                    <tr>
                        <th>الحالة</th>
                        <th>وقت الحدث</th>
                        <th>وقت التسجيل</th>
                        <th>بواسطة</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $row)
                        <tr>
                            <td>{{ $row->status_text }}</td>
                            <td>{{ optional($row->occurred_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ optional($row->recorded_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $row->creator?->name ?? '—' }}</td>
                            <td>{{ $row->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">لا توجد تحديثات بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
