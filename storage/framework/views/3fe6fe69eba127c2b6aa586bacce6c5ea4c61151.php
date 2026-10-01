<?php if($method == 'POST'): ?>
    <?php echo Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true]); ?>

<?php elseif($method == 'PUT'): ?>
    <?php echo Form::model($serviceCategory, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]); ?>

<?php endif; ?>
    <div class="card-body">
        <div class="row">
            <!-- For loop this div -->
            <div class="col-md-6 col-sm-12">
                <div class="form-group">
                    <?php echo Form::label("input_title", __('admin.en_title'), ["class" => "required-field"]); ?>

                    <?php echo Form::text('title[en]' , (isset($serviceCategory) ? $serviceCategory->getTranslations('title')['en'] : old('title[en]')) , ["class" => "form-control", "id" => "input_title", "placeholder"=> __('admin.en_title')]); ?>

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

                    <?php echo Form::text('title[ar]' , (isset($serviceCategory) ? $serviceCategory->getTranslations('title')['ar'] : old('title[ar]')), ["class" => "form-control", "id" => "input_title", "placeholder"=> __('admin.ar_title')]); ?>

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
                    <?php echo Form::label("input_service_status", __('admin.add_to_invoice'), ["class" => "required-field"]); ?>

                    <?php echo Form::select('service_status' , $status, old('service_status') , ["class" => "form-control", "id" => "input_service_status", "placeholder"=> __('admin.service_status')]); ?>

                    <?php $__errorArgs = ['service_status'];
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

            <div class="col-md-6 col-sm-12">
                <div class="form-group">
                    <?php echo Form::label("input_invoice_print_section", __('admin.invoice_print_section'), ["class" => "required-field"]); ?>

                    <?php echo Form::select(
                        'invoice_print_section',
                        $print_sections ?? [],
                        old('invoice_print_section', isset($serviceCategory) ? $serviceCategory->invoice_print_section : null),
                        ["class" => "form-control", "id" => "input_invoice_print_section", "placeholder" => __('admin.invoice_print_section')]
                    ); ?>

                    <?php $__errorArgs = ['invoice_print_section'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <small class="aleart text-danger"><?php echo e($message); ?></small>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <small class="text-muted d-block mt-1">تحديد مكان ظهور الفئة عند طباعة الفاتورة: ضريبية / إيصالات / خدمات إضافية</small>
                </div>
            </div>
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

<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/serviceCategories/form.blade.php ENDPATH**/ ?>