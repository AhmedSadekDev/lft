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
                        {{-- <th scope="col"></th> --}}
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

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
