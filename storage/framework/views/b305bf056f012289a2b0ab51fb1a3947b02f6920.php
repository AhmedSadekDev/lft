<?php if($method == 'POST'): ?>
    <?php echo Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true]); ?>

<?php elseif($method == 'PUT'): ?>
    <?php echo Form::model($yard, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]); ?>

<?php endif; ?>
    <div class="card-body">
        <div class="row">
            <!-- For loop this div -->
            <div class="col-md-6 col-sm-12">
                <div class="form-group">
                    <?php echo Form::label("input_title", __('admin.en_title'), ["class" => "required-field"]); ?>

                    <?php echo Form::text('title[en]' , (isset($yard) ? $yard->getTranslations('title')['en'] : old('title[en]')) , ["class" => "form-control", "id" => "input_title", "placeholder"=> __('admin.en_title')]); ?>

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

                    <?php echo Form::text('title[ar]' , (isset($yard) ? $yard->getTranslations('title')['ar'] : old('title[ar]')), ["class" => "form-control", "id" => "input_title", "placeholder"=> __('admin.ar_title')]); ?>

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

<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/yards/form.blade.php ENDPATH**/ ?>