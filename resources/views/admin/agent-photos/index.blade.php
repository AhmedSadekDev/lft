@extends('layouts.admin')
@section('content')
<div class="container">
    @include('layouts.includes.breadcrumb', ['page' => 'صور المناديب'])
    <div class="card card-custom">
        <div class="card-header align-items-center">
            <h3 class="card-title">صور المناديب <span class="badge badge-light-primary mx-2">{{ $photos->total() }}</span></h3>
        </div>
        <div class="card-body">
            <p class="text-muted">صور عامة رفعها المناديب، مثل تسليم الإفراجات والمستندات، غير مرتبطة بطلبات أو حاويات.</p>
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            <form method="GET" action="{{ route('agent-photos.index') }}" class="row mb-5">
                <div class="col-md-4 form-group">
                    <label for="photo-agent">المندوب</label>
                    <select id="photo-agent" name="agent_id" class="form-control">
                        <option value="">كل المناديب</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" @selected((string) request('agent_id') === (string) $agent->id)>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-group"><label for="photo-from">من تاريخ</label><input id="photo-from" type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
                <div class="col-md-2 form-group"><label for="photo-to">إلى تاريخ</label><input id="photo-to" type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
                <div class="col-md-4 form-group align-self-end">
                    <button class="btn btn-primary" type="submit">عرض</button>
                    <a class="btn btn-light" href="{{ route('agent-photos.index') }}">إلغاء الفلاتر</a>
                </div>
            </form>
            <div class="row">
                @forelse($photos as $photo)
                    <div class="col-sm-6 col-lg-3 mb-5">
                        <div class="card h-100 border">
                            <a href="{{ route('agent-photos.image', $photo) }}" target="_blank" rel="noopener" aria-label="عرض الصورة كاملة">
                                <img src="{{ route('agent-photos.image', $photo) }}" alt="{{ $photo->original_name }}" loading="lazy" class="card-img-top" style="height:220px;object-fit:contain;background:#f3f6f9">
                            </a>
                            <div class="card-body p-4">
                                <strong>{{ $photo->agent?->name ?? 'مندوب غير متاح' }}</strong>
                                <div class="text-muted mt-2">{{ $photo->created_at->format('Y-m-d H:i') }}</div>
                                <div class="text-truncate mt-2" title="{{ $photo->original_name }}">{{ $photo->original_name }}</div>
                                <a class="btn btn-sm btn-light-primary mt-3" href="{{ route('agent-photos.image', $photo) }}" target="_blank" rel="noopener">عرض الصورة</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted py-10">لا توجد صور{{ request()->filled('agent_id') || request()->filled('from') || request()->filled('to') ? ' مطابقة للبحث' : ' مرفوعة حتى الآن' }}.</div>
                @endforelse
            </div>
            {{ $photos->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection
