@extends("layouts.admin")
 <style>
        .bs-canvas-overlay,
        .bs-canvas {
            transition: all .4s ease-out;
            -webkit-transition: all .4s ease-out;
            -moz-transition: all .4s ease-out;
            -ms-transition: all .4s ease-out;
        }

        .bs-canvas {
            top: 0;
            z-index: 1110;
            overflow-x: hidden;
            overflow-y: auto;
            width: 330px;
        }

        .bs-canvas-left {
            left: 0;
            margin-left: -330px;
        }

        .bs-canvas-right {
            right: 0;
            margin-right: -330px;
        }

        /* Only for demo */
    </style>
@section("content")
<div class="container">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.agent_car_transfer') ])
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5 d-flex justify-content-between align-items-center">
            <div class="card-toolbar d-flex gap-2">
                @if (auth()->user()->hasPermissionTo('agents.index'))
                    <a href="{{ route('agent_car_tranfer.create', $agent->id) }}" class="btn btn-primary font-weight-bolder">
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
                        </span>{{ __('admin.add') }}
                    </a>
                @endif
                <!-- زر الفلتر -->
                <button type="button" class="btn btn-primary fw-bold shadow-sm" data-toggle="modal" data-target="#filterModal">
                    <i class="fas fa-filter"></i> فلتر
                </button>
                <!-- زر تصدير Excel -->
                <div class="p-2">
                    <button class="btn btn-primary" type="button" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> تصدير إلى Excel</button>
                </div>
            </div>
        </div>


        <div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-light">
                        <h5 class="modal-title" id="filterModalLabel">تقرير ب فتره</h5>
                        <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('agent_car_tranfer.index', $agent->id) }}" method="get">
                            <div class="form-group">
                                <label for="monthInput">من</label>
                                <input type="date" name="from" value="{{ old('from') ?? request('from') }}"
                                    id="monthInput" class="form-control" placeholder="من">
                            </div>
                            <div class="form-group">
                                <label for="yearInput">الي</label>
                                <input type="date" name="to" value="{{ old('to') ?? request('to') }}"
                                       id="yearInput" class="form-control"
                                       placeholder="الي" >
                            </div>
                            <button class="btn btn-primary" type="submit">فلتر</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="carTransfersTable">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th scope="col">#</th>
                            <th scope="col">الصورة</th>
                            <th scope="col">رقم السيارة</th>
                            <th scope="col">المندوب</th>
                            <th scope="col">الاسم</th>
                            <th scope="col">المبلغ</th>
                            <th scope="col">التاريخ</th>
                            <th scope="col">أضيف بواسطة</th>
                            <th scope="col">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalAmount = 0;
                        @endphp
                        @foreach ($items as $item)
                        @php
                            $totalAmount += $item->value ?? 0;
                        @endphp
                        <tr>
                            <th scope="row" class="text-center">{{ $item->id }}</th>
                            <td class="text-center" style="width: 80px;">
                                @if($item->image)
                                    @php
                                        $imagePath = asset($item->image);
                                    @endphp
                                    <a href="{{ $imagePath }}"
                                       data-lightbox="transfer-{{ $item->id }}"
                                       data-title="صورة المعاملة">
                                        <img src="{{ $imagePath }}"
                                             alt="صورة"
                                             class="img-thumbnail"
                                             style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;">
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->car?->car_number ?? '-' }}</td>
                            <td>{{ $item->agent?->name ?? '-' }}</td>
                            <td>{{ $item->name ?? '-' }}</td>
                            <td class="text-center">
                                <strong class="text-danger">{{ number_format($item->value ?? 0, 2) }} جنيه</strong>
                            </td>
                            <td class="text-center">{{ $item->created_at ?? '-' }}</td>
                            <td>{{ $item->user?->name ?? '-' }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    @if (auth()->user()->hasPermissionTo('agents.update'))
                                        <a href="{{ route('agent_car_tranfer.edit', $item->id) }}"
                                            class="btn btn-sm btn-clean btn-icon btn-icon-md" title="تعديل">
                                            <i class="la la-edit"></i>
                                        </a>
                                    @endif
                                    @if (auth()->user()->hasPermissionTo('agents.delete'))
                                        <a class="btn btn-sm btn-clean btn-icon btn-icon-md" title="حذف" onclick="Delete({{ $item->id }})">
                                            <i class="la la-trash"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="5" class="text-right">الإجمالي:</td>
                            <td class="text-center text-danger">
                                {{ number_format($totalAmount, 2) }} جنيه
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <!--end::Card-->
</div>


@endsection
@push('js')
    <script>

        function Delete(id) {
            Swal.fire({
                title: "{{ __('alerts.are_you_sure') }}",
                text: "{{ __('alerts.not_revert_information') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('alerts.confirm') }}",
                cancelButtonText: "{{ __('alerts.cancel') }}",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = '{{ route("agent_car_tranfer.destroy", ":id") }}';
                    url = url.replace(':id', id);
                    var token = '{{ csrf_token() }}';
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });
                    $.ajax({
                        url: url,
                        type: 'delete',
                        success: function(response, textStatus, xhr) {
                            console.log(response, xhr.status);
                            if(xhr.status == 200){
                                Swal.fire({
                                    title: "{{ __('alerts.done') }}",
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                });
                                location.reload();
                                //getNotify();
                            }
                        }
                    });
                }
            });
        }


    </script>
    <!-- Lightbox CSS & JS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportToExcel() {
            // الحصول على البيانات من الجدول
            let table = document.getElementById("carTransfersTable");
            let wb = XLSX.utils.book_new();

            // تحويل الجدول إلى ورقة عمل
            let ws = XLSX.utils.table_to_sheet(table, {
                raw: false,
                dateNF: 'dd/mm/yyyy'
            });

            // تنسيق الأعمدة
            ws['!cols'] = [
                { wch: 10 },  // #
                { wch: 15 },  // الصورة
                { wch: 15 },  // رقم السيارة
                { wch: 20 },  // المندوب
                { wch: 30 },  // الاسم
                { wch: 15 },  // المبلغ
                { wch: 15 },  // التاريخ
                { wch: 20 },  // أضيف بواسطة
                { wch: 15 }   // الإجراءات
            ];

            // إضافة الورقة إلى المصنف
            XLSX.utils.book_append_sheet(wb, ws, "تحويلات المندوب للسيارة");

            // تصدير الملف
            XLSX.writeFile(wb, "تحويلات_المندوب_للسيارة_" + new Date().toISOString().split('T')[0] + ".xlsx");
        }
    </script>
@endpush
