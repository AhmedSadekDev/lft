@extends("layouts.admin")
@section("content")
<!--begin::Card-->
<div class="card card-custom gutter-b">
    <div class="card-header">
        <div class="card-title">
            {{ __('admin.papers') }}
        </div>
    </div>

    <div class="card-body">
        <!-- بداية نموذج رفع صورة -->
<div class="card card-custom mb-5">
    <div class="card-header bg-light-primary">
        <h3 class="card-title font-weight-bold">رفع صورة أو ملف جديد</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('bookings.papers.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" value="{{ $booking->id }}" name="booking_id" />
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="image" class="font-weight-bold">اختر صورة أو ملف</label>
                    <input type="file" name="image" id="image" class="form-control" required>
                </div>

                <div class="form-group col-md-4">
                    <label for="type" class="font-weight-bold">نوع الملف</label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="0">جواب تخصيص</option>
                        <option value="1">صورة الحاوية</option>
                        <option value="5">صورة سيل ملاحي</option>
                        <option value="4">جواب التعتيق</option>
                        <option value="8">اذن شحن</option>
                        <option value="9">أخرى</option>
                    </select>
                </div>

                <div class="form-group col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-upload"></i> رفع الملف
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- نهاية نموذج رفع صورة -->

        <div class="row">
            @foreach ($booking_papers as $paper)
                <div class="col-md-3 mb-4">
                    <div class="card card-custom shadow-sm">
                        <div class="card-body p-2">
                            <div class="position-relative text-center">

                                <!-- Image as Link -->
                                @php
                                    $fileUrl = $paper?->image?->image ?? "";
                                    $fileExtension = pathinfo($fileUrl, PATHINFO_EXTENSION);
                                    $isImage = in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                @endphp
                                
                                <a href="{{ $fileUrl }}" target="_blank">
                                    @if ($isImage)
                                        <img src="{{ $fileUrl }}" class="img-fluid rounded"
                                             style="width: 100%; height: 200px; object-fit: cover;" alt="Paper Image">
                                    @else
                                        <div class="d-flex flex-column align-items-center justify-content-center"
                                             style="width: 100%; height: 200px; border: 1px dashed #ccc; border-radius: 8px;">
                                            <i class="fas fa-file-alt fa-3x text-muted mb-2"></i>
                                            <span class="text-muted">عرض الملف</span>
                                        </div>
                                    @endif
                                </a>


                                <!-- Delete Icon -->
                                <form action="{{ route('admin.papers.delete', $paper->id) }}" method="POST"
                                      style="position: absolute; top: 10px; right: 10px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('{{ __('هل تريد حذف الصوره؟') }}')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- Title -->
                            <div class="text-center mt-2">
                                <span class="font-weight-bold d-block" style="font-size:16px">
                                    @switch($paper->type)
                                        @case(0) جواب تخصيص @break
                                        @case(1) صوره الحاويه @break
                                        @case(5) صورة سيل ملاحي @break
                                        @case(4) جواب التعتيق @break
                                        @case(8) اذن شحن  @break
                                        @default صوره اخري
                                    @endswitch
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
