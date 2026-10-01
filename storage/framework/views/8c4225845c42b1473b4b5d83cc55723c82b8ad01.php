<?php if($method == 'POST'): ?>
    <?php echo Form::open(['url' => $action, 'method' => $method, 'enctype' => 'multipart/form-data', 'files' => true]); ?>

<?php elseif($method == 'PUT'): ?>
    <?php echo Form::model($privateCompany, [
        'url' => [$action],
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
    ]); ?>

<?php endif; ?>
<div class="card-body">
    <div class="row">
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_name', __('admin.name')); ?>

                <?php echo Form::text('name', old('name'), [
                    'class' => 'form-control',
                    'id' => 'input_name',
                    'placeholder' => __('admin.name'),
                    'required' => true,
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

        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_commercial_register', 'السجل التجاري'); ?>

                <?php echo Form::text('commercial_register', old('commercial_register'), [
                    'class' => 'form-control',
                    'id' => 'input_commercial_register',
                    'placeholder' => 'السجل التجاري',
                ]); ?>

                <?php $__errorArgs = ['commercial_register'];
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

        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_logo', 'اللوجو'); ?>

                <?php echo Form::file('logo', [
                    'class' => 'form-control',
                    'id' => 'input_logo',
                    'accept' => 'image/*',
                ]); ?>

                <?php $__errorArgs = ['logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <small class="aleart text-danger"><?php echo e($message); ?></small>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php if(isset($privateCompany) && $privateCompany->logo): ?>
                    <div class="mt-2">
                        <img src="<?php echo e($privateCompany->logo); ?>" alt="Logo"
                             style="max-width: 150px; max-height: 150px; border-radius: 4px;">
                        <p class="text-muted mt-1">اللوجو الحالي</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-12">
            <h5 class="font-weight-bold mt-3 mb-3">معلومات الاتصال</h5>
        </div>

        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_phone1', 'الهاتف الأول'); ?>

                <?php echo Form::text('phone1', old('phone1'), [
                    'class' => 'form-control',
                    'id' => 'input_phone1',
                    'placeholder' => '01001365666',
                ]); ?>

                <?php $__errorArgs = ['phone1'];
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

        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_phone2', 'الهاتف الثاني'); ?>

                <?php echo Form::text('phone2', old('phone2'), [
                    'class' => 'form-control',
                    'id' => 'input_phone2',
                    'placeholder' => '01013118008',
                ]); ?>

                <?php $__errorArgs = ['phone2'];
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

        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_tel_fax', 'تليفون - فاكس'); ?>

                <?php echo Form::text('tel_fax', old('tel_fax'), [
                    'class' => 'form-control',
                    'id' => 'input_tel_fax',
                    'placeholder' => '057 - 2292423',
                ]); ?>

                <?php $__errorArgs = ['tel_fax'];
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

        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_email', __('admin.email')); ?>

                <?php echo Form::email('email', old('email'), [
                    'class' => 'form-control',
                    'id' => 'input_email',
                    'placeholder' => 'leader@leaderfortrans.com',
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

        <div class="col-md-12 col-sm-12">
            <div class="form-group">
                <?php echo Form::label('input_address', __('admin.address')); ?>

                <?php echo Form::textarea('address', old('address'), [
                    'class' => 'form-control',
                    'id' => 'input_address',
                    'placeholder' => 'ميناء دمياط المجمع الاستثمارى وحدة ٢٠٢',
                    'rows' => 2,
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
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/private-companies/form.blade.php ENDPATH**/ ?>