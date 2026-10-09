@extends('layouts.admin')
@section('content')
<style>
    .agent-photos-page .photos-intro {
        color: var(--text-muted);
        margin-bottom: 0;
    }
    .agent-photos-page .photos-filters {
        background: var(--surface-secondary);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 1.25rem;
    }
    .agent-photos-page .photo-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        overflow: hidden;
        transition: box-shadow .2s ease, transform .2s ease;
        position: relative;
    }
    .agent-photos-page .photo-card:hover {
        box-shadow: var(--shadow);
        transform: translateY(-2px);
    }
    .agent-photos-page .photo-card.is-selected {
        border-color: var(--primary);
        background-color: var(--primary-soft);
    }
    .agent-photos-page .photo-preview {
        align-items: center;
        background: var(--surface-secondary);
        display: flex;
        height: 200px;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }
    .agent-photos-page .photo-preview img {
        height: 100%;
        object-fit: contain;
        width: 100%;
    }
    .agent-photos-page .photo-checkbox-wrap {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 5;
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 4px 8px;
        border-radius: 4px;
        box-shadow: var(--shadow-sm);
    }
    .agent-photos-page .photo-name {
        overflow-wrap: anywhere;
        font-size: 0.85rem;
        color: var(--text-primary);
    }
    .agent-photos-page .nav-tabs .nav-link {
        font-weight: 600;
        font-size: 1rem;
        padding: 0.75rem 1.25rem;
        color: var(--text-secondary);
    }
    .agent-photos-page .nav-tabs .nav-link.active {
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
    }
    .agent-photos-page .bulk-toolbar {
        background: var(--primary-soft);
        border: 1px solid var(--primary);
        border-radius: var(--radius-sm);
        padding: 0.75rem 1.25rem;
    }
    .search-results-box {
        max-height: 250px;
        overflow-y: auto;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: var(--surface);
        margin-top: 5px;
    }
    .search-result-item {
        padding: 10px 12px;
        border-bottom: 1px solid var(--border-light);
        cursor: pointer;
        transition: background 0.15s;
        color: var(--text-primary);
    }
    .search-result-item:hover, .search-result-item.selected {
        background: var(--surface-secondary);
    }
    .search-result-item:last-child {
        border-bottom: none;
    }
    @media (max-width: 575.98px) {
        .agent-photos-page .photo-preview {
            height: 170px;
        }
    }
</style>

<div class="container">
    @include('layouts.includes.breadcrumb', ['page' => 'صور المناديب'])
    <div class="agent-photos-page">
        <div class="card card-custom">
            <div class="card-header align-items-center flex-wrap py-5">
                <div>
                    <h3 class="card-title mb-2">صور المناديب وتسكين الحجوزات</h3>
                    <p class="photos-intro">استعرض الصور المرفوعة من المناديب، واحذف غير المطلوب، وسكّن كل صورة في الطلب أو الحاوية الخاصة بها.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-light-primary font-size-h6 px-4 py-3">{{ $photos->total() }} صورة معروضة</span>
                </div>
            </div>

            <!-- تبويبات حالة التسكين -->
            <div class="px-7 pt-4">
                <ul class="nav nav-tabs nav-tabs-line mb-0">
                    <li class="nav-item">
                        <a class="nav-link {{ ($status ?? 'all') === 'unassigned' ? 'active' : '' }}" 
                           href="{{ route('agent-photos.index', array_merge(request()->except('page'), ['status' => 'unassigned'])) }}">
                            <i class="fas fa-clock mr-2 text-warning"></i>
                            الصور غير المسكّنة
                            <span class="badge badge-warning ml-2">{{ $unassignedCount ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ ($status ?? 'all') === 'assigned' ? 'active' : '' }}" 
                           href="{{ route('agent-photos.index', array_merge(request()->except('page'), ['status' => 'assigned'])) }}">
                            <i class="fas fa-check-circle mr-2 text-success"></i>
                            الصور المسكّنة
                            <span class="badge badge-success ml-2">{{ $assignedCount ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ ($status ?? 'all') === 'all' ? 'active' : '' }}" 
                           href="{{ route('agent-photos.index', array_merge(request()->except('page'), ['status' => 'all'])) }}">
                            <i class="fas fa-images mr-2 text-primary"></i>
                            جميع الصور
                            <span class="badge badge-light-primary ml-2">{{ $totalCount ?? 0 }}</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <!-- فلاتر البحث -->
                <form method="GET" action="{{ route('agent-photos.index') }}" class="photos-filters mb-6">
                    <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
                    <div class="row align-items-end">
                        <div class="col-md-4 form-group mb-md-0">
                            <label for="photo-agent" class="font-weight-bold">المندوب</label>
                            <select id="photo-agent" name="agent_id" class="form-control">
                                <option value="">كل المناديب</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected((string) request('agent_id') === (string) $agent->id)>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group mb-md-0">
                            <label for="photo-from" class="font-weight-bold">من تاريخ</label>
                            <input id="photo-from" type="date" name="from" value="{{ request('from') }}" class="form-control">
                        </div>
                        <div class="col-md-3 form-group mb-md-0">
                            <label for="photo-to" class="font-weight-bold">إلى تاريخ</label>
                            <input id="photo-to" type="date" name="to" value="{{ request('to') }}" class="form-control">
                        </div>
                        <div class="col-md-2 mt-4 mt-md-0 d-flex">
                            <button class="btn btn-primary flex-grow-1" type="submit">
                                <i class="fas fa-filter"></i> تطبيق
                            </button>
                            <a class="btn btn-light mr-2" href="{{ route('agent-photos.index', ['status' => $status ?? 'all']) }}" title="إلغاء الفلاتر">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </div>
                </form>

                <!-- شريط العمليات الجماعية -->
                <div class="bulk-toolbar mb-6 d-flex flex-wrap align-items-center justify-content-between" id="bulkToolbar" style="display: none !important;">
                    <div class="d-flex align-items-center mb-2 mb-md-0">
                        <div class="custom-control custom-checkbox mr-3">
                            <input type="checkbox" class="custom-control-input" id="selectAllCheckbox">
                            <label class="custom-control-label font-weight-bold" for="selectAllCheckbox">تحديد الكل في هذه الصفحة</label>
                        </div>
                        <span class="badge badge-primary px-3 py-2" id="selectedCountBadge">تم تحديد 0 صورة</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-success btn-sm mr-2" id="bulkAssignBtn">
                            <i class="fas fa-folder-plus"></i> تسكين الصور المحددة في حجز
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn">
                            <i class="fas fa-trash-alt"></i> حذف الصور المحددة
                        </button>
                    </div>
                </div>

                <!-- شبكة الصور -->
                <div class="row">
                    @forelse($photos as $photo)
                        @php
                            $isAssigned = !empty($photo->booking_id);
                            $bookingNum = $photo->booking?->booking_number ?: ($photo->booking_id ? '#' . $photo->booking_id : null);
                            $containerNum = $photo->bookingContainer?->container_no ?: null;
                            $companyName = $photo->booking?->factory?->name ?: ($photo->booking?->company?->name ?: null);
                        @endphp
                        <div class="col-sm-6 col-lg-4 col-xl-3 mb-5">
                            <div class="card photo-card h-100" id="card-photo-{{ $photo->id }}">
                                <div class="photo-checkbox-wrap">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input photo-checkbox" 
                                               id="chk-{{ $photo->id }}" 
                                               value="{{ $photo->id }}"
                                               data-photo-id="{{ $photo->id }}"
                                               data-photo-url="{{ route('agent-photos.image', $photo) }}"
                                               data-photo-name="{{ $photo->original_name }}">
                                        <label class="custom-control-label" for="chk-{{ $photo->id }}"></label>
                                    </div>
                                </div>

                                <a class="photo-preview" href="{{ route('agent-photos.image', $photo) }}" target="_blank" rel="noopener" aria-label="عرض الصورة كاملة">
                                    <img src="{{ route('agent-photos.image', $photo) }}" alt="{{ $photo->original_name }}" loading="lazy">
                                </a>

                                <div class="card-body d-flex flex-column p-4">
                                    <!-- معلومات المندوب والتاريخ -->
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <strong class="text-dark font-size-sm">
                                            <i class="fas fa-user-tie text-muted mr-1"></i> {{ $photo->agent?->name ?? 'مندوب غير متاح' }}
                                        </strong>
                                        <span class="text-muted font-size-xs text-nowrap mr-1">{{ $photo->created_at->format('Y-m-d H:i') }}</span>
                                    </div>

                                    <!-- اسم الصورة -->
                                    <div class="photo-name text-muted mb-2 text-truncate" title="{{ $photo->original_name }}">
                                        <i class="far fa-image mr-1"></i> {{ $photo->original_name }}
                                    </div>

                                    <!-- حالة التسكين -->
                                    <div class="mb-3">
                                        @if($isAssigned)
                                            <div class="d-flex flex-wrap align-items-center">
                                                <span class="badge badge-success font-size-xs px-2 py-1 mr-1 mb-1" title="تم تسكين الصورة في حجز">
                                                    <i class="fas fa-check-circle"></i> حجز {{ $bookingNum }}
                                                </span>
                                                @if($containerNum)
                                                    <span class="badge badge-light-primary font-size-xs px-2 py-1 mr-1 mb-1" title="رقم الحاوية">
                                                        <i class="fas fa-box"></i> {{ $containerNum }}
                                                    </span>
                                                @endif
                                                <a href="{{ route('bookings.booking_papers', $photo->booking_id) }}" 
                                                   target="_blank" 
                                                   class="btn btn-xs btn-light-info mb-1" 
                                                   title="عرض أوراق الحجز">
                                                    <i class="fas fa-external-link-alt"></i> الأوراق
                                                </a>
                                            </div>
                                            @if($companyName)
                                                <div class="text-muted font-size-xs text-truncate mt-1" title="{{ $companyName }}">
                                                    <i class="fas fa-building mr-1"></i> {{ $companyName }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="badge badge-light-warning font-size-xs px-2 py-1">
                                                <i class="fas fa-clock"></i> في انتظار التسكين
                                            </span>
                                        @endif
                                    </div>

                                    <!-- أزرار الإجراءات -->
                                    <div class="mt-auto pt-2 border-top">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <!-- زر التسكين -->
                                            <button type="button" 
                                                    class="btn btn-sm {{ $isAssigned ? 'btn-light-warning' : 'btn-success' }} single-assign-btn" 
                                                    data-toggle="modal"
                                                    data-target="#assignModal"
                                                    data-photo-id="{{ $photo->id }}"
                                                    data-photo-url="{{ route('agent-photos.image', $photo) }}"
                                                    data-photo-name="{{ $photo->original_name }}"
                                                    data-current-booking="{{ $photo->booking_id }}"
                                                    data-current-booking-num="{{ $bookingNum }}"
                                                    title="{{ $isAssigned ? 'تعديل أو إعادة التسكين' : 'تسكين في حجز / حاوية' }}">
                                                <i class="fas fa-folder-plus"></i> {{ $isAssigned ? 'إعادة تسكين' : 'تسكين' }}
                                            </button>

                                            <!-- زر الحذف -->
                                            <form method="POST" action="{{ route('agent-photos.destroy', $photo) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذه الصورة نهائيًا؟');" class="d-inline ml-2">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger" title="حذف الصورة">
                                                    <i class="fas fa-trash-alt"></i> حذف
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center text-muted py-15">
                                <i class="far fa-images fa-4x mb-4 d-block text-secondary"></i>
                                <div class="font-size-h5 font-weight-bold">
                                    @if(($status ?? 'all') === 'unassigned')
                                        رائع! لا توجد صور بانتظار التسكين.
                                    @elseif(($status ?? 'all') === 'assigned')
                                        لا توجد صور مسكّنة مطابقة للفلاتر.
                                    @else
                                        لا توجد صور مرفوعة حتى الآن.
                                    @endif
                                </div>
                                <div class="mt-2 text-muted">يمكنك تعديل الفلاتر أو التبديل بين التبويبات أعلاه.</div>
                            </div>
                        </div>
                    @endforelse
                </div>

                <!-- الترقيم -->
                <div class="d-flex justify-content-center mt-5">
                    {{ $photos->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: تسكين الصورة / الصور في حجز -->
<div class="modal fade" id="assignModal" tabindex="-1" role="dialog" aria-labelledby="assignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="assignForm" method="POST" action="{{ route('agent-photos.assign') }}">
                @csrf
                <div class="modal-header bg-light-primary py-4">
                    <h5 class="modal-title font-weight-bold" id="assignModalLabel">
                        <i class="fas fa-folder-plus text-primary mr-2"></i> تسكين الصور في حجز أو حاوية
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-6">
                    <!-- حاوية الصور المختارة للتسكين -->
                    <div class="mb-4">
                        <label class="font-weight-bold">الصور المحددة للتسكين:</label>
                        <div id="assignSelectedPhotosPreviews" class="d-flex flex-wrap gap-2 p-2 border rounded bg-light" style="min-height: 80px; max-height: 140px; overflow-y: auto;">
                            <!-- يتم ملؤها ديناميكيًا بواسطة JS -->
                        </div>
                        <div id="assignPhotoIdsInputs">
                            <!-- inputs hidden for photo_ids[] -->
                        </div>
                    </div>

                    <!-- قائمة اختيار الطلب أو الحجز -->
                    <div class="form-group mb-4">
                        <label for="bookingSelect" class="font-weight-bold">
                            اختر الطلب / الحجز: <span class="text-danger">*</span>
                        </label>
                        <select name="booking_id" id="bookingSelect" class="form-control selectpicker" data-live-search="true" title="اختر الطلب من القائمة أو ابحث برقم الحجز أو الحاوية..." required>
                            <option value="">-- اختر الطلب / الحجز --</option>
                            @foreach($bookings ?? [] as $b)
                                @php
                                    $client = $b->factory?->name ?: ($b->company?->name ?: '');
                                    $containersData = $b->bookingContainers->map(fn($c) => [
                                        'id' => $c->id,
                                        'no' => $c->container_no ?: ('حاوية #' . $c->id),
                                        'sail' => $c->sail_of_number
                                    ])->values();
                                @endphp
                                <option value="{{ $b->id }}"
                                        data-booking-number="{{ $b->booking_number ?: ('#' . $b->id) }}"
                                        data-client="{{ $client }}"
                                        data-containers='@json($containersData)'>
                                    حجز {{ $b->booking_number ?: ('#' . $b->id) }}
                                    @if($client) - {{ $client }} @endif
                                    @if($b->bookingContainers->isNotEmpty())
                                        (حاويات: {{ $b->bookingContainers->pluck('container_no')->filter()->implode(', ') }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">قائمة بجميع الطلبات الحالية، مع إمكانية البحث السريع برقم الحجز أو الحاوية أو العميل.</small>
                    </div>

                    <!-- اختيار الحاوية التابعة للحجز -->
                    <div class="form-group mb-4" id="containerSelectionGroup" style="display: none;">
                        <label for="selectedContainerId" class="font-weight-bold">تحديد الحاوية (اختياري):</label>
                        <select name="booking_container_id" id="selectedContainerId" class="form-control">
                            <option value="">كامل الحجز (عام - بدون تحديد حاوية معينة)</option>
                        </select>
                        <small class="form-text text-muted">إذا كانت الصورة خاصة بحاوية معينة، يرجى اختيارها من هنا.</small>
                    </div>

                    <!-- نوع المستند / الصورة -->
                    <div class="form-group mb-2">
                        <label for="paperTypeSelect" class="font-weight-bold">نوع الملف / الصورة في أوراق الحجز: <span class="text-danger">*</span></label>
                        <select name="type" id="paperTypeSelect" class="form-control" required>
                            <option value="1" selected>صورة الحاوية</option>
                            <option value="0">جواب تخصيص</option>
                            <option value="6">جواب التحميل</option>
                            <option value="5">صورة سيل ملاحي</option>
                            <option value="4">جواب التعتيق</option>
                            <option value="3">كارتة سيارة</option>
                            <option value="8">إذن شحن</option>
                            <option value="9">صورة أخرى</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-3 bg-light">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success font-weight-bold" id="confirmAssignBtn" disabled>
                        <i class="fas fa-save mr-1"></i> تأكيد تسكين الصورة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نموذج الحذف الجماعي المخفي -->
<form id="bulkDeleteForm" method="POST" action="{{ route('agent-photos.bulk-destroy') }}" style="display: none;">
    @csrf
    <div id="bulkDeleteInputs"></div>
</form>

<script>
    (function() {
        function initAgentPhotosPage() {
            if (typeof jQuery === 'undefined') {
                setTimeout(initAgentPhotosPage, 100);
                return;
            }

            var $ = jQuery;
            var searchUrl = "{{ route('agent-photos.search-bookings') }}";
            var searchTimeout = null;

            // تحديث شريط العمليات الجماعية بناءً على التحديد
            function updateBulkToolbar() {
                var checkedBoxes = $('.photo-checkbox:checked');
                var count = checkedBoxes.length;

                if (count > 0) {
                    $('#bulkToolbar').slideDown(200);
                    $('#selectedCountBadge').text('تم تحديد ' + count + ' صورة');
                } else {
                    $('#bulkToolbar').slideUp(200);
                    $('#selectAllCheckbox').prop('checked', false);
                }

                $('.photo-card').removeClass('is-selected');
                checkedBoxes.each(function() {
                    $('#card-photo-' + $(this).val()).addClass('is-selected');
                });
            }

            // تحديد / إلغاء تحديد الكل
            $(document).on('change', '#selectAllCheckbox', function() {
                var isChecked = $(this).is(':checked');
                $('.photo-checkbox').prop('checked', isChecked);
                updateBulkToolbar();
            });

            // تغيير حالة مربع اختيار فردي
            $(document).on('change', '.photo-checkbox', function() {
                updateBulkToolbar();
            });

            // دالة ملء بيانات المودال
            function populateAssignModal(photos) {
                var previewsContainer = $('#assignSelectedPhotosPreviews');
                var hiddenInputsContainer = $('#assignPhotoIdsInputs');
                previewsContainer.empty();
                hiddenInputsContainer.empty();

                if (!photos || !photos.length) return;

                photos.forEach(function(p) {
                    previewsContainer.append(
                        '<div class="d-inline-flex flex-column align-items-center m-1 p-1 border bg-white rounded shadow-sm" style="width: 90px;">' +
                            '<img src="' + p.url + '" style="height: 55px; width: 80px; object-fit: cover;" class="rounded mb-1">' +
                            '<span class="font-size-xs text-truncate text-muted text-center" style="max-width: 80px;" title="' + p.name + '">' + p.name + '</span>' +
                        '</div>'
                    );
                    hiddenInputsContainer.append('<input type="hidden" name="photo_ids[]" value="' + p.id + '">');
                });

                $('#bookingSelect').val('');
                if ($.fn.selectpicker) {
                    $('#bookingSelect').selectpicker('refresh');
                }
                $('#containerSelectionGroup').hide();
                $('#selectedContainerId').html('<option value="">كامل الحجز (عام - بدون تحديد حاوية معينة)</option>');
                $('#confirmAssignBtn').prop('disabled', true);
                $('#paperTypeSelect').val('1');

                $('#assignModalLabel').html(
                    '<i class="fas fa-folder-plus text-primary mr-2"></i> تسكين ' + photos.length + (photos.length === 1 ? ' صورة' : ' صور') + ' في حجز أو حاوية'
                );
            }

            // عند فتح المودال عبر data-toggle
            $('#assignModal').on('show.bs.modal', function(e) {
                var $target = $(e.relatedTarget);
                if (!$target || !$target.length) return;
                var $btn = $target.hasClass('single-assign-btn') ? $target : $target.closest('.single-assign-btn');
                if ($btn.length) {
                    var photoId = $btn.data('photo-id');
                    var photoUrl = $btn.data('photo-url');
                    var photoName = $btn.data('photo-name');
                    if (photoId) {
                        populateAssignModal([{
                            id: photoId,
                            url: photoUrl,
                            name: photoName
                        }]);
                    }
                }
            });

            // فتح نافذة التسكين لصورة مفردة عبر النقر المباشر
            $(document).on('click', '.single-assign-btn', function() {
                var photoId = $(this).data('photo-id');
                var photoUrl = $(this).data('photo-url');
                var photoName = $(this).data('photo-name');

                populateAssignModal([{
                    id: photoId,
                    url: photoUrl,
                    name: photoName
                }]);
            });

            // فتح نافذة التسكين للمحدد جماعيًا
            $(document).on('click', '#bulkAssignBtn', function() {
                var selected = [];
                $('.photo-checkbox:checked').each(function() {
                    selected.push({
                        id: $(this).val(),
                        url: $(this).data('photo-url'),
                        name: $(this).data('photo-name')
                    });
                });

                if (selected.length === 0) {
                    alert('يرجى تحديد صورة واحدة على الأقل أولاً.');
                    return;
                }

                populateAssignModal(selected);
                $('#assignModal').modal('show');
            });

            // تفاعل تغيير الطلب / الحجز من القائمة
            $(document).on('change', '#bookingSelect', function() {
                var bookingId = $(this).val();
                if (!bookingId) {
                    $('#containerSelectionGroup').hide();
                    $('#confirmAssignBtn').prop('disabled', true);
                    return;
                }

                $('#confirmAssignBtn').prop('disabled', false);

                var $opt = $(this).find('option:selected');
                var rawContainers = $opt.attr('data-containers');
                var containers = [];
                try {
                    containers = JSON.parse(rawContainers || '[]');
                } catch(e) {
                    containers = [];
                }

                var $containerSelect = $('#selectedContainerId');
                $containerSelect.empty();
                $containerSelect.append('<option value="">كامل الحجز (عام - بدون تحديد حاوية معينة)</option>');

                if (containers && containers.length > 0) {
                    containers.forEach(function(c) {
                        $containerSelect.append(
                            '<option value="' + c.id + '">حاوية: ' + c.no + (c.sail ? ' (سيل: ' + c.sail + ')' : '') + '</option>'
                        );
                    });
                    $('#containerSelectionGroup').slideDown(150);
                } else {
                    $('#containerSelectionGroup').hide();
                }
            });

            // تنفيذ الحذف الجماعي
            $(document).on('click', '#bulkDeleteBtn', function() {
                var selectedIds = [];
                $('.photo-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) {
                    alert('يرجى تحديد صورة واحدة على الأقل أولاً.');
                    return;
                }

                if (!confirm('هل أنت متأكد من حذف ' + selectedIds.length + ' صور محددة نهائيًا؟')) {
                    return;
                }

                var inputsContainer = $('#bulkDeleteInputs');
                inputsContainer.empty();
                selectedIds.forEach(function(id) {
                    inputsContainer.append('<input type="hidden" name="photo_ids[]" value="' + id + '">');
                });

                $('#bulkDeleteForm').submit();
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAgentPhotosPage);
        } else {
            initAgentPhotosPage();
        }
    })();
</script>
@endsection

