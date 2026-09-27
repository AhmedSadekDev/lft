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
                    <?php echo Form::label("input_service_categories", __('admin.serviceCategory'), ["class" => "required-field"]); ?>

                    <?php echo Form::select('service_category_id' , $serviceCategories, old('service_category_id') , ["class" => "form-control", "id" => "input_service_categories", "placeholder"=> __('admin.serviceCategory')]); ?>

                    <?php $__errorArgs = ['service_category_id'];
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
                    <?php echo Form::label("input_service", __('admin.services'), ["class" => "required-field"]); ?>

                    <?php echo Form::select('service_id' , [], old('service_id') , ["class" => "form-control", "id" => "input_service", "placeholder"=> __('admin.services')]); ?>

                    <?php $__errorArgs = ['service_id'];
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
                    <?php echo Form::label("input_cost", __('admin.cost'), ["class" => "required-field"]); ?>

                    <?php echo Form::number('cost' , old('cost'), ["class" => "form-control", "id" => "input_cost", "min" => "1","placeholder"=> __('admin.cost')]); ?>

                    <?php $__errorArgs = ['cost'];
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

    <script>
        $('#input_service_categories').on('change', function(){
            var classification_id = $(this).val();
            if(classification_id == ''){
                return;
            }

            var url = "<?php echo e(route('services.getServices', ':id')); ?>"
            url = url.replace(':id', classification_id);
            var token = '<?php echo e(csrf_token()); ?>';

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            $.ajax({
                url:url,
                type:'GET',
                success:function(res){
                    $('#input_service').empty();
                    $('#input_service').append(`<option value=""><?php echo e(__('admin.choose_service')); ?></option>`);
                    $.each(res, function(i, v){
                        console.log(i, v);
                        $('#input_service').append(`<option value="${i}">${v}</option>`);
                    });
                    var service_id = `<?php echo e(isset($booking) ? ($booking->service_type ?? old('service_type')): old('service_type')); ?>`;
                    if(service_id != ''){
                        $('#input_service option[value='+service_id+']').attr('selected','selected');
                    }
                }
            })
        });
    </script>

<?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\companyServices\form.blade.php ENDPATH**/ ?>