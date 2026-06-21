<?php if($method == 'POST'): ?>
    <?php echo Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true]); ?>

<?php elseif($method == 'PUT'): ?>
    <?php echo Form::model($agent, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]); ?>

<?php endif; ?>
        <div class="card-body">
            <div class="row">
                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        <?php echo Form::label("input_name", __('admin.name')); ?>

                        <?php echo Form::text('name' , old('name'), ["class" => "form-control", "id" => "input_name", "placeholder"=> __('admin.name')]); ?>

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
                        <?php echo Form::label("input_email", __('admin.email')); ?>

                        <?php echo Form::text( 'email' , old('email'), ["class" => "form-control", "id" => "input_email", "placeholder"=> __('admin.email')]); ?>

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
                        <?php echo Form::label("input_phone", __('admin.phone')); ?>

                        <?php echo Form::text( 'phone' , old('phone'), ["class" => "form-control", "id" => "input_phone", "placeholder"=> __('admin.phone')]); ?>

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
<?php echo Form::close(); ?>

<!-- /.card-body -->

<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/agents/form.blade.php ENDPATH**/ ?>