
<?php $__env->startSection('content'); ?>
<div class="container">
    <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => __('profile.title')], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php if(session('success')): ?>
        <div class="alert alert-success" role="status"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <div class="row">
        <div class="col-lg-7 mb-5">
            <div class="card card-custom">
                <div class="card-header"><h3 class="card-title"><?php echo e(__('profile.details')); ?></h3></div>
                <form action="<?php echo e(route('profile.update')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="profile_name"><?php echo e(__('admin.name')); ?></label>
                            <input id="profile_name" class="form-control" type="text" name="name" value="<?php echo e(old('name', $user->name)); ?>" required maxlength="255">
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="form-group">
                            <label for="profile_email"><?php echo e(__('admin.email')); ?></label>
                            <input id="profile_email" class="form-control" type="email" name="email" value="<?php echo e(old('email', $user->email)); ?>" required maxlength="255">
                            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="form-group">
                            <label for="profile_phone"><?php echo e(__('admin.phone')); ?></label>
                            <input id="profile_phone" class="form-control" type="tel" name="phone" value="<?php echo e(old('phone', $user->phone)); ?>"  maxlength="255">
                            <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="form-group">
                            <label for="profile_address"><?php echo e(__('admin.address')); ?></label>
                            <input id="profile_address" class="form-control" type="text" name="address" value="<?php echo e(old('address', $user->address)); ?>"  maxlength="2000">
                            <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <button class="btn btn-primary" type="submit"><?php echo e(__('admin.save')); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-5 mb-5">
            <div class="card card-custom">
                <div class="card-header"><h3 class="card-title"><?php echo e(__('profile.change_password')); ?></h3></div>
                <form action="<?php echo e(route('profile.password')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="card-body">
                        <p class="text-muted"><?php echo e(__('profile.password_hint')); ?></p>
                        <div class="form-group">
                            <label for="current_password"><?php echo e(__('profile.current_password')); ?></label>
                            <input id="current_password" class="form-control" type="password" name="current_password" autocomplete="current-password" required >
                            <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="form-group">
                            <label for="password"><?php echo e(__('profile.new_password')); ?></label>
                            <input id="password" class="form-control" type="password" name="password" autocomplete="new-password" required minlength="8">
                            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation"><?php echo e(__('profile.confirm_password')); ?></label>
                            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" autocomplete="new-password" required minlength="8">
                            <?php $__errorArgs = ['password_confirmation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <button class="btn btn-primary" type="submit"><?php echo e(__('profile.change_password')); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\profile\edit.blade.php ENDPATH**/ ?>