<?php if($method == 'POST'): ?>
    <?php echo Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true]); ?>

<?php elseif($method == 'PUT'): ?>
    <?php echo Form::model($service, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]); ?>

<?php endif; ?>
    <div class="card-body">
        <div class="row">

            <!-- For loop this div -->
            <div class="col-md-6 col-sm-12">
                <div class="form-group">
                    <?php echo Form::label("input_service_categories", __('admin.superagents'), ["class" => "required-field"]); ?>

                    <?php echo Form::select('superagent_id' , $superagents, old('superagent_id') , ["class" => "form-control selectpicker", "id" => "superagents", "placeholder"=> __('admin.superagents'),"data-live-search" => "true"]); ?>

                    <?php $__errorArgs = ['superagent_id'];
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
                    <?php echo Form::label("input_financial_custody", __('admin.financial_custody'), ["class" => "required-field"]); ?>

                    <?php echo Form::number('value' , old('value'), ["class" => "form-control", "id" => "input_financial_custody", "placeholder"=> __('admin.financial_custody')]); ?>

                    <?php $__errorArgs = ['value'];
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
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/financial_custody_superagents/form.blade.php ENDPATH**/ ?>