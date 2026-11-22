@extends('layouts.admin')
@section('content')
<div class="container-fluid lft-simple-page">
    @include('layouts.includes.breadcrumb', ['page' => __('main.usd_vault_transactions')])
    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header border-0">
            <h3 class="card-label mb-0">{{ __('main.usd_vault_transactions') }}</h3>
            <div class="card-toolbar">
                <a href="{{ route('usd_vault.index') }}" class="btn btn-light">{{ __('main.back') }}</a>
            </div>
        </div>
        <div class="card-body">
            <form method="get" class="mb-4 row">
                <div class="col-md-2">
                    <select name="direction" class="form-control">
                        <option value="">الاتجاه</option>
                        <option value="in" @selected(($filters['direction'] ?? '')==='in')>وارد</option>
                        <option value="out" @selected(($filters['direction'] ?? '')==='out')>منصرف</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="category" class="form-control" placeholder="التصنيف" value="{{ $filters['category'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary">تصفية</button>
                </div>
            </form>
            <table class="table table-responsive-xl no-datatable">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>الاتجاه</th>
                        <th>التصنيف</th>
                        <th>المبلغ</th>
                        <th>المندوب</th>
                        <th>البنك</th>
                        <th>التاريخ</th>
                        <th>بواسطة</th>
                        <th>البيان</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                        <tr>
                            <td>{{ $tx->id }}</td>
                            <td>{{ $tx->direction === 'in' ? 'وارد' : 'منصرف' }}</td>
                            <td>{{ $tx->category }}</td>
                            <td>${{ number_format((float)$tx->amount, 2) }}</td>
                            <td>{{ $tx->agent?->name ?? '—' }}</td>
                            <td>{{ $tx->bank?->name ?? '—' }}</td>
                            <td>{{ optional($tx->occurred_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $tx->creator?->name ?? '—' }}</td>
                            <td>{{ $tx->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
