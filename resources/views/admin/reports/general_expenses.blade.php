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
    @include("layouts.includes.breadcrumb", [ 'page' => 'المصروفات العامة' ])
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5 d-flex justify-content-between align-items-center">
            <div class="card-toolbar d-flex gap-2">
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
                        <form action="{{ route('reports.general_expenses') }}" method="get">
                            <div class="form-group">
                                <label for="monthInput">من</label>
                                <input type="date" name="from" value="{{ old('from') }}"
                                    id="monthInput" class="form-control" placeholder="من">
                            </div>
                            <div class="form-group">
                                <label for="yearInput">الي</label>
                                <input type="date" name="to" value="{{ old('to') }}"
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
                <table class="table table-hover table-bordered" id="expensesTable">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th scope="col">الصورة</th>
                            <th scope="col">الفئة</th>
                            <th scope="col">الخدمة</th>
                            <th scope="col">القيمة منصرف</th>
                            <th scope="col">القيمة وارد</th>
                            <th scope="col">رقم الشحنة</th>
                            <th scope="col">التاريخ</th>
                            <th scope="col">المندوب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalExpenses = 0;
                            $totalIncome = 0;
                        @endphp
                        @foreach ($expenses as $item)
                        @php
                            // التحقق من نوع السجل (مصروف أو معاملة مالية أو سجل نشاط)
                            $isMoneyTransfer = $item instanceof \App\Models\MoneyTransfer;
                            $isExpense = $item instanceof \App\Models\AgentExpense;
                            $isLogActivity = $item instanceof \App\Models\LogActivity;

                            // حساب المصروفات والوارد
                            $expenseValue = 0;
                            $incomeValue = 0;
                            $category = '';
                            $service = '';
                            $agentName = '';
                            $bookingNumber = '';
                            $bookingId = null;
                            $imageUrl = '';
                            $date = '';

                            if ($isLogActivity) {
                                // سجل نشاط (LogActivity)
                                $logType = $item->log_type;
                                $log = $item->log;
                                $value = $log?->value ?? 0;

                                $agentName = $item->attacher?->name ?? '-';
                                $date = $item->created_at ?? '';

                                if ($logType == \App\Models\AgentExpense::class) {
                                    // مصروف مندوب (منصرف)
                                    $expenseValue = $value;
                                    $totalExpenses += $expenseValue;
                                    $category = 'سجل نشاط - مصروف';
                                    $service = $item->action ?? 'مصروف مندوب';

                                    // محاولة الحصول على الصورة من المصروف
                                    if ($log && $log->image_agent_expenses) {
                                        $imageUrl = $log->image_agent_expenses;
                                    }

                                    // محاولة الحصول على رقم الشحنة
                                    if ($log && $log->bookingContainer) {
                                        $bookingNumber = $log->bookingContainer->booking?->booking_number ?? '';
                                        $bookingId = $log->bookingContainer->booking_id;
                                    }
                                } elseif ($logType == \App\Models\MoneyTransfer::class && $log) {
                                    $type = $log->type;

                                    if ($type == \App\Models\MoneyTransfer::deliveryPolicy) {
                                        // عهدة السيارة (منصرف)
                                        $expenseValue = $value;
                                        $totalExpenses += $expenseValue;
                                        $category = 'سجل نشاط - عهدة السيارة';
                                        $service = $item->action ?? 'تحويل عهدة';

                                        // الحصول على الصورة من البوليصة
                                        if ($log->delivery_policy_id && $log->delivery_policy && $log->delivery_policy->image) {
                                            $imageUrl = $log->delivery_policy->image->image;
                                        }

                                        // محاولة الحصول على رقم الشحنة
                                        if ($log->delivery_policy_id && $log->delivery_policy) {
                                            $firstContainer = $log->delivery_policy->booking_containers->first();
                                            if ($firstContainer) {
                                                $bookingNumber = $firstContainer->booking?->booking_number ?? '';
                                                $bookingId = $firstContainer->booking_id;
                                            }
                                        }
                                    } elseif ($type == \App\Models\MoneyTransfer::officeCommission) {
                                        // دخان المكتب (وارد)
                                        $incomeValue = $value;
                                        $totalIncome += $incomeValue;
                                        $category = 'سجل نشاط - دخان المكتب';
                                        $service = $item->action ?? 'دخان المكتب';

                                        // الحصول على الصورة من البوليصة
                                        if ($log->delivery_policy_id && $log->delivery_policy && $log->delivery_policy->image) {
                                            $imageUrl = $log->delivery_policy->image->image;
                                        }

                                        // محاولة الحصول على رقم الشحنة
                                        if ($log->delivery_policy_id && $log->delivery_policy) {
                                            $firstContainer = $log->delivery_policy->booking_containers->first();
                                            if ($firstContainer) {
                                                $bookingNumber = $firstContainer->booking?->booking_number ?? '';
                                                $bookingId = $firstContainer->booking_id;
                                            }
                                        }
                                    } elseif ($type == \App\Models\MoneyTransfer::fromDashboard) {
                                        // إيداع من لوحة التحكم (وارد)
                                        $incomeValue = $value;
                                        $totalIncome += $incomeValue;
                                        $category = 'سجل نشاط - إيداع';
                                        $service = $item->action ?? 'إيداع من لوحة التحكم';
                                    } elseif ($type == \App\Models\MoneyTransfer::transferAgent) {
                                        // تحويل لمندوب آخر (منصرف)
                                        $expenseValue = $value;
                                        $totalExpenses += $expenseValue;
                                        $category = 'سجل نشاط - تحويل';
                                        $service = $item->action ?? 'تحويل لمندوب آخر';
                                    } elseif ($type == \App\Models\MoneyTransfer::settle) {
                                        // تسوية (منصرف)
                                        $expenseValue = $value;
                                        $totalExpenses += $expenseValue;
                                        $category = 'سجل نشاط - تسوية';
                                        $service = $item->action ?? 'تسوية البوليصة';
                                    }
                                }
                            } elseif ($isMoneyTransfer && $item->type == 3) {
                                // عهدة السيارة (منصرف)
                                $expenseValue = $item->value ?? 0;
                                $totalExpenses += $expenseValue;
                                $category = 'عهدة السيارة';
                                $service = 'تحويل عهدة للسائق ' . ($item->transfered?->name ?? '');
                                $agentName = $item->transferer?->name ?? '-';
                                $date = $item->created_at ?? '';

                                // الحصول على الصورة من البوليصة
                                if ($item->delivery_policy_id && $item->delivery_policy && $item->delivery_policy->image) {
                                    $imageUrl = $item->delivery_policy->image->image;
                                }

                                // محاولة الحصول على رقم الشحنة من البوليصة
                                if ($item->delivery_policy_id && $item->delivery_policy) {
                                    $firstContainer = $item->delivery_policy->booking_containers->first();
                                    if ($firstContainer) {
                                        $bookingNumber = $firstContainer->booking?->booking_number ?? '';
                                        $bookingId = $firstContainer->booking_id;
                                    }
                                }
                            } elseif ($isMoneyTransfer && $item->type == 5) {
                                // دخان المكتب (وارد)
                                $incomeValue = $item->value ?? 0;
                                $totalIncome += $incomeValue;
                                $category = 'دخان المكتب';
                                $service = 'عهدة السيارة';
                                $agentName = $item->transfered?->name ?? '-';
                                $date = $item->created_at ?? '';

                                // الحصول على الصورة من البوليصة
                                if ($item->delivery_policy_id && $item->delivery_policy && $item->delivery_policy->image) {
                                    $imageUrl = $item->delivery_policy->image->image;
                                }

                                // محاولة الحصول على رقم الشحنة من البوليصة
                                if ($item->delivery_policy_id && $item->delivery_policy) {
                                    $firstContainer = $item->delivery_policy->booking_containers->first();
                                    if ($firstContainer) {
                                        $bookingNumber = $firstContainer->booking?->booking_number ?? '';
                                        $bookingId = $firstContainer->booking_id;
                                    }
                                }
                            } elseif ($isExpense) {
                                // مصروف عادي (منصرف)
                                $expenseValue = $item->value ?? 0;
                                $totalExpenses += $expenseValue;

                                if ($item->delivery_policy_id) {
                                    $category = 'عهدة السيارة';
                                } elseif ($item->type == 2) {
                                    $category = 'عهدة السيارة';
                                } else {
                                    $category = 'من إدخال المندوب';
                                }

                                if ($item->service) {
                                    $service = ($item->service->serviceCategory?->title ?? '') . ' - ' . ($item->service->name ?? '');
                                } else {
                                    $service = '-';
                                }

                                $agentName = $item->agent?->name ?? '-';
                                $bookingNumber = $item->bookingContainer?->booking?->booking_number ?? '';
                                $bookingId = $item->bookingContainer?->booking_id;
                                $imageUrl = $item->image_agent_expenses;
                                $date = $item->created_at ?? '';
                            }
                        @endphp
                        <tr>
                            <td class="text-center" style="width: 80px;">
                                @if($imageUrl)
                                    @php
                                        // تحديد المسار الصحيح للصورة
                                        $imagePath = '';
                                        if (strpos($imageUrl, 'http') === 0) {
                                            $imagePath = $imageUrl; // رابط كامل
                                        } elseif (strpos($imageUrl, 'data:image') === 0) {
                                            $imagePath = $imageUrl; // base64
                                        } else {
                                            // صورة محفوظة في السيرفر
                                            $imagePath = asset($imageUrl);
                                        }
                                    @endphp
                                    <a href="{{ $imagePath }}"
                                       data-lightbox="expense-{{ $item->id }}"
                                       data-title="إيصال المصروف">
                                        <img src="{{ $imagePath }}"
                                             alt="صورة"
                                             class="img-thumbnail"
                                             style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;">
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $category }}</td>
                            <td>{{ $service }}</td>
                            <td class="text-center">
                                @if($expenseValue > 0)
                                    <strong class="text-danger">{{ number_format($expenseValue, 2) }}</strong>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                @if($incomeValue > 0)
                                    <strong class="text-success">{{ number_format($incomeValue, 2) }}</strong>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                @if($bookingId && $bookingNumber)
                                    <a href="{{ route('bookings.show', $bookingId) }}"
                                       class="btn btn-sm btn-link">
                                        {{ $bookingNumber }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $date }}</td>
                            <td>{{ $agentName }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="3" class="text-right">الإجمالي:</td>
                            <td class="text-center text-danger">
                                {{ number_format($totalExpenses, 2) }} جنيه
                            </td>
                            <td class="text-center text-success">
                                {{ number_format($totalIncome, 2) }} جنيه
                            </td>
                            <td colspan="3" class="text-right">
                                <span class="text-muted">الصافي: </span>
                                <strong class="{{ ($totalIncome - $totalExpenses) >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($totalIncome - $totalExpenses, 2) }} جنيه
                                </strong>
                            </td>
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
                    var url = '{{ route("agents.destroy", ":id") }}';
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
            let table = document.getElementById("expensesTable");
            let wb = XLSX.utils.book_new();

            // تحويل الجدول إلى ورقة عمل
            let ws = XLSX.utils.table_to_sheet(table, {
                raw: false,
                dateNF: 'dd/mm/yyyy'
            });

            // تنسيق الأعمدة
            ws['!cols'] = [
                { wch: 15 },  // الصورة
                { wch: 20 },  // الفئة
                { wch: 30 },  // الخدمة
                { wch: 15 },  // القيمة منصرف
                { wch: 15 },  // القيمة وارد
                { wch: 20 },  // رقم الشحنة
                { wch: 15 },  // التاريخ
                { wch: 20 }   // المندوب
            ];

            // إضافة الورقة إلى المصنف
            XLSX.utils.book_append_sheet(wb, ws, "المصروفات العامة");

            // تصدير الملف
            XLSX.writeFile(wb, "المصروفات_العامة_" + new Date().toISOString().split('T')[0] + ".xlsx");
        }
    </script>
@endpush

