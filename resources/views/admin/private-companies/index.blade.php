@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => 'الشركات الخاصة'])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-title">
                    <h3 class="card-label">الشركات الخاصة</h3>
                </div>
                <div class="card-toolbar">
                    <!--begin::Search-->
                    <form method="GET" action="{{ route('private-companies.index') }}" class="d-flex align-items-center mr-4">
                        <input type="text" name="search" class="form-control form-control-solid w-250px mr-3"
                               placeholder="بحث بالاسم، الرقم الضريبي، السجل التجاري..."
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-light-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('private-companies.index') }}" class="btn btn-light ml-2">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </form>
                    <!--end::Search-->
                    <!--begin::Button-->
                    <a href="{{ route('private-companies.create') }}" class="btn btn-primary font-weight-bolder mr-2">
                        <span class="svg-icon svg-icon-md">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <rect x="0" y="0" width="24" height="24" />
                                    <circle fill="#000000" cx="9" cy="15" r="6" />
                                    <path
                                        d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z"
                                        fill="#000000" opacity="0.3" />
                                </g>
                            </svg>
                        </span>إضافة شركة خاصة
                    </a>
                    <!--end::Button-->
                </div>
            </div>
            <div class="card-body">
                @if($privateCompanies->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-separate table-head-custom no-datatable" id="table" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px">#</th>
                                <th style="min-width: 150px">الاسم</th>
                                <th style="min-width: 120px">الرقم الضريبي</th>
                                <th style="min-width: 120px">السجل التجاري</th>
                                <th style="min-width: 100px">اللوجو</th>
                                <th class="text-center" style="min-width: 120px">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($privateCompanies as $privateCompany)
                                <tr>
                                    <td class="text-center align-middle">
                                        <span class="text-muted font-weight-bold">{{ $privateCompany->id }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-dark font-weight-bold">{{ $privateCompany->name }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-muted">{{ $privateCompany->tax_no ?? '-' }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-muted">{{ $privateCompany->commercial_register ?? '-' }}</span>
                                    </td>
                                    <td class="align-middle">
                                        @if($privateCompany->logo)
                                            <img src="{{ $privateCompany->logo }}" alt="{{ $privateCompany->name }}"
                                                 style="max-width: 60px; max-height: 60px; border-radius: 4px;">
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('private-companies.edit', $privateCompany->id) }}"
                                               class="btn btn-sm btn-clean btn-icon" title="تعديل">
                                                <i class="la la-edit text-primary"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-clean btn-icon delete-btn"
                                                    data-id="{{ $privateCompany->id }}"
                                                    data-url="{{ route('private-companies.destroy', $privateCompany->id) }}"
                                                    title="حذف">
                                                <i class="la la-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center flex-wrap mt-5">
                    <div class="d-flex flex-wrap py-2 mr-3">
                        <span class="text-muted">
                            عرض {{ $privateCompanies->firstItem() }} إلى {{ $privateCompanies->lastItem() }} من {{ $privateCompanies->total() }} نتائج
                        </span>
                    </div>
                    <div>
                        {{ $privateCompanies->links() }}
                    </div>
                </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> لا توجد شركات خاصة مسجلة حالياً.
                    </div>
                @endif
            </div>
        </div>
        <!--end::Card-->
    </div>
@endsection

@push('js')
<script>
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var url = $(this).data('url');

        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "لن تتمكن من التراجع عن هذا!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، احذف!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire(
                            'تم الحذف!',
                            'تم حذف الشركة الخاصة بنجاح.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'خطأ!',
                            'حدث خطأ أثناء حذف الشركة الخاصة.',
                            'error'
                        );
                    }
                });
            }
        });
    });
</script>
@endpush
