<h4 class="mb-10 font-weight-bold text-dark">
    <?php echo e(__('admin.container_information')); ?>

</h4>

<div class="row">
    <?php echo Form::open([
        'url' => ['javascript:void(0)'],
        'method' => null,
        'id' => 'inbound_form',
        'onclick' => 'outbound_submit_form()',
    ]); ?>

    <div class="col-xl-4">
        <!--begin::Input-->
        <div class="form-group">
            <?php echo Form::label('containers_number_input', __('admin.containers_number')); ?>

            <?php echo Form::number(
                'containers_number',
                isset($booking) ? $booking->bookingContainers?->count() ?? old('containers_number') : old('containers_number'),
                [
                    'id' => 'containers_number_input',
                    'class' => 'form-control',
                    'placeholder' => __('admin.containers_number'),
                    'min' => 1,
                ],
            ); ?>

            <?php $__errorArgs = ['containers_number'];
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
    
    <div class="col-xl-1">
        <!--begin::Select -->
        <div class="form-group">
            <?php echo Form::button(__('admin.add'), [
                'class' => 'btn btn-primary',
                'id' => 'submit_inbound_form',
                'onclick' => 'outbound_submit_form()',
            ]); ?>

        </div>
        <!--end::Select-->
    </div>
    <?php echo Form::close(); ?>

</div>

<div>
    <table id="example" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th><?php echo e(__('admin.container_id')); ?></th>
                <th><?php echo e(__('admin.navigational_torrent')); ?></th>
                <th><?php echo e(__('admin.container_no')); ?></th>
                <th><?php echo e(__('admin.container_type')); ?></th>
                <th><?php echo e(__('admin.branches')); ?></th>
                <th><?php echo e(__('admin.arrival_date')); ?></th>
                <th><?php echo e(__('admin.actions')); ?></th>
            </tr>
        </thead>
        <tbody id="tableRows">
            <?php if(isset($booking) && !is_null($booking->bookingContainers)): ?>
                <?php $__currentLoopData = $booking->bookingContainers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $bookingContainer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr id="row_<?php echo e($key + 1); ?>" data-id="<?php echo e($key + 1); ?>">
                        <input type="hidden" name="factoriesIDs[]" class="form-control"
                            value="<?php echo e($bookingContainer->branch?->factory?->id); ?>">
                        <?php if($bookingContainer->container): ?>
                            <input type="hidden" name="containers[]" class="form-control"
                                value="<?php echo e($bookingContainer->container?->id); ?>">
                        <?php endif; ?>
                        <td><?php echo e($key + 1); ?></td>
                        <td>
                            <?php echo Form::text('sail_of_numbers[]', $bookingContainer->sail_of_number, [
                                'class' => 'form-control',
                                'placeholder' => __('admin.navigational_torrent'),
                            ]); ?>

                        </td>
                        <td>
                            <?php echo Form::text('container_no[]', $bookingContainer->container_no, [
                                'class' => 'form-control',
                                'placeholder' => __('admin.container_no'),
                            ]); ?>

                        </td>
                        <td><?php echo e($bookingContainer->container?->full_name ?? ''); ?></td>
                        <td>
                            <?php if($bookingContainer->branch): ?>
                                <?php echo Form::select(
                                    'branches[]',
                                    factoryBranches($bookingContainer->branch?->factory?->id),
                                    isset($booking) ? $bookingContainer?->branch_id ?? old('branch_id')[$key] : old('branch_id'),
                                    [
                                        'id' => 'branch_name_input branch_' . $key,
                                        'class' => 'form-control branches',
                                        'placeholder' => __('admin.choose_branch'),
                                    ],
                                ); ?>

                            <?php endif; ?>
                            <?php $__errorArgs = ['branches.' . $key];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="form-text text-danger"><?php echo e($message); ?></span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </td>
                        <td>
                            <?php echo Form::date('arrival_dates[]', $bookingContainer->arrival_date, [
                                'class' => 'form-control arrival_date_input',
                                'id' => 'arrival_date_input',
                            ]); ?>

                            <?php $__errorArgs = ['arrival_dates.' . $key];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="form-text text-danger"><?php echo e($message); ?></span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </td>
                        <td>
                            <a href="javascript:void(0)" class="mt-2 text-center"
                                onclick="remove(<?php echo e($key + 1); ?>)">
                                <i class="fa fa-trash text-danger mt-3 ml-12"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php else: ?>
                <?php if(!is_null(old('container_no')) || !is_null(old('arrival_date'))): ?>
                    <?php for($i = 0; $i < count(old('container_no')); $i++): ?>
                        <tr id="row_<?php echo e($i); ?>" data-id="<?php echo e($i); ?>">
                            <input type="hidden" name="factoriesIDs[]" class="form-control"
                                value="<?php echo e(old('factoriesIDs')[$i]); ?>">
                            <input type="hidden" name="containers[]" class="form-control"
                                value="<?php echo e(old('containers')[$i]); ?>">
                            <td><?php echo e($i + 1); ?></td>
                            <td>
                                <?php echo Form::text('sail_of_numbers[]', !is_null(old('sail_of_numbers')) ? old('sail_of_numbers')[$i] : null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('admin.navigational_torrent'),
                                ]); ?>

                                <?php $__errorArgs = ['sail_of_numbers.' . $i];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <span class="form-text text-danger"><?php echo e($message); ?></span>x
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </td>
                            <td>
                                <?php echo Form::text('container_no[]', !is_null(old('container_no')) ? old('container_no')[$i] : null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('admin.container_no'),
                                ]); ?>

                                <?php $__errorArgs = ['container_no.' . $i];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <span class="form-text text-danger"><?php echo e($message); ?></span>x
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </td>
                            <td><?php echo e(containerType(old('containers')[$i])); ?></td>
                            <td>
                                <?php echo Form::select(
                                    'branches[]',
                                    factoryBranches(old('factoriesIDs')[$i]),
                                    !is_null(old('branches')) && is_array(old('branches')) ? old('branches')[$i] : null,
                                    [
                                        'id' => 'branch_name_input branches_' . $i,
                                        'class' => 'form-control branches',
                                        'placeholder' => __('admin.choose_branch'),
                                    ],
                                ); ?>

                                <?php $__errorArgs = ['branches.' . $i];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <span class="form-text text-danger"><?php echo e($message); ?></span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </td>
                            <td>
                                <?php echo Form::date(
                                    'arrival_dates[]',
                                    !is_null(old('arrival_dates')) && is_array(old('arrival_dates')) ? old('arrival_dates')[$i] : null,
                                    ['class' => 'form-control arrival_date_input', 'id' => 'arrival_date_input'],
                                ); ?>

                                <?php $__errorArgs = ['arrival_dates.' . $i];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <span class="form-text text-danger"><?php echo e($message); ?></span>x
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </td>
                            <td>
                                <a href="javascript:void(0)" class="mt-2 text-center"
                                    onclick="remove(<?php echo e($i + 1); ?>)">
                                    <i class="fa fa-trash text-danger mt-3 ml-12"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endfor; ?>
                    
                <?php endif; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $__env->startPush('js'); ?>
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
                        <input type="number" name="sail_of_numbers[]" class="form-control" placeholder="<?php echo e(__('admin.navigational_torrent')); ?>">
                    </td>
                    <td>
                        <input type="number" name="container_no[]" class="form-control" placeholder="<?php echo e(__('admin.container_no')); ?>">
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
<?php $__env->stopPush(); ?>
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/bookings/thirdStep/outbound.blade.php ENDPATH**/ ?>