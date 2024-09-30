<div class="col-md-12 mt-2 p-5">
    <a href="{{ route('booking-containers.create', ['booking' => $booking->id]) }}">
        <button class="btn btn-primary float-right" data-target="#serviceModal" type="button">
            <i class="fa fa-plus text-white"></i> {{ __('admin.add') }}
        </button>
    </a>
</div>

<div class="table-responsive">
    <table class="table table-striped" id="transportation_id" style="width:100%">
        <thead>
            <tr>
                @if ($booking->type_of_action != 2)
                    <th>
                        {{ __('admin.container_no') }}
                    </th>
                    <th>
                        {{ __('admin.navigational_torrent') }}
                    </th>
                @endif
                <th>
                    {{ __('admin.container_type') }}
                </th>
                <th>
                    <div class="col-md-12">
                        {{ __('admin.factory') . ':' }}
                    </div>
                    <div class="col-md-12">
                        <small class="badge badge-pill badge-light">
                            {{ __('admin.branch') }}
                        </small>
                    </div>
                </th>
                <th>
                    {{ __('admin.departure_location') }}
                </th>
                <th>
                    {{ __('admin.loading_location') }}
                </th>
                <th>
                    {{ __('admin.aging_location') }}
                </th>
                <th>
                    {{ __('admin.cost') }}
                </th>
                <th>{{ __('admin.action') }}</th>
            </tr>
        </thead>
        <tbody id="transportationsTableRows">
            @if (!is_null($booking->bookingContainers))
                @foreach ($booking->bookingContainers as $container)
                    <tr id="transportation_{{ $container->id }}">
                        @if ($booking->type_of_action != 2)
                            <td>{{ $container->container_no }}</td>
                            <td>{{ $container->sail_of_number }}</td>
                        @endif
                        <td>{{ $container->container?->full_name }}</td>
                        <td>
                            <div class="col-md-12">
                                {{ $container->branch?->factory->name . ':' }}
                            </div>
                            <div class="col-md-12">
                                <small class="badge badge-pill badge-light">
                                    {{ $container->branch?->name }}
                                </small>
                            </div>
                        </td>
                        <td>{{ $container->departure->title ?? '' }}</td>
                        <td>{{ $container->loading->title ?? '' }}</td>
                        <td>{{ $container->aging->title ?? '' }}</td>

                        <td class="prices">{{ $container->price }}</td>

                        <td>
                            <div class="d-flex">
                                <a class="mx-2"
                                    href="{{ route('expenses.booking_container_expenses', $container->id) }}">
                                    {{ __('main.expenses') }}
                                </a>

                                <a class="mx-2"
                                    href="{{ route('bookings.booking_container_papers', $container->id) }}">
                                    {{ __('admin.papers') }}
                                </a>

                                <a class="mx-2"
                                    href="{{ route('bookings.booking_container_policies', $container->id) }}">
                                    {{ __('main.delivery_policies') }}
                                </a>

                                @if ($container->delivery_policies->count())
                                    <a class="mx-2"
                                        href="{{ route('booking_contrainer_extra_costs', $container->id) }}">
                                        {{ __('main.extra_expense') }}
                                    </a>
                                @endif


                                <!-- Button trigger modal -->
                                <a
                                    href="{{ route('booking-containers.edit', ['booking_container' => $container->id]) }}">
                                    <button class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3" type="button">
                                        <i class="fas fa-edit text-primary"></i>
                                    </button>
                                </a>



                                <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                                    onclick="containerDelete(event, '{{ $container->id }}')">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>

                            </div>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</div>

@push('js')
    <script>
        function containerDelete(e, id) {
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
                    var url = "{{ route('booking-containers.destroy', ':id') }}";
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
                            $('#transportation_' + id).remove();
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
