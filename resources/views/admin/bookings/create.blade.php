@extends('layouts.admin')
@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.add_new_booking') }}
            </div>
            <div class="mt-3">
                <a href="{{ route('bookings.index') }}" class="btn btn-secondary float-right">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        
        @include('admin.bookings.form')


    </div>
@endsection

@push('js')
    
    
    <script>
    $(document).on('change', '#factory_id', function(){
        let factoryId = $(this).val();
        let url = '{{ route("factory.branches", ":id") }}';
        url = url.replace(':id', factoryId);  // Corrected 'replace' spelling
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                if(response.status) {
                    let options = '<option value="">{{ __('admin.select') }}</option>';
                    
                    $.each(response.data, function(id, name) {
                        options += `<option value="${id}">${name}</option>`;
                    });

                    $('#branch_id').html(options);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching branches:', error);
            }
        });
    });
</script>

@endpush


