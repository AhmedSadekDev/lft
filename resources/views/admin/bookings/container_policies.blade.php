@extends("layouts.admin")
@section("content")
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                {{ __('main.delivery_policies') }}
            </div>
            <div class="mt-3">
                <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-secondary float-right">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ __('main.drivers') }}</th>
                        <th scope="col">{{ __('admin.value') }}</th>
                        <th scope="col">{{ __('admin.car_number') }}</th>
                        <th scope="col">{{ __('main.date') }}</th>
                        <th scope="col">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($booking_policies as $allExpense)
                    <tr>
                            <th scope="row">{{$allExpense->id}}</th>

                            <td>{{ $allExpense->driver->name ?? "" }}</td>
                            <td>{{ $allExpense->money_transfer->value ?? "" }}</td>
                            <td>{{ $allExpense->car->car_number ?? "" }}</td>
                            <td>{{ $allExpense->created_at ?? "" }}</td>
                            <td>
                                @if($allExpense->is_settled != 1)
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                                        onclick="deleteDeliveryPolicy('{{ $allExpense->id }}')">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                @else
                                    <span class="badge badge-success">{{ __('admin.settle') }}</span>
                                @endif
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('js')
    <script>
        function deleteDeliveryPolicy(id) {
            Swal.fire({
                title: "{{ __('alerts.are_you_sure') }}",
                text: "{{ __('alerts.not_revert_information') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('alerts.confirm') }}",
                cancelButtonText: "{{ __('alerts.cancel') }}",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = '{{ route("bookings.delete_delivery_policy", ":id") }}';
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
                        type: 'DELETE',
                        method: 'DELETE',
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
