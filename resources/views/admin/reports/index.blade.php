@extends("layouts.admin")
@section("content")
<div class="container">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.daily_reports') ])
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
                    <a href="{{ route('reports.export_excel', request()->query()) }}" class="btn btn-success">
                        <i class="fas fa-file-excel me-1"></i> تصدير اكسيل
                    </a>
                </div>
                <div class="p-2">
                    <a class="btn btn-primary" href="{{ route('reports.daily_reports') }}"><i class="fas fa-sync-alt me-1"></i>إعادة الضبط</a>
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
                        <form action="{{ route('reports.daily_reports') }}" method="get">
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
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ __('admin.agent') }}</th>
                        <th scope="col">{{ __('admin.action') }}</th>
                        <th scope="col">{{ __('main.date') }}</th>
                        <th scope="col">{{ __('main.time') }}</th>
                        {{-- <th scope="col"></th> --}}
                    </tr>
                </thead>
                <tbody>
                    @foreach ($log_activities as $log_activity)
                    <tr>
                            <th scope="row">{{$log_activity->id}}</th>
                            <td>
                                {{$log_activity?->attacher?->name ?? ""}}
                            </td>
                    
                            <td>{{ $log_activity->action ?? "" }}</td>
                            <td>{{ $log_activity->date ?? "" }}</td>
                            <td>{{ $log_activity->time ?? "" }}</td>
                          
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportToExcel() {
            let table = document.getElementById("table");
            let wb = XLSX.utils.table_to_book(table, { sheet: "التقارير اليوميه" });
            XLSX.writeFile(wb, "التقارير اليوميه.xlsx");
        }
    </script>
@endpush

