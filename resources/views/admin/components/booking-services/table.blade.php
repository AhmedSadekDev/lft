<div class="col-md-12 mt-2 p-5">
    <!-- Button trigger modal -->
    <a href="{{ route('booking-services.create', ['booking' => $booking->id]) }}">
        <button class="btn btn-primary float-right" data-target="#serviceModal" type="button" {{-- onclick=" serviceModal('{{ $booking->id }}')" --}}>
            <i class="fa fa-plus text-white"></i> {{ __('admin.add') }}
        </button>
    </a>
</div>

<table class="table table-striped" id="extensions_id" style="width:100%">
    <thead>
        <tr>
            <th>
                #
            </th>
            <th>
                {{ __('admin.service') }}
            </th>
            <th>
                {{ __('admin.note') }}
            </th>
            <th>
                {{ __('admin.cost') }}
            </th>
            <th></th>
        </tr>
    </thead>
    <tbody id="serviceTableRows">
        @forelse ($booking_services as $service)
            <tr id="service_{{ $service->id }}">
                <td>
                    {{ $service->id }}
                </td>
                <td>
                    {{ $service->full_name }}
                </td>
                <td>
                    {{ $service->note }}
                </td>
                <td class="services_total_price" data-price="{{ $service->price }}">
                    {{ $service->price }}
                </td>
                <td>
                    @if (auth()->user()->hasPermissionTo('services.delete'))
                        <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                            onclick="serviceDelete(event, '{{ $service->id }}')">
                            <i class="fas fa-trash text-danger"></i>
                        </button>
                    @endif
                </td>
            </tr>
        @empty
        @endforelse

        @if (isset($expensesServices))
            @foreach($expensesServices as $expense)
                <tr id="service_{{ $expense->id }}">
                    <td>
                        {{ $expense->id }}
                    </td>
                    <td>
                        {{ $expense->service->name }}
                    </td>
                    <td>
                        {{ $expense->notes }}
                    </td>
                    <td class="services_total_price" data-price="{{ $expense->value }}">
                        {{ $expense->value }}
                    </td>
                    <td>
                        @if (auth()->user()->hasPermissionTo('services.delete'))
                            <button class="btn btn-icon btn-light btn-hover-danger btn-sm">
                                <i class="fas fa-trash text-danger"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
        @endif


    </tbody>
</table>

@push('js')
    <script>
        function serviceDelete(e, id) {
            e.preventDefault();
            Swal.fire({
                title: "{{ __('alerts.are_you_sure') }}",
                text: "{{ __('alerts.not_revert_information') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('alerts.confirm') }}",
                cancelButtonText: "{{ __('alerts.cancel') }}",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('booking-services.destroy', ':id') }}";
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
                        success: function(response) {
                            $('#service_' + id).remove();
                            Swal.fire({
                                title: "{{ __('alerts.success') }}",
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            var message = xhr.responseJSON.message;
                            Swal.fire({
                                title: message,
                                icon: 'error',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        }
                    });
                }
            });
        }
    </script>
@endpush
