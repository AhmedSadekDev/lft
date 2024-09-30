<h4 class="mb-10 font-weight-bold text-dark">
    {{ __('admin.container_information') }}
</h4>

<div class="row">
    {!! Form::open([
        'url' => ['javascript:void(0)'],
        'method' => null,
        'id' => 'inbound_form',
        'onclick' => 'outbound_submit_form()',
    ]) !!}
    <div class="col-xl-4">
        <!--begin::Input-->
        <div class="form-group">
            {!! Form::label('containers_number_input', __('admin.containers_number')) !!}
            {!! Form::number(
                'containers_number',
                isset($booking) ? $booking->bookingContainers?->count() ?? old('containers_number') : old('containers_number'),
                [
                    'id' => 'containers_number_input',
                    'class' => 'form-control',
                    'placeholder' => __('admin.containers_number'),
                    'min' => 1,
                ],
            ) !!}
            @error('containers_number')
                <span class="form-text text-danger">{{ $message }}</span>
            @enderror
        </div>
        <!--end::Input-->
    </div>
    {{-- <div class="col-xl-4">
        <!--begin::Select -->
        <div class="form-group">
            {!! Form::label('container_type_input', __('admin.container_type')) !!}
            {!! Form::select('container_type', $containers_type, (isset($booking) ?  ($booking->thirdBookings?->container_id ?? old('container_type')): old('container_type')), ['id' => 'container_type_input', 'class' => 'form-control', 'placeholder' => __('admin.choose_container_type')]) !!}
            @error('container_type')
                <span class="form-text text-danger">{{ $message }}</span>
            @enderror
        </div>
        <!--end::Select-->
    </div>
    <div class="col-xl-4">
        <!--begin::Select -->
        <div class="form-group">
            {!! Form::label('factory_name_input', __('admin.factory_name')) !!}
            {!! Form::select('factory_id', $factories, (isset($booking) ?  ($booking->thirdBookings?->factory_id ?? old('factory_id')): old('factory_id')), ['id' => 'factory_name_input', 'class' => 'form-control', 'placeholder' => __('admin.choose_factory')]) !!}
            @error('factory_id')
                <span class="form-text text-danger">{{ $message }}</span>
            @enderror
        </div>
        <!--end::Select-->
    </div> --}}
    <div class="col-xl-1">
        <!--begin::Select -->
        <div class="form-group">
            {!! Form::button(__('admin.add'), [
                'class' => 'btn btn-primary',
                'id' => 'submit_inbound_form',
                'onclick' => 'outbound_submit_form()',
            ]) !!}
        </div>
        <!--end::Select-->
    </div>
    {!! Form::close() !!}
</div>

<div>
    <table id="example" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('admin.container_id') }}</th>
                <th>{{ __('admin.navigational_torrent') }}</th>
                <th>{{ __('admin.container_no') }}</th>
                <th>{{ __('admin.container_type') }}</th>
                <th>{{ __('admin.branches') }}</th>
                <th>{{ __('admin.arrival_date') }}</th>
                <th>{{ __('admin.actions') }}</th>
            </tr>
        </thead>
        <tbody id="tableRows">
            @if (isset($booking) && !is_null($booking->bookingContainers))
                @foreach ($booking->bookingContainers as $key => $bookingContainer)
                    <tr id="row_{{ $key + 1 }}" data-id="{{ $key + 1 }}">
                        <input type="hidden" name="factoriesIDs[]" class="form-control"
                            value="{{ $bookingContainer->branch?->factory?->id }}">
                        @if ($bookingContainer->container)
                            <input type="hidden" name="containers[]" class="form-control"
                                value="{{ $bookingContainer->container?->id }}">
                        @endif
                        <td>{{ $key + 1 }}</td>
                        <td>
                            {!! Form::text('sail_of_numbers[]', $bookingContainer->sail_of_number, [
                                'class' => 'form-control',
                                'placeholder' => __('admin.navigational_torrent'),
                            ]) !!}
                        </td>
                        <td>
                            {!! Form::text('container_no[]', $bookingContainer->container_no, [
                                'class' => 'form-control',
                                'placeholder' => __('admin.container_no'),
                            ]) !!}
                        </td>
                        <td>{{ $bookingContainer->container?->full_name ?? '' }}</td>
                        <td>
                            @if ($bookingContainer->branch)
                                {!! Form::select(
                                    'branches[]',
                                    factoryBranches($bookingContainer->branch?->factory?->id),
                                    isset($booking) ? $bookingContainer?->branch_id ?? old('branch_id')[$key] : old('branch_id'),
                                    [
                                        'id' => 'branch_name_input branch_' . $key,
                                        'class' => 'form-control branches',
                                        'placeholder' => __('admin.choose_branch'),
                                    ],
                                ) !!}
                            @endif
                            @error('branches.' . $key)
                                <span class="form-text text-danger">{{ $message }}</span>
                            @enderror
                        </td>
                        <td>
                            {!! Form::date('arrival_dates[]', $bookingContainer->arrival_date, [
                                'class' => 'form-control arrival_date_input',
                                'id' => 'arrival_date_input',
                            ]) !!}
                            @error('arrival_dates.' . $key)
                                <span class="form-text text-danger">{{ $message }}</span>
                            @enderror
                        </td>
                        <td>
                            <a href="javascript:void(0)" class="mt-2 text-center"
                                onclick="remove({{ $key + 1 }})">
                                <i class="fa fa-trash text-danger mt-3 ml-12"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            @else
                @if (!is_null(old('container_no')) || !is_null(old('arrival_date')))
                    @for ($i = 0; $i < count(old('container_no')); $i++)
                        <tr id="row_{{ $i }}" data-id="{{ $i }}">
                            <input type="hidden" name="factoriesIDs[]" class="form-control"
                                value="{{ old('factoriesIDs')[$i] }}">
                            <input type="hidden" name="containers[]" class="form-control"
                                value="{{ old('containers')[$i] }}">
                            <td>{{ $i + 1 }}</td>
                            <td>
                                {!! Form::text('sail_of_numbers[]', !is_null(old('sail_of_numbers')) ? old('sail_of_numbers')[$i] : null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('admin.navigational_torrent'),
                                ]) !!}
                                @error('sail_of_numbers.' . $i)
                                    <span class="form-text text-danger">{{ $message }}</span>x
                                @enderror
                            </td>
                            <td>
                                {!! Form::text('container_no[]', !is_null(old('container_no')) ? old('container_no')[$i] : null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('admin.container_no'),
                                ]) !!}
                                @error('container_no.' . $i)
                                    <span class="form-text text-danger">{{ $message }}</span>x
                                @enderror
                            </td>
                            <td>{{ containerType(old('containers')[$i]) }}</td>
                            <td>
                                {!! Form::select(
                                    'branches[]',
                                    factoryBranches(old('factoriesIDs')[$i]),
                                    !is_null(old('branches')) && is_array(old('branches')) ? old('branches')[$i] : null,
                                    [
                                        'id' => 'branch_name_input branches_' . $i,
                                        'class' => 'form-control branches',
                                        'placeholder' => __('admin.choose_branch'),
                                    ],
                                ) !!}
                                @error('branches.' . $i)
                                    <span class="form-text text-danger">{{ $message }}</span>
                                @enderror
                            </td>
                            <td>
                                {!! Form::date(
                                    'arrival_dates[]',
                                    !is_null(old('arrival_dates')) && is_array(old('arrival_dates')) ? old('arrival_dates')[$i] : null,
                                    ['class' => 'form-control arrival_date_input', 'id' => 'arrival_date_input'],
                                ) !!}
                                @error('arrival_dates.' . $i)
                                    <span class="form-text text-danger">{{ $message }}</span>x
                                @enderror
                            </td>
                            <td>
                                <a href="javascript:void(0)" class="mt-2 text-center"
                                    onclick="remove({{ $i + 1 }})">
                                    <i class="fa fa-trash text-danger mt-3 ml-12"></i>
                                </a>
                            </td>
                        </tr>
                    @endfor
                    {{--  @foreach (old('containers_number') as $key => $containers)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ old('container_type') }}</td>
                            <td>
                                {!! Form::select('branches[]', [], old('branches')[$key], ['id' => 'branch_name_input', 'class' => 'form-control branches', 'placeholder' => __('admin.choose_branch')]) !!}
                                @error('branches.' . $key)
                                    <span class="form-text text-danger">{{ $message }}</span>
                                @enderror
                            </td>
                            <td>
                                {!! Form::date('arrival_dates[]', !is_null(old('arrival_dates')) ? old('arrival_dates')[$key] : null, [ 'class' => 'form-control arrival_date_input', 'id' => 'arrival_date_input']) !!}
                                @error('arrival_dates.' . $key)
                                    <span class="form-text text-danger">{{ $message }}</span>
                                @enderror
                            </td>
                        </tr>
                    @endforeach  --}}
                @endif
            @endif
        </tbody>
    </table>
</div>

@push('js')
    <script>
        function outbound_submit_form() {
            var formData = $('form#outbound_submit_form').serialize();
            var factory_id = $('#factory_name_input').val();
            var type = $('#container_type_input option:selected').text();
            var container_id = $('#container_type_input option:selected').val();
            var number = $('#containers_number_input').val();
            var count = $('#tableRows tr').length;

            var trId = $("#tableRows tr").last().attr('data-id');
            if (typeof trId !== 'undefined' && trId !== 0) {
                var counter = parseInt(trId);
            } else {
                var counter = count;
            }

            for (i = 1; i <= number; i++) {
                counter += 1;
                $('#tableRows').append(`<tr id="row_${counter}" data-id="${counter}">

                    <input type="hidden" name="factoriesIDs[]" class="form-control" value="${factory_id}">
                    <input type="hidden" name="containers[]" class="form-control" value="${container_id}">
                    <td>${counter}</td>
                    <td>
                        <input type="number" name="sail_of_numbers[]" class="form-control" placeholder="{{ __('admin.navigational_torrent') }}">
                    </td>
                    <td>
                        <input type="number" name="container_no[]" class="form-control" placeholder="{{ __('admin.container_no') }}">
                    </td>
                    <td>${type}</td>
                    <td>
                        <select name="branches[]" id="branches_${counter}" class="form-control branches">
                        </select>
                    </td>
                    <td>
                        <input type="date" name="arrival_dates[]" class="form-control arrival_date_input">
                    </td>
                    <td>
                        <a href="javascript:void(0)" class="mt-2 text-center" onclick="remove(${counter})">
                            <i class="fa fa-trash text-danger mt-3 ml-4"></i>
                        </a>
                    </td>
                </tr>`);
                branches(factory_id, counter);
            }

            var tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow = tomorrow.toISOString().split('T')[0];
            $(".arrival_date_input").val(tomorrow);
        };
    </script>
@endpush
