
<?php $__env->startSection('content'); ?>
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                اضافة استلام
            </div>
        </div>
        <?php echo Form::open([
            'url' => route('companyInvoice.update', [$invoice->company_id]),
            'method' => 'put',
            'enctype' => 'multipart/form-data',
            'files' => true,
        ]); ?>


        <div class="card-body">
            <div class="row">

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        <?php echo Form::label('input_image', 'صورة التحويل'); ?>

                        <?php echo Form::file('image', [
                            'class' => 'form-control',
                            'id' => 'input_image',
                            'placeholder' => 'صورة التحويل',
                        ]); ?>

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
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        <?php echo Form::label('input_total', 'القيمة المرسلة'); ?>

                        <?php echo Form::text('total', $invoice->total ?? old('total'), [
                            'class' => 'form-control',
                            'id' => 'input_total',
                            'placeholder' => 'القيمة المرسلة',
                            'required' => true,
                        ]); ?>

                        <?php $__errorArgs = ['total'];
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

                <!-- Add hidden input field here -->
                <?php echo Form::hidden('invoice_id', $invoice->id); ?>

                <!-- You can also add multiple hidden fields as needed -->

            </div>
        </div>

        <div class="card-footer">
            <?php echo Form::submit(__('admin.save'), [
                'class' => 'btn btn-primary btn-md text-uppercase font-weight-bold chat-send py-2 px-6',
            ]); ?>

        </div>

        <?php echo Form::close(); ?>

        <!-- /.card-body -->
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\companyInvoice\edit.blade.php ENDPATH**/ ?>