{{-- <h4 class="mb-10 font-weight-bold text-dark">
    {{ __('admin.customer_clearance_information') }}
</h4>

<div class="row">

    <div class="col-xl-4">
        <!--begin::Input-->
        <div class="form-group">
            {!! Form::label('quantity_input', __('admin.quantity')) !!}
            {!! Form::number('quantity', (isset($booking) ? ($booking?->thirdBookings?->quantity ?? old('quantity')) : old('quantity')), ['id'=>'quantity_input', 'class'=>'form-control', 'placeholder'=> __('admin.quantity'), 'min'=>1]) !!}
            @error('quantity')
                <span class="form-text text-danger">{{ $message }}</span>
            @enderror
        </div>
        <!--end::Input-->
    </div>

    <div class="col-xl-4">
        <!--begin::Select -->
        <div class="form-group">
            {!! Form::label('unit_input', __('admin.unit')) !!}
            {!! Form::text('unit',  (isset($booking) ? ($booking?->thirdBookings?->unit ?? old('unit')) : old('unit')), ['id' => 'unit_input', 'class' => 'form-control', 'placeholder' => __('admin.unit')]) !!}
            @error('unit')
                <span class="form-text text-danger">{{ $message }}</span>
            @enderror
        </div>
        <!--end::Select-->
    </div>

    <div class="col-xl-4">
        <!--begin::Select -->
        <div class="form-group">
            {!! Form::label('factory_input', __('admin.factory')) !!}
            {!! Form::select('factory_id', $factories, (isset($booking) ? ($booking?->thirdBookings?->factory_id ?? old('factory_id')) : old('factory_id')), ['id' => 'factory_input', 'class' => 'form-control', 'placeholder' => __('admin.choose_factory'), 'onchange' => 'clearance_branches()']) !!}
            @error('factory_id')
                <span class="form-text text-danger">{{ $message }}</span>
            @enderror
        </div>
        <!--end::Select-->
    </div>

    <div class="col-xl-4">
        <!--begin::Select -->
        <div class="form-group">
            {!! Form::label('branch_input', __('admin.branch')) !!}
            {!! Form::select('branch_id', [], (isset($booking) ? ($booking?->thirdBookings?->branch_id ?? old('branch_id')) : old('branch_id')), ['id' => 'branch_input', 'class' => 'form-control', 'placeholder' => __('admin.choose_branch')]) !!}
            @error('branch_id')
                <span class="form-text text-danger">{{ $message }}</span>
            @enderror
        </div>
        <!--end::Select-->
    </div>

    <div class="col-xl-4">
        <!--begin::Select -->
        <div class="form-group">
            {!! Form::label('arrival_date_input', __('admin.arrival_date')) !!}
            {!! Form::date('arrival_date',  (isset($booking) ? ($booking?->thirdBookings?->arrival_date ?? old('arrival_date')) : old('arrival_date')), ['id' => 'arrival_date_input', 'class' => 'form-control arrival_date_input', 'placeholder' => __('admin.choose_arrival_date')]) !!}
            @error('arrival_date')
                <span class="form-text text-danger">{{ $message }}</span>
            @enderror
        </div>
        <!--end::Select-->
    </div>
</div>

@push('js')
    <script>
        @if (!is_null(old('factory_id')) || isset($booking))
            var factory_id = "{{ isset($booking) ? ($booking?->thirdBookings?->factory_id ?? old('factory_id')) : old('factory_id') }}";
            clearance_branches(factory_id);
        @endif

        function clearance_branches(factory_id){
            var id = $('#factory_input').val();
            if (id != '' && id != undefined){
                var id = id;
            } else {
                var id = factory_id;
            }

            var url = "{{ route('factory.branches', ':id') }}";
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
                method:'GET',
                success:function(res){
                    $('#branch_input').empty();
                    $.each(res['data'], function(i, v){
                        $('#branch_input').append(`<option value=${i}>${v}</option>`);
                    });

                    var branch_id = `{{ isset($booking) ? ($booking->thirdBookings?->branch_id ?? old('branch_id')): old('branch_id') }}`;
                    if(branch_id != ''){
                        $('#branch_input option[value='+branch_id+']').attr('selected','selected');
                    }
                }
            })
        }

    </script>
@endpush
 --}}
