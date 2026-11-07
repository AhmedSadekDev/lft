@extends('layouts.admin')

@section('content')
<div class="container-fluid lft-simple-page">
    @include('layouts.includes.breadcrumb', ['page' => 'معاينة TABLE للفواتير الإلكترونية'])

    <div class="card card-custom gutter-b">
        <div class="card-header py-5">
            <div class="card-title mb-0">
                <h3 class="card-label mb-0">TABLE — {{ $count }} فاتورة</h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('e-invoices.index') }}" class="btn btn-light-primary">رجوع للقائمة</a>
            </div>
        </div>
        <div class="card-body">
            @if(!empty($errors))
                <div class="alert alert-warning">
                    <div class="font-weight-bold mb-2">تحذيرات أثناء الجلب من ETA:</div>
                    <ul class="mb-0">
                        @foreach($errors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            @foreach($headers as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{{ is_scalar($cell) || $cell === null ? $cell : json_encode($cell, JSON_UNESCAPED_UNICODE) }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ max(count($headers), 1) }}" class="text-center text-muted">لا توجد صفوف</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
