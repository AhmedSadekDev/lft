<?php if($method == 'POST'): ?>
    <?php echo Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data']); ?>

<?php elseif($method == 'PUT'): ?>
    <?php echo Form::model($container, ['url' => [$action], 'method'=>$method, 'enctype'=>'multipart/form-data']); ?>

<?php endif; ?>
        <div class="card-body">
            <div class="row">
                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        <?php echo Form::label("input_size", __('admin.size')); ?>

                        <?php echo Form::text( 'size' , old('size'), ["class" => "form-control", "id" => "input_size", "placeholder"=> __('admin.size')]); ?>

                        <?php $__errorArgs = ['size'];
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
                        <?php echo Form::label("input_type", __('admin.type')); ?>

                        <?php echo Form::text( 'type' , old('type'), ["class" => "form-control", "id" => "input_type", "placeholder"=> __('admin.type')]); ?>

                        <?php $__errorArgs = ['type'];
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

                
            </div>
        </div>

        <div class="card-footer">
            <?php if($method == 'POST'): ?>
                    <?php echo Form::submit(__('admin.save'), ["class"=>"btn btn-primary btn-md text-uppercase font-weight-bold chat-send py-2 px-6"]); ?>

                <?php elseif($method == 'PUT'): ?>
                    <?php echo Form::submit(__('admin.update'), ["class"=>"btn btn-primary"]); ?>

                <?php endif; ?>
        </div>

</form>

<!-- /.card-body -->
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/containers/form.blade.php ENDPATH**/ ?>