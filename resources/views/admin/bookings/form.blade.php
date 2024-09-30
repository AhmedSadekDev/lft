<div class="card-body p-0" style="direction:rtl">
    <div class="row justify-content-center py-10 px-8 py-lg-12 px-lg-10">
        <div class="col-xl-12 col-xxl-7">

            @if ($method == 'POST')
                {!! Form::open([
                    'url' => $action,
                    'method' => $method,
                    'enctype' => 'multipart/form-data',
                    'id' => 'kt_form',
                    'class' => 'form',
                    'files' => true,
                ]) !!}
            @elseif ($method == 'PUT')
                {!! Form::model($booking, [
                    'url' => [$action],
                    'method' => $method,
                    'enctype' => 'multipart/form-data',
                    'id' => 'kt_form',
                    'files' => true,
                ]) !!}
            @endif

            {{-- <!--begin::Select-->
            <div class="form-group{{ $errors->has('company_id') ? ' has-error' : '' }}">
                {!! Form::label('company_id', __('main.company')) !!}
                {!! Form::select(
                    'company_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $companies),
                    old('company_id'),
                    [
                        'id' => 'company_id',
                        'class' => 'form-control',
                        'required' => 'required',
                    ],
                ) !!}
                <small class="text-danger">{{ $errors->first('company_id') }}</small>
            </div>
            <!--end::Select--> --}}

            <div class="form-group">
                <label for="">{{ __('main.company') }}</label>
                <select name="company_id" id="company_id" class="form-control selectpicker select-company">
                    <option value="">{{ __('admin.select') }}</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @if (old('company_id') == $company->id || (isset($booking) && $booking->company_id == $company->id)) selected @endif>
                            {{ $company->name }}</option>
                    @endforeach
                </select>
            </div>


            <!--begin::Select-->
            <div class="form-group{{ $errors->has('employee_id') ? ' has-error' : '' }}">
                {!! Form::label('employee_id', __('main.employee')) !!}
                {!! Form::select(
                    'employee_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $employees?->all() ?? []),
                    old('employee_id'),
                    [
                        'id' => 'employee_id',
                        'class' => 'form-control select-employee',
                        'required' => 'required',
                    ],
                ) !!}
                <small class="text-danger">{{ $errors->first('employee_id') }}</small>
            </div>
            <!--end::Select-->

            <!--begin::Input-->
            <div class="form-group">
                {!! Form::label('shipping_agent_input', __('admin.shipping_agency')) !!}
                {!! Form::select(
                    'shipping_agent_id',
                    $shipping_agents,
                    isset($booking) ? $booking?->shipping_agent_id ?? null : old('shipping_agent_id'),
                    [
                        'id' => 'shipping_agent_input',
                        'class' => 'form-control',
                        'placeholder' => __('admin.shipping_agency'),
                    ],
                ) !!}
                @error('shipping_agent')
                    <span class="form-text text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group{{ $errors->has('factory_id') ? ' has-error' : '' }}">
                {!! Form::label('factory_id', __('admin.factory')) !!}
                {!! Form::select(
                    'factory_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $factories->all()),
                    old('factory_id'),
                    [
                        'id' => 'factory_id',
                        'class' => 'form-control',
                        'required' => 'required',
                    ],
                ) !!}
                <small class="text-danger">{{ $errors->first('factory_id') }}</small>
            </div>
            <!--end::Input-->
            <!--begin::Input-->
            <div class="form-group">
                {!! Form::label('booking_number_input', __('admin.booking_number')) !!}
                {!! Form::text('booking_number', isset($booking) ? $booking?->booking_number ?? null : old('booking_number'), [
                    'id' => 'booking_number_input',
                    'class' => 'form-control',
                    'placeholder' => __('admin.booking_number'),
                    'min' => 1,
                    'maxLength' => 255,
                ]) !!}
                @error('booking_number')
                    <span class="form-text text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                {!! Form::label('employee_name_input', __('admin.responsible_employee')) !!}
                {!! Form::text('employee_name', isset($booking) ? $booking?->employee_name ?? null : old('employee_name'), [
                    'id' => 'employee_name_input',
                    'class' => 'form-control',
                    'placeholder' => __('admin.responsible_employee'),
                    'minLength' => 3,
                    'maxLength' => 255,
                ]) !!}
                @error('employee_name')
                    <span class="form-text text-danger">{{ $message }}</span>
                @enderror
            </div>



            <!--end::Input-->
            <div class="row">
                <div class="col-xl-6">
                    <!--begin::Input-->
                    <div class="form-group">
                        {!! Form::label('certificate_number_input', __('admin.certificate_number')) !!}
                        {!! Form::text(
                            'certificate_number',
                            isset($booking) ? $booking?->certificate_number ?? null : old('certificate_number'),
                            [
                                'id' => 'certificate_number_input',
                                'class' => 'form-control',
                                'placeholder' => __('admin.certificate_number'),
                            ],
                        ) !!}
                        @error('certificate_number')
                            <span class="form-text text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <!--end::Input-->
                </div>
                <div class="col-xl-6">
                    <!--begin::Select-->
                    <div class="form-group">
                        {!! Form::label('type_of_action_input', __('admin.type_of_action')) !!}
                        {!! Form::select(
                            'type_of_action',
                            $type_of_actions,
                            isset($booking) ? $booking?->type_of_action ?? old('type_of_action') : old('type_of_action'),
                            [
                                'id' => 'type_of_action_input',
                                'class' => 'form-control',
                                'placeholder' => __('admin.choose_action'),
                            ],
                        ) !!}
                        @error('type_of_action')
                            <span class="form-text text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <!--end::Select-->
                </div>
            </div>

            <div class="row">
                <div class="col-xl-6 Inbound_inputs">
                    <!--begin::Date-->
                    <div class="form-group">
                        {{--  < JS Hidden Toggle >  --}}
                        {!! Form::label('discharge_date_input', __('admin.etd')) !!}
                        {!! Form::date(
                            'discharge_date',
                            isset($booking) ? $booking?->discharge_date ?? old('discharge_date') : old('discharge_date'),
                            ['id' => 'discharge_date_input', 'class' => 'form-control'],
                        ) !!}
                        @error('discharge_date')
                            <span class="form-text text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <!--end::Date-->
                </div>

                <div class="col-xl-6 Inbound_inputs">
                    <!--begin::Date-->
                    <div class="form-group">
                        {{--  < JS Hidden Toggle >  --}}
                        {!! Form::label('permit_end_date_input', __('admin.permit_end_date')) !!}
                        {!! Form::date(
                            'permit_end_date',
                            isset($booking) ? $booking?->permit_end_date ?? old('permit_end_date') : old('permit_end_date'),
                            ['id' => 'permit_end_date_input', 'class' => 'form-control'],
                        ) !!}
                        <span class="form-text text-danger" id="permit_end_dateError"></span>
                        @error('permit_end_date')
                            <span class="form-text text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <!--end::Date-->
                </div>
            </div>

            <div class="row justify-content-end">
                <div class="col-xl-2">
                    <button class="btn btn-primary add-container-btn" type="button" data-toggle="collapse"
                        data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample"
                        onclick="">
                        {{ __('admin.add_container') }}
                    </button>
                </div>
            </div>
            @forelse ($groupedContainers as $key => $group)
                @php
                    $branchId = explode('-', $key)[0];
                    $containerCount = explode('-', $key)[1];
                @endphp

                <div class="containers-divs">

                    <div class="row   px-4 py-4 mt-4 mb-4 containers-div" id="containers">

                        <div class="row align-items-center justify-content-between container-1 col-xl-12 ">

                            <div class="col-xl-6 ">
                                <div class="form-group{{ $errors->has('branch_id') ? ' has-error' : '' }}">
                                    {!! Form::label('branch_id', __('admin.branch')) !!}
                                    <select name="containers[0][branch_id]" id="branch_id" class="form-control"
                                        required>
                                        <option disabled">{{ __('admin.select') }}</option>
                                        @foreach ($branches as $id => $name)
                                            <option value="{{ $id }}"
                                                {{ old('containers.0.branch_id') == $id || $branchId == $id ? 'selected class=selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-danger">{{ $errors->first('containers.0.branch_id') }}</small>
                                </div>

                            </div>

                            <div class="col-xl-2 delete-div d-none">
                                <a class="btn btn-danger delete-container">X</a>
                            </div>

                        </div>
                        <div class="row container-1 col-xl-12">
                            <div class="col-xl-6 ">
                                <div class="form-group{{ $errors->has('container_id') ? ' has-error' : '' }}">
                                    {!! Form::label('container_id', __('admin.container')) !!}
                                    <select name="containers[0][container_id]" id="container_id" class="form-control"
                                        required>
                                        <option value="to_be_disabled">{{ __('admin.select') }}</option>
                                        @foreach ($containers_type->all() as $id => $name)
                                            <option value="{{ $id }}"
                                                {{ old('containers.0.container_id') == $id || $group[0]->branch_id == $id ? 'selected class=selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small
                                        class="text-danger">{{ $errors->first('containers.0.container_id') }}</small>
                                </div>

                            </div>
                            <div class="col-xl-6 ">
                                <div class="form-group{{ $errors->has('arrival_date') ? ' has-error' : '' }}">
                                    {!! Form::label('arrival_date', __('admin.arrival_date')) !!}
                                    <input type="date" name="containers[0][arrival_date]"
                                        value="{{ old('containers.0.arrival_date') ?? $group[0]->arrival_date }}" class="form-control" required>
                                    <small
                                        class="text-danger">{{ $errors->first('containers.0.arrival_date') }}</small>
                                </div>

                            </div>
                        </div>
                        <div class="row container-1 col-xl-12 border-bottom mx-0 mb-4">
                            <div class="col-xl-12">
                                <!--begin::Input-->
                                <div class="form-group{{ $errors->has('containers.0.containers_count') ? ' has-error' : '' }}">
                                    {!! Form::label('containers_count_input', __('admin.containers_count')) !!}
                                    <input
                                        type="number"
                                        name="containers[0][containers_count]"
                                        id="containers_count_input"
                                        class="form-control"
                                        placeholder="{{ __('admin.containers_count') }}"
                                        value="{{ old('containers.0.containers_count') ?? $containerCount }}"
                                        required
                                        min="1">
                                    @error('containers.0.containers_count')
                                        <span class="form-text text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!--end::Input-->
                            </div>
                        </div>

                        @for ($i = 1; $i < count(old('containers', [])); $i++)
                            <div class="row justify-content-end col-12 container-1">
                                <div class="col-xl-1">
                                    <button class="btn btn-danger" type="button" data-toggle="collapse"
                                        data-target="#collapseExample" aria-expanded="false"
                                        aria-controls="collapseExample" onclick="removeContainer({{ $i }})">
                                        X
                                    </button>
                                </div>
                            </div>
                            <div class="row container-1 col-xl-12 ">

                                <div class="col-xl-6 ">
                                    <div class="form-group{{ $errors->has('branch_id') ? ' has-error' : '' }}">
                                        {!! Form::label('branch_id', __('admin.branch')) !!}
                                        {!! Form::select(
                                            'containers[{{ $i }}][branch_id]',
                                            array_replace(['to_be_disabled' => __('admin.select')], $branches?->toArray() ?? []),
                                            old("containers.$i.branch_id"),
                                            ['id' => 'branch_id', 'class' => 'form-control', 'required' => 'required'],
                                        ) !!}
                                        <small
                                            class="text-danger">{{ $errors->first("containers.$i.branch_id") }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row container-1 col-xl-12">
                                <div class="col-xl-6 ">
                                    <div class="form-group{{ $errors->has('container_id') ? ' has-error' : '' }}">
                                        {!! Form::label('container_id', __('admin.container')) !!}
                                        {!! Form::select(
                                            'containers[{{ $i }}][container_id]',
                                            array_replace(['to_be_disabled' => __('admin.select')], $containers_type->all()),
                                            old("containers.$i.container_id"),
                                            [
                                                'id' => 'container_id',
                                                'class' => 'form-control',
                                                'required' => 'required',
                                            ],
                                        ) !!}
                                        <small
                                            class="text-danger">{{ $errors->first("containers.$i.container_id") }}</small>
                                    </div>
                                </div>
                                <div class="col-xl-6 ">
                                    <div class="form-group{{ $errors->has('arrival_date') ? ' has-error' : '' }}">

                                        {!! Form::label('arrival_date', __('admin.arrival_date')) !!}
                                        {!! Form::date('containers[{{ $i }}][arrival_date]', old("containers.$i.arrival_date"), [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                        ]) !!}
                                        <small
                                            class="text-danger">{{ $errors->first("containers.$i.arrival_date") }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row container-1 col-xl-12 border-bottom mx-0 mb-4">
                                <div class="col-xl-12">
                                    <!--begin::Input-->
                                    <div class="form-group">
                                        {!! Form::label('containers_count_input', __('admin.containers_count')) !!}
                                        {!! Form::number('containers[{{ $i }}][containers_count]', old("containers.$i.containers_count"), [
                                            'id' => 'containers_count_input',
                                            'class' => 'form-control',
                                            'placeholder' => __('admin.containers_count'),
                                            'min' => 1,
                                            'required' => 'required',
                                        ]) !!}
                                        @error("containers.$i.containers_count")
                                            <span class="form-text text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <!--end::Input-->
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

            @empty

                <div class="containers-divs">
                    <div class="row   px-4 py-4 mt-4 mb-4 containers-div" id="containers">

                        <div class="row align-items-center justify-content-between container-1 col-xl-12 ">

                            <div class="col-xl-6 ">
                                <div class="form-group{{ $errors->has('branch_id') ? ' has-error' : '' }}">
                                    {!! Form::label('branch_id', __('admin.branch')) !!}
                                    {!! Form::select(
                                        'containers[0][branch_id]',
                                        array_replace(['to_be_disabled' => __('admin.select')], $branches?->toArray() ?? []),
                                        old('containers.0.branch_id'),
                                        ['id' => 'branch_id', 'class' => 'form-control', 'required' => 'required'],
                                    ) !!}
                                    <small class="text-danger">{{ $errors->first('containers.0.branch_id') }}</small>
                                </div>
                            </div>

                            <div class="col-xl-2 delete-div d-none">
                                <a class="btn btn-danger delete-container">X</a>
                            </div>

                        </div>
                        <div class="row container-1 col-xl-12">
                            <div class="col-xl-6 ">
                                <div class="form-group{{ $errors->has('container_id') ? ' has-error' : '' }}">
                                    {!! Form::label('container_id', __('admin.container')) !!}
                                    {!! Form::select(
                                        'containers[0][container_id]',
                                        array_replace(['to_be_disabled' => __('admin.select')], $containers_type->all()),
                                        old('containers.0.container_id'),
                                        [
                                            'id' => 'container_id',
                                            'class' => 'form-control',
                                            'required' => 'required',
                                        ],
                                    ) !!}
                                    <small
                                        class="text-danger">{{ $errors->first('containers.0.container_id') }}</small>
                                </div>
                            </div>
                            <div class="col-xl-6 ">
                                <div class="form-group{{ $errors->has('arrival_date') ? ' has-error' : '' }}">
                                    {!! Form::label('arrival_date', __('admin.arrival_date')) !!}
                                    {!! Form::date('containers[0][arrival_date]', old('containers.0.arrival_date'), [
                                        'class' => 'form-control',
                                        'required' => 'required',
                                    ]) !!}
                                    <small
                                        class="text-danger">{{ $errors->first('containers.0.arrival_date') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="row container-1 col-xl-12 border-bottom mx-0 mb-4">
                            <div class="col-xl-12">
                                <!--begin::Input-->
                                <div class="form-group">
                                    {!! Form::label('containers_count_input', __('admin.containers_count')) !!}
                                    {!! Form::number('containers[0][containers_count]', old('containers.0.containers_count'), [
                                        'id' => 'containers_count_input',
                                        'class' => 'form-control',
                                        'placeholder' => __('admin.containers_count'),
                                        'required' => 'required',
                                        'min' => 1,
                                    ]) !!}
                                    @error('containers.0.containers_count')
                                        <span class="form-text text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <!--end::Input-->
                            </div>
                        </div>

                        @for ($i = 1; $i < count(old('containers', [])); $i++)
                            <div class="row justify-content-end col-12 container-1">
                                <div class="col-xl-1">
                                    <button class="btn btn-danger" type="button" data-toggle="collapse"
                                        data-target="#collapseExample" aria-expanded="false"
                                        aria-controls="collapseExample"
                                        onclick="removeContainer({{ $i }})">
                                        X
                                    </button>
                                </div>
                            </div>
                            <div class="row container-1 col-xl-12 ">

                                <div class="col-xl-6 ">
                                    <div class="form-group{{ $errors->has('branch_id') ? ' has-error' : '' }}">
                                        {!! Form::label('branch_id', __('admin.branch')) !!}
                                        {!! Form::select(
                                            'containers[{{ $i }}][branch_id]',
                                            array_replace(['to_be_disabled' => __('admin.select')], $branches?->toArray() ?? []),
                                            old("containers.$i.branch_id"),
                                            ['id' => 'branch_id', 'class' => 'form-control', 'required' => 'required'],
                                        ) !!}
                                        <small
                                            class="text-danger">{{ $errors->first("containers.$i.branch_id") }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row container-1 col-xl-12">
                                <div class="col-xl-6 ">
                                    <div class="form-group{{ $errors->has('container_id') ? ' has-error' : '' }}">
                                        {!! Form::label('container_id', __('admin.container')) !!}
                                        {!! Form::select(
                                            'containers[{{ $i }}][container_id]',
                                            array_replace(['to_be_disabled' => __('admin.select')], $containers_type->all()),
                                            old("containers.$i.container_id"),
                                            [
                                                'id' => 'container_id',
                                                'class' => 'form-control',
                                                'required' => 'required',
                                            ],
                                        ) !!}
                                        <small
                                            class="text-danger">{{ $errors->first("containers.$i.container_id") }}</small>
                                    </div>
                                </div>
                                <div class="col-xl-6 ">
                                    <div class="form-group{{ $errors->has('arrival_date') ? ' has-error' : '' }}">

                                        {!! Form::label('arrival_date', __('admin.arrival_date')) !!}
                                        {!! Form::date('containers[{{ $i }}][arrival_date]', old("containers.$i.arrival_date"), [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                        ]) !!}
                                        <small
                                            class="text-danger">{{ $errors->first("containers.$i.arrival_date") }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row container-1 col-xl-12 border-bottom mx-0 mb-4">
                                <div class="col-xl-12">
                                    <!--begin::Input-->
                                    <div class="form-group">
                                        {!! Form::label('containers_count_input', __('admin.containers_count')) !!}
                                        {!! Form::number('containers[{{ $i }}][containers_count]', old("containers.$i.containers_count"), [
                                            'id' => 'containers_count_input',
                                            'class' => 'form-control',
                                            'placeholder' => __('admin.containers_count'),
                                            'min' => 1,
                                            'required' => 'required',
                                        ]) !!}
                                        @error("containers.$i.containers_count")
                                            <span class="form-text text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <!--end::Input-->
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            @endforelse

            {!! Form::submit(__('admin.submit'), ['class' => 'btn btn-success font-weight-bolder text-uppercase px-9 py-4']) !!}
            {!! Form::close() !!}

        </div>
    </div>
    <!--end: Wizard Body-->
</div>
@push('js')
    <script>
        var company_employees = {!! json_encode($company_employees) !!};

        $('#company_id').on('change', loadEmployees);

        function loadEmployees() {
            var company_id = $('#company_id').val();

            $('#employee_id option').remove();
            $('#employee_id').append(
                '<option value="to_be_disabled" disabled="disabled" selected="selected">{{ __('admin.select') }}</option>'
            );
            var available_employees = company_employees[company_id];
            for (const emp in available_employees) {
                $('#employee_id').append(`<option value="${emp}">${available_employees[emp]}</option>`)
            }
        }

        $(document).ready(function() {
            // Add new container
            $(document).on('click', '.add-container-btn', function() {
                var newContainer = $('.containers-div').first().clone();
                newContainer.find('.delete-div').removeClass('d-none');
                newContainer.appendTo('.containers-div').parent();
            });

            // Delete container
            $(document).on('click', '.delete-container', function() {
                $(this).closest('.containers-div').remove();
            });
        });
    </script>
    <script>
        $(function() {
            var dtToday = new Date();

            var month = dtToday.getMonth() + 1;
            var day = dtToday.getDate();
            var year = dtToday.getFullYear();
            if (month < 10)
                month = '0' + month.toString();
            if (day < 10)
                day = '0' + day.toString();

            var maxDate = year + '-' + month + '-' + day;

            // or instead:
            // var maxDate = dtToday.toISOString().substr(0, 10);
            $('#permit_end_date_input').attr('min', maxDate);
            $('#discharge_date_input').attr('min', maxDate);
            $('.arrival_date_input').attr('min', maxDate);
        });

        @if (!is_null(old('type_of_action')) || isset($booking))
            var type_of_action =
                "{{ isset($booking) ? $booking?->type_of_action ?? old('type_of_action') : old('type_of_action') }}";
            thirdStep(type_of_action);
        @else
            $('.Inbound_inputs').hide();
        @endif

        @if (isset($booking) || !is_null(old('company_id')))
            companyEmployee(`{{ $booking->company_id ?? old('company_id') }}`);
        @endif

        // @if (
            (isset($booking) && !is_null($booking->bookingContainers) && count($booking->bookingContainers) > 0) ||
                !is_null(old('factory_id')))
        //     var factory_id = "{{ $booking->thirdBookings?->factory_id ?? old('factory_id') }}";
        //     //branches(factory_id);
        // @endif

        $('#type_of_action_input').on('change', function() {
            var type_of_action = $(this).val();
            thirdStep(type_of_action);
        });

        function thirdStep(type_of_action) {
            if (type_of_action == 0) {
                // ----------- OutBound -----------
                $('.Inbound_inputs').hide();
                var html = `@include('admin.bookings.thirdStep.outbound')`;
                $('#action_section').html(html);
                // ----------- \OutBound -----------
            } else if (type_of_action == 1) {
                // ----------- InBound -----------
                $('.Inbound_inputs').show();
                var html = `@include('admin.bookings.thirdStep.inbound')`;
                $('#action_section').html(html);
                // ----------- \InBound -----------
            } else if (type_of_action == 2) {
                // ----------- Clearance -----------
                $('.Inbound_inputs').hide();
                var html = `@include('admin.bookings.thirdStep.clearance')`;
                $('#action_section').html(html);
                // ----------- \Clearance -----------
            }
        }

        function branches(id, index) {
            console.log(id, index);
            var url = "{{ route('factory.branches', ':id') }}",
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
                method: 'GET',
                success: function(res) {
                    $('#row_' + index).find('select#branches_' + index).append(
                        `<option value="">{{ __('admin.choose_branch') }}</option>`);
                    //console.log($('#row_'+ index).find('select#branches_' + index), jQuery('#row_'+ index).find('tr td:eq(4)'));
                    $.each(res['data'], function(i, v) {
                        $('#row_' + index).find('select#branches_' + index).append(
                            `<option value="${i}" id="branch_${i}">${v}</option>`);
                    });
                }
            })

        }

        function remove(index) {
            $('#row_' + index).remove();
        }
    </script>
@endpush
