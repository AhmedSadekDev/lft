<?php if($method == 'POST'): ?>
    <?php echo Form::open(['url' => $action, 'method' => $method, 'enctype' => 'multipart/form-data', 'files' => true]); ?>

<?php elseif($method == 'PUT'): ?>
    <?php echo Form::model($company, [
        'url' => [$action],
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
    ]); ?>

<?php endif; ?>
<div class="card-body">
    <div class="row">
        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_name', __('admin.name')); ?>

                <?php echo Form::text('name', old('name'), [
                    'class' => 'form-control',
                    'id' => 'input_name',
                    'placeholder' => __('admin.name'),
                ]); ?>

                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <small class="aleart text-danger"><?php echo e($message); ?></small>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_email', __('admin.email')); ?>

                <?php echo Form::text('email', old('email'), [
                    'class' => 'form-control',
                    'id' => 'input_email',
                    'placeholder' => __('admin.email'),
                ]); ?>

                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <small class="aleart text-danger"><?php echo e($message); ?></small>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <!-- For loop this div -->
        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_address', __('admin.address')); ?>

                <?php echo Form::text('address', old('address'), [
                    'class' => 'form-control',
                    'id' => 'input_address',
                    'placeholder' => __('admin.address'),
                ]); ?>

                <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <small class="aleart text-danger"><?php echo e($message); ?></small>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_tax_no', __('admin.tax_no')); ?>

                <?php echo Form::text('tax_no', old('tax_no'), [
                    'class' => 'form-control',
                    'id' => 'input_tax_no',
                    'placeholder' => __('admin.tax_no'),
                ]); ?>

                <?php $__errorArgs = ['tax_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <small class="aleart text-danger"><?php echo e($message); ?></small>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <!--begin::Select -->
            <div class="form-group">
                <?php echo Form::label('taxed_input', __('admin.taxed_status')); ?>

                <?php echo Form::select('taxed', [0 => __('admin.no'), 1 => __('admin.yes')], old('taxed', isset($company) ? $company->taxed : null), [
                    'id' => 'add_vat_input',
                    'class' => 'form-control',
                    'placeholder' => __('admin.taxed_status'),
                ]); ?>

                <?php $__errorArgs = ['taxed'];
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
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12" id="private_company_wrapper" style="display: none;">
            <!--begin::Select -->
            <div class="form-group">
                <?php echo Form::label('private_company_input', 'الشركة الخاصة'); ?>

                <?php echo Form::select('private_company_id', $privateCompanies ?? [], old('private_company_id', isset($company) ? $company->private_company_id : null), [
                    'id' => 'private_company_input',
                    'class' => 'form-control',
                    'placeholder' => 'اختر الشركة الخاصة',
                ]); ?>

                <?php $__errorArgs = ['private_company_id'];
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
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_phone', __('admin.phone')); ?>

                <?php echo Form::text('phone', old('phone'), [
                    'class' => 'form-control',
                    'id' => 'input_phone',
                    'placeholder' => __('admin.phone'),
                ]); ?>

                <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <small class="aleart text-danger"><?php echo e($message); ?></small>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_opening_balance', 'الرصيد الافتتاحي'); ?>

                <?php echo Form::number('opening_balance', old('opening_balance', isset($company) ? $company->opening_balance : null), [
                    'class' => 'form-control',
                    'id' => 'input_opening_balance',
                    'placeholder' => 'الرصيد الافتتاحي',
                    'step' => '0.01',
                    'min' => '0',
                ]); ?>

                <?php $__errorArgs = ['opening_balance'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <small class="aleart text-danger"><?php echo e($message); ?></small>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12 mt-2">
            <div class="form-group">
                <?php echo Form::label('default_type', __('admin.default_billing_type')); ?>

                <div class="row" id="default_type">
                    <div class="col-6">
                        <?php echo Form::radio('bill_type', '1', ['class' => 'form-check-input col-1 mt-3', 'id' => 'input_invoice']); ?>

                        <?php echo Form::label('input_invoice', __('admin.invoice')); ?>

                    </div>
                    <div class="col-6">
                        <?php echo Form::radio('bill_type', '2', ['class' => 'form-check-input col-1 mt-3', 'id' => 'input_statment']); ?>

                        <?php echo Form::label('input_statment', __('admin.statment')); ?>

                    </div>
                </div>
                <?php $__errorArgs = ['bill_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <small class="aleart text-danger"><?php echo e($message); ?></small>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_attachments', __('admin.attachments')); ?>

                <?php echo Form::file('attachments[]', [
                    'multiple' => true,
                    'class' => 'form-control',
                    'id' => 'input_attachments',
                    'placeholder' => __('admin.attachments'),
                ]); ?>

                <?php $__errorArgs = ['attachments.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <small class="aleart text-danger"><?php echo e($message); ?></small>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
    </div>
</div>

<div class="card-footer">
    <?php if($method == 'POST'): ?>
        <?php echo Form::submit(__('admin.save'), [
            'class' => 'btn btn-primary btn-md text-uppercase font-weight-bold chat-send py-2 px-6',
        ]); ?>

    <?php elseif($method == 'PUT'): ?>
        <?php echo Form::submit(__('admin.update'), ['class' => 'btn btn-primary']); ?>

    <?php endif; ?>
</div>

</form>
<?php echo Form::close(); ?>

<!-- /.card-body -->

<?php $__env->startPush('js'); ?>
<script>
    $(document).ready(function() {
        // Function to toggle private company select visibility
        function togglePrivateCompanySelect() {
            var taxedValue = $('#add_vat_input').val();
            if (taxedValue == '1') {
                $('#private_company_wrapper').slideDown();
            } else {
                $('#private_company_wrapper').slideUp();
                $('#private_company_input').val('');
            }
        }

        // Check on page load
        togglePrivateCompanySelect();

        // Listen for changes
        $('#add_vat_input').on('change', function() {
            togglePrivateCompanySelect();
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/companies/form.blade.php ENDPATH**/ ?>