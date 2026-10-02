@extends('layouts.admin')
@section('content')
<style>
    .agent-photos-page .photos-intro {
        color: #7e8299;
        margin-bottom: 0;
    }
    .agent-photos-page .photos-filters {
        background: #f8f9fc;
        border: 1px solid #ebedf3;
        border-radius: .5rem;
        padding: 1.25rem;
    }
    .agent-photos-page .photo-card {
        border-color: #ebedf3;
        border-radius: .5rem;
        overflow: hidden;
        transition: box-shadow .2s ease, transform .2s ease;
    }
    .agent-photos-page .photo-card:hover {
        box-shadow: 0 .5rem 1.5rem rgba(24, 28, 50, .1);
        transform: translateY(-2px);
    }
    .agent-photos-page .photo-preview {
        align-items: center;
        background: #f3f6f9;
        display: flex;
        height: 220px;
        justify-content: center;
        overflow: hidden;
    }
    .agent-photos-page .photo-preview img {
        height: 100%;
        object-fit: contain;
        width: 100%;
    }
    .agent-photos-page .photo-name {
        overflow-wrap: anywhere;
    }
    @media (max-width: 575.98px) {
        .agent-photos-page .photo-preview {
            height: 190px;
        }
    }
</style>
<div class="container">
    @include('layouts.includes.breadcrumb', ['page' => 'صور المناديب'])
    <div class="agent-photos-page">
        <div class="card card-custom">
            <div class="card-header align-items-center flex-wrap py-5">
                <div>
                    <h3 class="card-title mb-2">صور المناديب</h3>
                    <p class="photos-intro">استعرض الصور المرفوعة من المناديب، مع إمكانية تصفيتها حسب المندوب أو التاريخ.</p>
                </div>
                <span class="badge badge-light-primary font-size-h6 px-4 py-3">{{ $photos->total() }} صورة</span>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <form method="GET" action="{{ route('agent-photos.index') }}" class="photos-filters mb-8">
                    <div class="row align-items-end">
                        <div class="col-md-4 form-group mb-md-0">
                            <label for="photo-agent">المندوب</label>
                            <select id="photo-agent" name="agent_id" class="form-control">
                                <option value="">كل المناديب</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected((string) request('agent_id') === (string) $agent->id)>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group mb-md-0">
                            <label for="photo-from">من تاريخ</label>
                            <input id="photo-from" type="date" name="from" value="{{ request('from') }}" class="form-control">
                        </div>
                        <div class="col-md-3 form-group mb-md-0">
                            <label for="photo-to">إلى تاريخ</label>
                            <input id="photo-to" type="date" name="to" value="{{ request('to') }}" class="form-control">
                        </div>
                        <div class="col-md-2 mt-4 mt-md-0 d-flex">
                            <button class="btn btn-primary flex-grow-1" type="submit">تطبيق</button>
                            <a class="btn btn-light mr-2" href="{{ route('agent-photos.index') }}" title="إلغاء الفلاتر" aria-label="إلغاء الفلاتر">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </div>
                </form>
                <div class="row">
                    @forelse($photos as $photo)
                        <div class="col-sm-6 col-lg-4 col-xl-3 mb-5">
                            <div class="card photo-card h-100">
                                <a class="photo-preview" href="{{ route('agent-photos.image', $photo) }}" target="_blank" rel="noopener" aria-label="عرض الصورة كاملة">
                                    <img src="{{ route('agent-photos.image', $photo) }}" alt="{{ $photo->original_name }}" loading="lazy">
                                </a>
                                <div class="card-body d-flex flex-column p-5">
                                    <div class="d-flex align-items-start justify-content-between mb-3">
                                        <strong class="text-dark">{{ $photo->agent?->name ?? 'مندوب غير متاح' }}</strong>
                                        <span class="text-muted font-size-sm text-nowrap mr-2">{{ $photo->created_at->format('Y-m-d H:i') }}</span>
                                    </div>
                                    <div class="photo-name text-muted mb-5" title="{{ $photo->original_name }}">{{ $photo->original_name }}</div>
                                    <div class="d-flex align-items-center justify-content-between mt-auto">
                                        <a class="btn btn-sm btn-light-primary" href="{{ route('agent-photos.image', $photo) }}" target="_blank" rel="noopener">
                                            <i class="fas fa-eye"></i> عرض الصورة
                                        </a>
                                        <form method="POST" action="{{ route('agent-photos.destroy', $photo) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذه الصورة؟ لا يمكن التراجع عن هذا الإجراء.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger" aria-label="حذف الصورة">
                                                <i class="fas fa-trash-alt"></i> حذف
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center text-muted py-15">
                                <i class="far fa-images fa-3x mb-4 d-block"></i>
                                <div class="font-size-h6">لا توجد صور{{ request()->filled('agent_id') || request()->filled('from') || request()->filled('to') ? ' مطابقة للفلاتر' : ' مرفوعة حتى الآن' }}.</div>
                                <div class="mt-2">يمكنك تعديل الفلاتر أو العودة لاحقًا لمراجعة الصور الجديدة.</div>
                            </div>
                        </div>
                    @endforelse
                </div>
                <div class="d-flex justify-content-center mt-3">
                    {{ $photos->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
