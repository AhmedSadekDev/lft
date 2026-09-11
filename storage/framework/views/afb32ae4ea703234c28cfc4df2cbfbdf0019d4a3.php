<?php if($method == 'POST'): ?>
    <?php echo Form::open(['url' => $action, 'method' => $method, 'enctype' => 'multipart/form-data', 'files' => true]); ?>

<?php elseif($method == 'PUT'): ?>
    <?php echo Form::model($bank, [
        'url' => [$action],
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
    ]); ?>

<?php endif; ?>
<div class="card-body">
    <div class="row justify-content-center me-3">

        <div class="col-12">
            <div class="form-group">
                <label for="name"><?php echo e(__('admin.name')); ?></label>
                <input id="name" value="<?php echo e(isset($bank) ? (old('name') ?? $bank->name) :old('name')); ?>" class="form-control" type="text" name="name">
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="text-danger"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>


        <div class="col-12">
            <div class="form-group">
                <label for="amount"><?php echo e(__('main.balance')); ?></label>
                <input id="amount" value="<?php echo e(isset($bank) ? (old('amount') ?? $bank->amount) :old('amount')); ?>" class="form-control" type="number" name="amount">
                <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="text-danger"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
    </div>
</div>

<div class="card-footer">
    <div class="row ">

        <?php if($method == 'POST'): ?>
            <?php echo Form::submit(__('admin.save'), [
                'class' => 'btn btn-primary btn-md text-uppercase font-weight-bold chat-send py-2 px-6',
            ]); ?>

        <?php elseif($method == 'PUT'): ?>
            <?php echo Form::submit(__('admin.update'), ['class' => 'btn btn-primary']); ?>

        <?php endif; ?>
    </div>
</div>

</form>
<?php echo Form::close(); ?>

<!-- /.bankd-body -->
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/banks/form.blade.php ENDPATH**/ ?>