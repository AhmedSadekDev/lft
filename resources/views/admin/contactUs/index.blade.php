@extends("layouts.admin")
@section("content")
<div class="container">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.contactUs') ])
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-body">
            <table class="table table-responsive-sm" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ __('admin.email') }}</th>
                        <th scope="col">{{ __('admin.message') }}</th>
                        <th scope="col">{{ __('admin.created_at') }}</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contacts as $contact)
                        <tr>
                            <th scope="row">{{$contact->id}}</th>
                            <td>{{$contact->email}}</td>
                            <td>{{$contact->message}}</td>
                            <td>{{$contact->created_at}}</td>
                            <td>
                                @if(auth()->user()->hasPermissionTo('contactUs.delete'))
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete" onclick="Delete('{{ $contact->id }}')">
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
                    var url = '{{ route("contactUs.destroy", ":id") }}';
                    url = url.replace(':id', id);
                    var token = '{{ csrf_token() }}';
                    console.log(url, token);
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
                            console.log(response);
                            location.reload();
                            Swal.fire({
                                title: `{{ __('alerts.done') }}`,
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
