<?php if($method == 'POST'): ?>
    <?php echo Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true]); ?>

<?php elseif($method == 'PUT'): ?>
    <?php echo Form::model($sponser, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]); ?>

<?php endif; ?>
        <div class="card-body">
            <div class="row">

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        <?php echo Form::label("input_title", __('admin.en_title'), ["class" => "required-field"]); ?>

                        <?php echo Form::text('title[en]' , (isset($sponser) ? $sponser->getTranslations('title')['en'] : old('title[en]')) , ["class" => "form-control", "id" => "input_title", "placeholder"=> __('admin.en_title')]); ?>

                        <?php $__errorArgs = ['title.en'];
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
                        <?php echo Form::label("input_title", __('admin.ar_title'), ["class" => "required-field"]); ?>

                        <?php echo Form::text('title[ar]' , (isset($sponser) ? $sponser->getTranslations('title')['ar'] : old('title[ar]')), ["class" => "form-control", "id" => "input_title", "placeholder"=> __('admin.ar_title')]); ?>

                        <?php $__errorArgs = ['title.ar'];
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
                        <?php echo Form::label("input_description", __('admin.en_description'), ["class" => "required-field"]); ?>

                        <?php echo Form::text( 'description[en]' , (isset($sponser) ? $sponser->getTranslations('description')['en'] : old('description[ar]')), ["class" => "form-control", "id" => "input_description", "placeholder"=> __('admin.en_description')]); ?>

                        <?php $__errorArgs = ['description.en'];
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
                        <?php echo Form::label("input_description", __('admin.ar_description'), ["class" => "required-field"]); ?>

                        <?php echo Form::text( 'description[ar]' , (isset($sponser) ? $sponser->getTranslations('description')['ar'] : old('description[ar]')), ["class" => "form-control", "id" => "input_description", "placeholder"=> __('admin.ar_description')]); ?>

                        <?php $__errorArgs = ['description.ar'];
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
                        <?php echo Form::label("input_image", __('admin.image'), ["class" => "required-field"]); ?>

                        <?php echo Form::file('image', ["multiple" => true, "class" => "form-control", "id" => "input_image", "placeholder"=> __('admin.image')]); ?>

                        <?php $__errorArgs = ['image'];
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

<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/sponsers/form.blade.php ENDPATH**/ ?>