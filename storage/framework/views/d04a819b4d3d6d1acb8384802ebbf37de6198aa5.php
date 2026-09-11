<div class="card-body p-0" style="direction:rtl">
    <div class="row justify-content-center py-10 px-8 py-lg-12 px-lg-10">
        <div class="col-xl-12 col-xxl-7">

            <?php if($method == 'POST'): ?>
                <?php echo Form::open([
                    'url' => $action,
                    'method' => $method,
                    'enctype' => 'multipart/form-data',
                    'id' => 'kt_form',
                    'class' => 'form',
                    'files' => true,
                ]); ?>

            <?php elseif($method == 'PUT'): ?>
                <?php echo Form::model($booking, [
                    'url' => [$action],
                    'method' => $method,
                    'enctype' => 'multipart/form-data',
                    'id' => 'kt_form',
                    'files' => true,
                ]); ?>

            <?php endif; ?>

            

            <div class="form-group">
                <label for=""><?php echo e(__('main.company')); ?></label>
                <select name="company_id" id="company_id" class="form-control selectpicker select-company">
                    <option value=""><?php echo e(__('admin.select')); ?></option>
                    <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($company->id); ?>" <?php if(old('company_id') == $company->id || (isset($booking) && $booking->company_id == $company->id)): ?> selected <?php endif; ?>>
                            <?php echo e($company->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>


            <!--begin::Select-->
            <div class="form-group<?php echo e($errors->has('employee_id') ? ' has-error' : ''); ?>">
                <?php echo Form::label('employee_id', __('main.employee')); ?>

                <?php echo Form::select(
                    'employee_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $employees?->all() ?? []),
                    old('employee_id'),
                    [
                        'id' => 'employee_id',
                        'class' => 'form-control select-employee',
                        'required' => 'required',
                    ],
                ); ?>

                <small class="text-danger"><?php echo e($errors->first('employee_id')); ?></small>
            </div>
            <!--end::Select-->

            <!--begin::Input-->
            <div class="form-group">
                <?php echo Form::label('shipping_agent_input', __('admin.shipping_agency')); ?>

                <?php echo Form::select(
                    'shipping_agent_id',
                    $shipping_agents,
                    isset($booking) ? $booking?->shipping_agent_id ?? null : old('shipping_agent_id'),
                    [
                        'id' => 'shipping_agent_input',
                        'class' => 'form-control',
                        'placeholder' => __('admin.shipping_agency'),
                    ],
                ); ?>

                <?php $__errorArgs = ['shipping_agent'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="form-text text-danger"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="form-group<?php echo e($errors->has('factory_id') ? ' has-error' : ''); ?>">
                <?php echo Form::label('factory_id', __('admin.factory')); ?>

                <?php echo Form::select(
                    'factory_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $factories->all()),
                    old('factory_id'),
                    [
                        'id' => 'factory_id',
                        'class' => 'form-control',
                        'required' => 'required',
                    ],
                ); ?>

                <small class="text-danger"><?php echo e($errors->first('factory_id')); ?></small>
            </div>
            <!--end::Input-->
            <!--begin::Input-->
            <div class="form-group">
                <?php echo Form::label('booking_number_input', __('admin.booking_number')); ?>

                <?php echo Form::text('booking_number', isset($booking) ? $booking?->booking_number ?? null : old('booking_number'), [
                    'id' => 'booking_number_input',
                    'class' => 'form-control',
                    'placeholder' => __('admin.booking_number'),
                    'min' => 1,
                    'maxLength' => 255,
                ]); ?>

                <?php $__errorArgs = ['booking_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="form-text text-danger"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="form-group">
                <?php echo Form::label('employee_name_input', __('admin.responsible_employee')); ?>

                <?php echo Form::text('employee_name', isset($booking) ? $booking?->employee_name ?? null : old('employee_name'), [
                    'id' => 'employee_name_input',
                    'class' => 'form-control',
                    'placeholder' => __('admin.responsible_employee'),
                    'minLength' => 3,
                    'maxLength' => 255,
                ]); ?>

                <?php $__errorArgs = ['employee_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="form-text text-danger"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>



            <!--end::Input-->
            <div class="row">
                <div class="col-xl-6">
                    <!--begin::Input-->
                    <div class="form-group">
                        <?php echo Form::label('certificate_number_input', __('admin.certificate_number')); ?>

                        <?php echo Form::text(
                            'certificate_number',
                            isset($booking) ? $booking?->certificate_number ?? null : old('certificate_number'),
                            [
                                'id' => 'certificate_number_input',
                                'class' => 'form-control',
                                'placeholder' => __('admin.certificate_number'),
                            ],
                        ); ?>

                        <?php $__errorArgs = ['certificate_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="form-text text-danger"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <!--end::Input-->
                </div>
                <div class="col-xl-6">
                    <!--begin::Select-->
                    <div class="form-group">
                        <?php echo Form::label('type_of_action_input', __('admin.type_of_action')); ?>

                        <?php echo Form::select(
                            'type_of_action',
                            $type_of_actions,
                            isset($booking) ? $booking?->type_of_action ?? old('type_of_action') : old('type_of_action'),
                            [
                                'id' => 'type_of_action_input',
                                'class' => 'form-control',
                                'placeholder' => __('admin.choose_action'),
                            ],
                        ); ?>

                        <?php $__errorArgs = ['type_of_action'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="form-text text-danger"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <!--end::Select-->
                </div>
            </div>

            <div class="row">
                <div class="col-xl-6 Inbound_inputs">
                    <!--begin::Date-->
                    <div class="form-group">
                        
                        <?php echo Form::label('discharge_date_input', __('admin.etd')); ?>

                        <?php echo Form::date(
                            'discharge_date',
                            isset($booking) ? $booking?->discharge_date ?? old('discharge_date') : old('discharge_date'),
                            ['id' => 'discharge_date_input', 'class' => 'form-control'],
                        ); ?>

                        <?php $__errorArgs = ['discharge_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="form-text text-danger"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <!--end::Date-->
                </div>

                <div class="col-xl-6 Inbound_inputs">
                    <!--begin::Date-->
                    <div class="form-group">
                        
                        <?php echo Form::label('permit_end_date_input', __('admin.permit_end_date')); ?>

                        <?php echo Form::date(
                            'permit_end_date',
                            isset($booking) ? $booking?->permit_end_date ?? old('permit_end_date') : old('permit_end_date'),
                            ['id' => 'permit_end_date_input', 'class' => 'form-control'],
                        ); ?>

                        <span class="form-text text-danger" id="permit_end_dateError"></span>
                        <?php $__errorArgs = ['permit_end_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="form-text text-danger"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <!--end::Date-->
                </div>
                <div class="form-group col-md-6">
                    <label for="image" class="font-weight-bold">اختر صورة أو ملف</label>
                    <input type="file" name="image" id="image" class="form-control">
                </div>

                <div class="form-group col-md-6">
                    <label for="type" class="font-weight-bold">نوع الملف</label>
                    <select name="type" id="type" class="form-control">
                        <option>اختر نوع الملف</option>
                        <option value="0">جواب تخصيص</option>
                        <option value="1">صورة الحاوية</option>
                        <option value="6">جواب التحميل</option>
                        <option value="5">صورة سيل ملاحي</option>
                        <option value="4">جواب التعتيق</option>
                        <option value="8">اذن شحن</option>
                        <option value="9">أخرى</option>
                    </select>
                </div>
            </div>

            <div class="row justify-content-end">
                <div class="col-xl-2">
                    <button class="btn btn-primary add-container-btn" type="button" data-toggle="collapse"
                        data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample"
                        onclick="">
                        <?php echo e(__('admin.add_container')); ?>

                    </button>
                </div>
            </div>
            <?php $__empty_1 = true; $__currentLoopData = $groupedContainers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $branchId = explode('-', $key)[0];
                    $containerCount = explode('-', $key)[1];
                ?>

                <div class="containers-divs">

                    <div class="row px-4 py-4 mt-4 mb-4 containers-div booking-container-block" id="containers">

                        <div class="row align-items-center justify-content-between container-1 col-xl-12 ">

                            <div class="col-xl-6 ">
                                <div class="form-group<?php echo e($errors->has('branch_id') ? ' has-error' : ''); ?>">
                                    <?php echo Form::label('branch_id', __('admin.branch')); ?>

                                    <select name="containers[0][branch_id]" id="branch_id" class="form-control"
                                        required>
                                        <option value="" disabled><?php echo e(__('admin.select')); ?></option>
                                        <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($id); ?>"
                                                <?php echo e(old('containers.0.branch_id') == $id || $branchId == $id ? 'selected class=selected' : ''); ?>>
                                                <?php echo e($name); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <small class="text-danger"><?php echo e($errors->first('containers.0.branch_id')); ?></small>
                                </div>

                            </div>

                            <div class="col-xl-2 delete-div d-none">
                                <a class="btn btn-danger delete-container">X</a>
                            </div>

                        </div>
                        <div class="row container-1 col-xl-12">
                            <div class="col-xl-6 ">
                                <div class="form-group<?php echo e($errors->has('container_id') ? ' has-error' : ''); ?>">
                                    <?php echo Form::label('container_id', __('admin.container')); ?>

                                    <select name="containers[0][container_id]" id="container_id" class="form-control"
                                        required>
                                        <option value="to_be_disabled"><?php echo e(__('admin.select')); ?></option>
                                        <?php $__currentLoopData = $containers_type->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($id); ?>"
                                                <?php echo e(old('containers.0.container_id') == $id || (isset($group[0]) && $group[0]->container_id == $id) ? 'selected' : ''); ?>>
                                                <?php echo e($name); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <small
                                        class="text-danger"><?php echo e($errors->first('containers.0.container_id')); ?></small>
                                </div>

                            </div>
                            <div class="col-xl-6 ">
                                <div class="form-group<?php echo e($errors->has('arrival_date') ? ' has-error' : ''); ?>">
                                    <?php echo Form::label('arrival_date', __('admin.arrival_date')); ?>

                                    <input type="date" name="containers[0][arrival_date]"
                                        value="<?php echo e(old('containers.0.arrival_date') ?? $group[0]->arrival_date); ?>" class="form-control" required>
                                    <small
                                        class="text-danger"><?php echo e($errors->first('containers.0.arrival_date')); ?></small>
                                </div>

                            </div>
                        </div>
                        <div class="row container-1 col-xl-12 border-bottom mx-0 mb-4">
                            <div class="col-xl-12">
                                <!--begin::Input-->
                                <div class="form-group<?php echo e($errors->has('containers.0.containers_count') ? ' has-error' : ''); ?>">
                                    <?php echo Form::label('containers_count_input', __('admin.containers_count')); ?>

                                    <input
                                        type="number"
                                        name="containers[0][containers_count]"
                                        id="containers_count_input"
                                        class="form-control"
                                        placeholder="<?php echo e(__('admin.containers_count')); ?>"
                                        value="<?php echo e(old('containers.0.containers_count') ?? $containerCount); ?>"
                                        required
                                        min="1">
                                    <?php $__errorArgs = ['containers.0.containers_count'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="form-text text-danger"><?php echo e($message); ?></span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <!--end::Input-->
                            </div>
                        </div>

                        <?php for($i = 1; $i < count(old('containers', [])); $i++): ?>
                            <div class="row justify-content-end col-12 container-1">
                                <div class="col-xl-1">
                                    <button class="btn btn-danger" type="button" data-toggle="collapse"
                                        data-target="#collapseExample" aria-expanded="false"
                                        aria-controls="collapseExample" onclick="removeContainer(<?php echo e($i); ?>)">
                                        X
                                    </button>
                                </div>
                            </div>
                            <div class="row container-1 col-xl-12 ">

                                <div class="col-xl-6 ">
                                    <div class="form-group<?php echo e($errors->has('branch_id') ? ' has-error' : ''); ?>">
                                        <?php echo Form::label('branch_id', __('admin.branch')); ?>

                                        <?php echo Form::select(
                                            'containers[<?php echo e($i); ?>][branch_id]',
                                            array_replace(['to_be_disabled' => __('admin.select')], $branches?->toArray() ?? []),
                                            old("containers.$i.branch_id"),
                                            ['id' => 'branch_id', 'class' => 'form-control', 'required' => 'required'],
                                        ); ?>

                                        <small
                                            class="text-danger"><?php echo e($errors->first("containers.$i.branch_id")); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="row container-1 col-xl-12">
                                <div class="col-xl-6 ">
                                    <div class="form-group<?php echo e($errors->has('container_id') ? ' has-error' : ''); ?>">
                                        <?php echo Form::label('container_id', __('admin.container')); ?>

                                        <?php echo Form::select(
                                            'containers[<?php echo e($i); ?>][container_id]',
                                            array_replace(['to_be_disabled' => __('admin.select')], $containers_type->all()),
                                            old("containers.$i.container_id"),
                                            [
                                                'id' => 'container_id',
                                                'class' => 'form-control',
                                                'required' => 'required',
                                            ],
                                        ); ?>

                                        <small
                                            class="text-danger"><?php echo e($errors->first("containers.$i.container_id")); ?></small>
                                    </div>
                                </div>
                                <div class="col-xl-6 ">
                                    <div class="form-group<?php echo e($errors->has('arrival_date') ? ' has-error' : ''); ?>">

                                        <?php echo Form::label('arrival_date', __('admin.arrival_date')); ?>

                                        <?php echo Form::date('containers[<?php echo e($i); ?>][arrival_date]', old("containers.$i.arrival_date"), [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                        ]); ?>

                                        <small
                                            class="text-danger"><?php echo e($errors->first("containers.$i.arrival_date")); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="row container-1 col-xl-12 border-bottom mx-0 mb-4">
                                <div class="col-xl-12">
                                    <!--begin::Input-->
                                    <div class="form-group">
                                        <?php echo Form::label('containers_count_input', __('admin.containers_count')); ?>

                                        <?php echo Form::number('containers[<?php echo e($i); ?>][containers_count]', old("containers.$i.containers_count"), [
                                            'id' => 'containers_count_input',
                                            'class' => 'form-control',
                                            'placeholder' => __('admin.containers_count'),
                                            'min' => 1,
                                            'required' => 'required',
                                        ]); ?>

                                        <?php $__errorArgs = ["containers.$i.containers_count"];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <span class="form-text text-danger"><?php echo e($message); ?></span>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <!--end::Input-->
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                <div class="containers-divs">
                    <div class="row px-4 py-4 mt-4 mb-4 containers-div booking-container-block" id="containers">

                        <div class="row align-items-center justify-content-between container-1 col-xl-12 ">

                            <div class="col-xl-6 ">
                                <div class="form-group<?php echo e($errors->has('branch_id') ? ' has-error' : ''); ?>">
                                    <?php echo Form::label('branch_id', __('admin.branch')); ?>

                                    <?php echo Form::select(
                                        'containers[0][branch_id]',
                                        array_replace(['to_be_disabled' => __('admin.select')], $branches?->toArray() ?? []),
                                        old('containers.0.branch_id'),
                                        ['id' => 'branch_id', 'class' => 'form-control', 'required' => 'required'],
                                    ); ?>

                                    <small class="text-danger"><?php echo e($errors->first('containers.0.branch_id')); ?></small>
                                </div>
                            </div>

                            <div class="col-xl-2 delete-div d-none">
                                <a class="btn btn-danger delete-container">X</a>
                            </div>

                        </div>
                        <div class="row container-1 col-xl-12">
                            <div class="col-xl-6 ">
                                <div class="form-group<?php echo e($errors->has('container_id') ? ' has-error' : ''); ?>">
                                    <?php echo Form::label('container_id', __('admin.container')); ?>

                                    <?php echo Form::select(
                                        'containers[0][container_id]',
                                        array_replace(['to_be_disabled' => __('admin.select')], $containers_type->all()),
                                        old('containers.0.container_id'),
                                        [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                        ],
                                    ); ?>

                                    <small
                                        class="text-danger"><?php echo e($errors->first('containers.0.container_id')); ?></small>
                                </div>
                            </div>
                            <div class="col-xl-6 ">
                                <div class="form-group<?php echo e($errors->has('arrival_date') ? ' has-error' : ''); ?>">
                                    <?php echo Form::label('arrival_date', __('admin.arrival_date')); ?>

                                    <?php echo Form::date('containers[0][arrival_date]', old('containers.0.arrival_date'), [
                                        'class' => 'form-control',
                                        'required' => 'required',
                                    ]); ?>

                                    <small
                                        class="text-danger"><?php echo e($errors->first('containers.0.arrival_date')); ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="row container-1 col-xl-12 border-bottom mx-0 mb-4">
                            <div class="col-xl-12">
                                <!--begin::Input-->
                                <div class="form-group">
                                    <?php echo Form::label('containers_count_input', __('admin.containers_count')); ?>

                                    <?php echo Form::number('containers[0][containers_count]', old('containers.0.containers_count'), [
                                        'id' => 'containers_count_input',
                                        'class' => 'form-control',
                                        'placeholder' => __('admin.containers_count'),
                                        'required' => 'required',
                                        'min' => 1,
                                    ]); ?>

                                    <?php $__errorArgs = ['containers.0.containers_count'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="form-text text-danger"><?php echo e($message); ?></span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <!--end::Input-->
                            </div>
                        </div>

                        <?php for($i = 1; $i < count(old('containers', [])); $i++): ?>
                            <div class="row justify-content-end col-12 container-1">
                                <div class="col-xl-1">
                                    <button class="btn btn-danger" type="button" data-toggle="collapse"
                                        data-target="#collapseExample" aria-expanded="false"
                                        aria-controls="collapseExample"
                                        onclick="removeContainer(<?php echo e($i); ?>)">
                                        X
                                    </button>
                                </div>
                            </div>
                            <div class="row container-1 col-xl-12 ">

                                <div class="col-xl-6 ">
                                    <div class="form-group<?php echo e($errors->has('branch_id') ? ' has-error' : ''); ?>">
                                        <?php echo Form::label('branch_id', __('admin.branch')); ?>

                                        <?php echo Form::select(
                                            'containers[<?php echo e($i); ?>][branch_id]',
                                            array_replace(['to_be_disabled' => __('admin.select')], $branches?->toArray() ?? []),
                                            old("containers.$i.branch_id"),
                                            ['id' => 'branch_id', 'class' => 'form-control', 'required' => 'required'],
                                        ); ?>

                                        <small
                                            class="text-danger"><?php echo e($errors->first("containers.$i.branch_id")); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="row container-1 col-xl-12">
                                <div class="col-xl-6 ">
                                    <div class="form-group<?php echo e($errors->has('container_id') ? ' has-error' : ''); ?>">
                                        <?php echo Form::label('container_id', __('admin.container')); ?>

                                        <?php echo Form::select(
                                            'containers[<?php echo e($i); ?>][container_id]',
                                            array_replace(['to_be_disabled' => __('admin.select')], $containers_type->all()),
                                            old("containers.$i.container_id"),
                                            [
                                                'id' => 'container_id',
                                                'class' => 'form-control',
                                                'required' => 'required',
                                            ],
                                        ); ?>

                                        <small
                                            class="text-danger"><?php echo e($errors->first("containers.$i.container_id")); ?></small>
                                    </div>
                                </div>
                                <div class="col-xl-6 ">
                                    <div class="form-group<?php echo e($errors->has('arrival_date') ? ' has-error' : ''); ?>">

                                        <?php echo Form::label('arrival_date', __('admin.arrival_date')); ?>

                                        <?php echo Form::date('containers[<?php echo e($i); ?>][arrival_date]', old("containers.$i.arrival_date"), [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                        ]); ?>

                                        <small
                                            class="text-danger"><?php echo e($errors->first("containers.$i.arrival_date")); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="row container-1 col-xl-12 border-bottom mx-0 mb-4">
                                <div class="col-xl-12">
                                    <!--begin::Input-->
                                    <div class="form-group">
                                        <?php echo Form::label('containers_count_input', __('admin.containers_count')); ?>

                                        <?php echo Form::number('containers[<?php echo e($i); ?>][containers_count]', old("containers.$i.containers_count"), [
                                            'id' => 'containers_count_input',
                                            'class' => 'form-control',
                                            'placeholder' => __('admin.containers_count'),
                                            'min' => 1,
                                            'required' => 'required',
                                        ]); ?>

                                        <?php $__errorArgs = ["containers.$i.containers_count"];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <span class="form-text text-danger"><?php echo e($message); ?></span>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <!--end::Input-->
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="booking-submit-wrap">
                <button type="submit" class="booking-submit-btn">
                    <i class="fas fa-check"></i>
                    <?php echo e(__('admin.submit')); ?>

                </button>
            </div>
            <?php echo Form::close(); ?>


        </div>
    </div>
    <!--end: Wizard Body-->
</div>
<?php $__env->startPush('js'); ?>
    <script>
        var company_employees = <?php echo json_encode($company_employees); ?>;

        $('#company_id').on('change', function() { loadEmployees($(this).val()); });

        function loadEmployees(company_id) {
            company_id = company_id || $('#company_id').val();
            var $empSelect = $('#employee_id');
            if (!$empSelect.length) return;
            $empSelect.find('option').remove();
            $empSelect.append(
                '<option value="to_be_disabled" disabled="disabled" selected="selected"><?php echo e(__('admin.select')); ?></option>'
            );
            var available_employees = company_employees[company_id];
            if (available_employees) {
                for (const emp in available_employees) {
                    $empSelect.append('<option value="' + emp + '">' + available_employees[emp] + '</option>');
                }
            }
        }

        function companyEmployee(companyId) {
            if (!companyId) return;
            loadEmployees(companyId);
        }

        $(document).ready(function () {
    let containerIndex = $(".containers-div").length; // Track index for new containers

    // Add new container
    $(document).on("click", ".add-container-btn", function (e) {
        e.preventDefault();

        // Get the last container
        let lastContainer = $(".containers-div").last();
        let newContainer = lastContainer.clone(); // Clone the last container

        // Increment the index for the new container
        containerIndex++;

        // Update input names and IDs for uniqueness
        newContainer.find("select, input").each(function () {
            let name = $(this).attr("name");
            let id = $(this).attr("id");

            if (name) {
                // Replace the index in the name (e.g., containers[0][branch_id] -> containers[1][branch_id])
                let newName = name.replace(/\[\d+\]/, "[" + containerIndex + "]");
                $(this).attr("name", newName);
            }
            if (id) {
                // Replace the index in the ID (e.g., branch_id_0 -> branch_id_1)
                let newId = id.replace(/\d+/, containerIndex);
                $(this).attr("id", newId);
            }

            // Clear values for inputs
            if ($(this).is("input")) {
                $(this).val("");
            }
        });

        // Show the delete button for the new container
        newContainer.find(".delete-div").removeClass("d-none");

        // Append the new container after the last one
        lastContainer.after(newContainer);
    });

    // Delete container
    $(document).on("click", ".delete-container", function () {
        if ($(".containers-div").length > 1) {
            $(this).closest(".containers-div").remove();
        }
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

        <?php if(!is_null(old('type_of_action')) || isset($booking)): ?>
            var type_of_action =
                "<?php echo e(isset($booking) ? $booking?->type_of_action ?? old('type_of_action') : old('type_of_action')); ?>";
            thirdStep(type_of_action);
        <?php else: ?>
            $('.Inbound_inputs').hide();
        <?php endif; ?>

        <?php if(isset($booking) || !is_null(old('company_id'))): ?>
            companyEmployee(`<?php echo e($booking->company_id ?? old('company_id')); ?>`);
            <?php if(isset($booking) && $booking->employee_id): ?>
            $('#employee_id').val('<?php echo e($booking->employee_id); ?>');
            <?php elseif(old('employee_id')): ?>
            $('#employee_id').val('<?php echo e(old('employee_id')); ?>');
            <?php endif; ?>
        <?php endif; ?>

        // <?php if(
            (isset($booking) && !is_null($booking->bookingContainers) && count($booking->bookingContainers) > 0) ||
                !is_null(old('factory_id'))): ?>
        //     var factory_id = "<?php echo e($booking->thirdBookings?->factory_id ?? old('factory_id')); ?>";
        //     //branches(factory_id);
        // <?php endif; ?>

        $('#type_of_action_input').on('change', function() {
            var type_of_action = $(this).val();
            thirdStep(type_of_action);
        });

        function thirdStep(type_of_action) {
            if (type_of_action == 0) {
                // ----------- OutBound -----------
                $('.Inbound_inputs').hide();
                var html = `<?php echo $__env->make('admin.bookings.thirdStep.outbound', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>`;
                $('#action_section').html(html);
                // ----------- \OutBound -----------
            } else if (type_of_action == 1) {
                // ----------- InBound -----------
                $('.Inbound_inputs').show();
                var html = `<?php echo $__env->make('admin.bookings.thirdStep.inbound', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>`;
                $('#action_section').html(html);
                // ----------- \InBound -----------
            } else if (type_of_action == 2) {
                // ----------- Clearance -----------
                $('.Inbound_inputs').hide();
                var html = `<?php echo $__env->make('admin.bookings.thirdStep.clearance', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>`;
                $('#action_section').html(html);
                // ----------- \Clearance -----------
            }
        }

        function branches(id, index) {
            console.log(id, index);
            var url = "<?php echo e(route('factory.branches', ':id')); ?>",
                url = url.replace(':id', id);
            var token = '<?php echo e(csrf_token()); ?>';

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
                        `<option value=""><?php echo e(__('admin.choose_branch')); ?></option>`);
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
<?php $__env->stopPush(); ?>
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/bookings/form.blade.php ENDPATH**/ ?>