@extends('layouts.admin')
@section('content')
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
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.bookings')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar w-100 d-flex justify-content-between align-items-center">
                    <!--begin::Button-->
                    @if (auth()->user()->hasPermissionTo('bookings.create'))
                        <a href="{{ route('bookings.create') }}" class="btn btn-primary font-weight-bolder">
                            <span class="svg-icon svg-icon-md">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
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
                                <!--end::Svg Icon-->
                            </span>{{ __('admin.add_new_booking') }}
                        </a>
                    @endif
                    <!--end::Button-->

                    <div class="p-2">
                        <a class="btn btn-primary"
                            href="{{ route('booking_container.export', ['ids' => implode(',', $bookings->pluck('id')->toArray())]) }}">{{ __('admin.export') }}</a>
                        <button class="btn btn-primary" type="button" data-toggle="canvas" data-target="#bs-canvas-left"
                            aria-expanded="false" aria-controls="bs-canvas-left">{{ __('admin.search') }}</button>
                    </div>

                    <div id="bs-canvas-left" class="bs-canvas bs-canvas-left position-fixed bg-light h-100">
                        <header class="bs-canvas-header p-3 bg-primary overflow-auto">
                            <button type="button" class="bs-canvas-close float-left close" aria-label="Close"
                                aria-expanded="false"><span aria-hidden="true" class="text-light">&times;</span></button>
                            <h4 class="d-inline-block text-light mb-0 float-right">Filter</h4>
                        </header>
                        <div class="bs-canvas-content px-3 py-5">
                            <form action="">
                                <div class="form-group">
                                    <label for="">{{ __('admin.date') }}</label>
                                    <input type="date" name="arrival_date" value="{{ old('arrival_date') }}"
                                        id="" class="form-control" placeholder="{{ __('admin.date') }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('admin.search') }}</label>
                                    <input type="text" name="search" id="SearchInput" value="{{ old('search') }}"
                                        class="form-control" placeholder="{{ __('admin.search') }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('main.company') }}</label>
                                    <select name="company" id="" class="form-control selectpicker"
                                        title="{{ __('main.comapny') }}">
                                        <option value="">{{ __('admin.select') }}</option>
                                        @foreach ($companies as $company)
                                            <option {{ old('company') == $company->id ? 'selected' : '' }}
                                                value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                {{-- <div class="form-group">
                                    <label for="">{{ __('admin.taxed_status') }}</label>
                                    <select name="tax_status" id="" class="form-control selectpicker"
                                        title="{{ __('admin.taxed_status') }}">
                                        <option value="">{{ __('admin.select') }}</option>
                                        <option value="1">{{ __('admin.taxed') }}</option>
                                        <option value="0">{{ __('admin.not_taxed') }}</option>
                                    </select>
                                </div> --}}
                                <button class="btn btn-primary" type="submit">{{ __('admin.search') }}</button>

                            </form>
                        </div>
                    </div>
                    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
                        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
                    </script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js"
                        integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous">
                    </script>
                    <script>
                        jQuery(document).ready(function($) {
                            var bsOverlay = $('.bs-canvas-overlay');
                            $('[data-toggle="canvas"]').on('click', function() {
                                var ctrl = $(this),
                                    elm = ctrl.is('button') ? ctrl.data('target') : ctrl.attr('href');
                                $(elm).addClass('mr-0');
                                $(elm + ' .bs-canvas-close').attr('aria-expanded', "true");
                                $('[data-target="' + elm + '"], a[href="' + elm + '"]').attr('aria-expanded', "true");
                                if (bsOverlay.length)
                                    bsOverlay.addClass('show');
                                return false;
                            });

                            $('.bs-canvas-close, .bs-canvas-overlay').on('click', function() {
                                var elm;
                                if ($(this).hasClass('bs-canvas-close')) {
                                    elm = $(this).closest('.bs-canvas');
                                    $('[data-target="' + elm + '"], a[href="' + elm + '"]').attr('aria-expanded', "false");
                                } else {
                                    elm = $('.bs-canvas')
                                    $('[data-toggle="canvas"]').attr('aria-expanded', "false");
                                }
                                elm.removeClass('mr-0');
                                $('.bs-canvas-close', elm).attr('aria-expanded', "false");
                                if (bsOverlay.length)
                                    bsOverlay.removeClass('show');
                                return false;
                            });
                        });
                    </script>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">

                    <table class="table-responsive-xl" id="">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('admin.company_name') }}</th>
                                <th scope="col">{{ __('admin.responsible_employee') }}</th>
                                <th scope="col">{{ __('main.factory') }}</th>
                                <th scope="col">{{ __('admin.booking_number') }}</th>
                                <th scope="col">{{ __('admin.taxed_status') }}</th>

                                <th scope="col">{{ __('admin.invoice_status') }}</th>
                                <th scope="col">{{ __('admin.created_at') }}</th>
                                <th scope="col">{{ __('admin.print_invoice') }}</th>
                                <th scope="col">{{ __('admin.print_eInvoice') }}</th>
                                <th scope="col">الملاحظات</th>

                                <th scope="col">{{ __('admin.delete') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bookings as $booking)
                                <tr>
                                    <th scope="row">{{ $booking->id }}</th>
                                    <td>{{ $booking->company->name ?? '__' }}</td>
                                    <td>{{ $booking->employee_name ?? '__' }}</td>
                                    <td>{{ $booking?->factory?->name ?? '__' }}</td>
                                    <td>{{ $booking->booking_number }} </td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $booking->company->taxed == 0 ? 'danger' : 'success' }} text-white">
                                            <i
                                                class="fa fa-{{ $booking->company->taxed == 0 ? 'xmark' : 'check' }} text-white"></i>
                                            {{ $booking->taxed_invoice }}
                                        </span>
                                    </td>

                                    <td>{{ !is_null($booking->invoice?->invoice_number) ? 'تم إنشاء فاتورة' : 'لم يتم إنشاء فاتورة' }}
                                    </td>
                                    <td>{{ $booking->created_at }}</td>
                                    <td class="text-center">
                                        @if (is_null($booking->invoice?->invoice_number))
                                            @if ($booking->type_of_action != 2)
                                                <a class="btn btn-primary" href="{{ route('booking-invoices.create', $booking->id) }}">
                                                    {{ __('admin.create_invoice') }}
                                                </a>
                                            @else
                                                ____
                                            @endif
                                        @else
                                            @if ($booking->type_of_action != 2)
                                                <a href="{{ route('booking-invoices.edit', $booking->invoice->id) }}"
                                                    class="btn btn-primary mb-2">
                                                    {{ __('admin.edit_invoice') }}
                                                </a>
                                                <a href="{{ route('booking-invoices.show', ['booking_invoice' => $booking->invoice->id]) }}"
                                                    class="btn btn-primary w-full">
                                                    {{ __('admin.show') . ' ' . __('admin.bill_type_invoice') }}
                                                </a>
                                            @else
                                                ____
                                            @endif
                                        @endif
                                    </td>
                                   
                                    <td>{{ 'لم يتم طباعة الفاتورة الالكترونية' }}</td>
                                    <td><a href="{{ route('bookings.booking_notes', ['booking' => $booking->id]) }}"
                                                    class="btn btn-primary w-full">
                                                    الملاحظات
                                                </a></td>
                                    <td>
                                        <div class="row">
                                            @if (auth()->user()->hasPermissionTo('bookings.index'))
                                                <div class="col-md-3 col-sm-6 ml-2">
                                                    <a href="{{ route('bookings.show', $booking->id) }}"
                                                        class="btn btn-icon btn-light btn-hover-success btn-sm mx-3">
                                                        <i class="fas fa-eye text-success"></i>
                                                    </a>
                                                </div>
                                            @endif
                                            @if (auth()->user()->hasPermissionTo('bookings.update'))
                                                <div class="col-md-3 col-sm-6 ml-2">
                                                    <a href="{{ route('bookings.edit', $booking->id) }}"
                                                        class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3">
                                                        <i class="fas fa-edit text-primary"></i>
                                                    </a>
                                                </div>
                                            @endif
                                            @if (auth()->user()->hasPermissionTo('bookings.delete'))
                                                <div class="col-md-3 col-sm-6 ml-2">
                                                    <button
                                                        class="btn btn-icon btn-light btn-hover-danger btn-sm mx-3 delete"
                                                        onclick="Delete('{{ $booking->id }}')">
                                                        <i class="fas fa-trash text-danger"></i>
                                                    </button>
                                                </div>
                                            @endif
                                            <div class="col-md-3 col-sm-6 ml-2 mt-1">
                                                <a href="{{ route('bookings.booking_papers', ['booking' => $booking->id]) }}"
                                                    class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3">
                                                    {{ __('admin.papers') }}
                                                </a>
                                            </div>
                                            
                                        </div>
                                    </td>
                                </tr>
                                <div class="modal fade" id="invoiceModal{{ $booking->id }}" tabindex="-1" aria-labelledby="invoiceModalLabel{{ $booking->id }}" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="invoiceModalLabel{{ $booking->id }}">إنشاء فاتورة</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        {{-- محتوى إنشاء الفاتورة --}}
        <form method="GET" id="invoiceForm{{$booking->id}}" action="{{ route('booking-invoices.create', $booking->id) }}">

  <div class="form-group">
    <label for="invoice_number">رقم الفاتورة</label>
    <input form="invoiceForm{{$booking->id}}" type="text" name="invoice_number" id="invoice_number" class="form-control" required>
  </div>

  {{-- باقي الحقول المطلوبة --}}

  <button type="submit" form="invoiceForm{{$booking->id}}" class="btn btn-success">حفظ الفاتورة</button>
</form>

      </div>
    </div>
  </div>
</div>

                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--end::Card-->
    </div>
    <!-- Modal -->


@endsection
@push('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (function($) {
            "use strict";
        })(jQuery);

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
                    var url = '{{ route('bookings.destroy', ':id') }}';
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
                            location.reload();
                            Swal.fire({
                                title: "{{ __('alerts.done') }}",
                                icon: 'success',
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
