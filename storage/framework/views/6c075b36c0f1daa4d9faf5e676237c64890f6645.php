<?php if($method == 'POST'): ?>
    <?php echo Form::open(['url' => $action, 'method' => $method]); ?>

<?php elseif($method == 'PUT'): ?>
    <?php echo Form::model($supplier, ['url' => [$action], 'method' => $method]); ?>

<?php endif; ?>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 col-sm-12">
                <div class="form-group">
                    <?php echo Form::label('input_name', __('admin.name'), ['class' => 'required-field']); ?>

                    <?php echo Form::text('name', old('name', isset($supplier) ? $supplier->name : null), [
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
                        <small class="text-danger"><?php echo e($message); ?></small>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <?php if($method == 'POST'): ?>
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        <?php echo Form::label('input_balance', 'الرصيد الافتتاحي'); ?>

                        <?php echo Form::number('balance', old('balance', 0), [
                            'class' => 'form-control',
                            'id' => 'input_balance',
                            'step' => '0.01',
                            'min' => '0',
                        ]); ?>

                        <?php $__errorArgs = ['balance'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <small class="text-danger"><?php echo e($message); ?></small>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-footer">
        <?php if($method == 'POST'): ?>
            <?php echo Form::submit(__('admin.save'), ['class' => 'btn btn-primary']); ?>

        <?php else: ?>
            <?php echo Form::submit(__('admin.update'), ['class' => 'btn btn-primary']); ?>

        <?php endif; ?>
    </div>
<?php echo Form::close(); ?>

<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/suppliers/form.blade.php ENDPATH**/ ?>