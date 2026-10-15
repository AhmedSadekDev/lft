@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
<div class="container-fluid booking-form-page">
        @include('layouts.includes.breadcrumb', ['page' => __('admin.add_new_booking')])

        <div class="booking-form-hero">
            <div class="booking-form-hero-inner">
                <div>
                    <h1 class="booking-form-hero-title">{{ __('admin.add_new_booking') }}</h1>
                    <p class="booking-form-hero-subtitle">أدخل بيانات الطلب والحاويات ثم أكّد الحفظ</p>
                </div>
                <a href="{{ route('bookings.index') }}" class="booking-form-hero-back">
                    <i class="fas fa-arrow-right"></i>
                    {{ __('main.back') }}
                </a>
            </div>
        </div>

        <div class="card card-custom gutter-b lft-simple-card booking-form-card">
            @include('admin.bookings.form')
        </div>
    </div>
    </div>
@endsection

@push('js')
<script>
    $(document).ready(function () {
        $(document).on('change', '#factory_id', function () {
            let factoryId = $(this).val();
            let url = '{{ route("factory.branches", ":id") }}'.replace(':id', factoryId);

            $.ajax({
                url: url,
                type: 'GET',
                success: function (response) {
                    if (response.status) {
                        let options = '<option value="">{{ __('admin.select') }}</option>';
                        $.each(response.data, function (id, name) {
                            options += `<option value="${id}">${name}</option>`;
                        });
                        $('#branch_id').html(options);
                        if ($('#branch_id').hasClass('selectpicker')) {
                            $('#branch_id').selectpicker('refresh');
                        }
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching branches:', error);
                }
            });
        });

        var company_employees = {!! json_encode($company_employees) !!};
        $('#company_id').on('change', function () {
            var companyId = $(this).val();
            $('#employee_id').empty().append('<option value="">{{ __('admin.select') }}</option>');
            if (company_employees[companyId]) {
                $.each(company_employees[companyId], function (id, name) {
                    $('#employee_id').append(`<option value="${id}">${name}</option>`);
                });
            }
        });

        let today = new Date().toISOString().split('T')[0];
        $('#permit_end_date_input, #discharge_date_input, .arrival_date_input').attr('min', today);
    });
</script>
@endpush
