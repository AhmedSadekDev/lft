@extends("layouts.admin")
@section("content")
<div class="container">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.expenses') ])
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5">
            <div class="card-toolbar">
                <!--begin::Button-->

                <!--end::Button-->
            </div>
        </div>
        <div class="card-body">
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">صورة</th>
                        <th scope="col">{{ __('admin.title') }}</th>
                        <th scope="col">{{ __('admin.value') }}</th>
                        <th scope="col">{{ __('main.date') }}</th>
                        <th scope="col">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($allExpenses as $allExpense)
                    <tr>
                            <th scope="row">{{$allExpense->id}}</th>
                            <td >@if($allExpense->image !== null) <img
                                    src="{{ asset('Admin/images/expenses/' . $allExpense->image_agent_expenses) }}" alt="صورة الايصال"
                                    style="width: 100px;" /> @else لا توجد صورة @endif</td>
                            <td>{{ $allExpense->title ?? "" }}</td>
                            <td>{{ $allExpense->value ?? "" }}</td>
                            <td>{{ $allExpense->created_at ?? "" }}</td>
                            <td>
                                @if(isset($allExpense->agent_id) && $allExpense instanceof \App\Models\AgentExpense)
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                                        onclick="DeleteExpense('{{ $allExpense->id }}')">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                @endif
                            </td>
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

        function DeleteExpense(id) {
            Swal.fire({
                title: "{{ __('alerts.are_you_sure') }}",
                text: "{{ __('alerts.not_revert_information') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('alerts.confirm') }}",
                cancelButtonText: "{{ __('alerts.cancel') }}",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = '{{ route("expenses.destroy", ":id") }}';
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
                            Swal.fire({
                                title: "{{ __('alerts.done') }}",
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                            location.reload();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: "{{ __('alerts.error') }}",
                                text: xhr.responseJSON?.message || "{{ __('alerts.error_occurred') }}",
                                icon: 'error',
                            });
                        }
                    });
                }
            });
        }


    </script>
@endpush

